<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use Laika\Cache\Cache as CacheManager;
use Laika\Core\Http\CSRF as CsrfObject;
use Laika\Core\Http\Response as ResponseObject;
use Laika\Core\Pipeline\CachePipeline;
use Laika\Route\Handler;
use Laika\Service\CSRF;
use Laika\Service\Cache;
use Laika\Service\Response;
use PHPUnit\Framework\TestCase;

/**
 * Every condition under which a response must NOT be stored is a way one
 * visitor's page could be served to another. Each gets its own test.
 */
final class CachePipelineTest extends TestCase
{
    private int $runs = 0;

    /** @var array Snapshot restored in tearDown */
    private array $server = [];

    protected function setUp(): void
    {
        // laika-cache and the Cache relay are newer than the published packages
        // this suite installs on its own. Until they are released it runs only
        // against an application's vendor tree:
        //   vendor/bin/phpunit --bootstrap <app>/vendor/autoload.php tests/Unit/CachePipelineTest.php
        if (!class_exists(CacheManager::class) || !class_exists(Cache::class)) {
            self::markTestSkipped('laika-cache is not installed alongside this laika-core.');
        }

        $this->server = $_SERVER;
        $_COOKIE = [];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/pricing?b=2&a=1';
        $_SERVER['HTTP_HOST'] = 'example.test';
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);

        // In memory, so nothing is written to the application's storage
        Cache::swap(new CacheManager(['driver' => 'array']));
        Response::swap(new ResponseObject());
        CSRF::swap(self::countingCsrf());

        $this->runs = 0;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_COOKIE = [];
        Cache::clearResolvedInstance();
        Response::clearResolvedInstance();
        CSRF::clearResolvedInstance();
    }

    /**
     * Counts tokens the way the real one does, but does not sign them: signing
     * needs lf-storage/keys/app.key, which a standalone checkout does not have.
     * The pipeline reads only issued(), so the signature is irrelevant here.
     */
    private static function countingCsrf(): CsrfObject
    {
        return new class () extends CsrfObject {
            public function generate(): string
            {
                $this->issued++;
                return bin2hex(random_bytes(16));
            }
        };
    }

    /** Run the pipeline once with a controller that counts its calls */
    private function request(?callable $controller = null, array $params = []): ?string
    {
        $controller ??= function (): string {
            $this->runs++;
            return 'page';
        };

        return (new CachePipeline())->handle($controller, $params);
    }

    public function testASecondRequestIsServedWithoutRunningTheController(): void
    {
        self::assertSame('page', $this->request());
        self::assertSame('page', $this->request());
        self::assertSame(1, $this->runs);
        self::assertSame('HIT', Response::getHeader('X-Laika-Cache'));
    }

    public function testAHitRestoresContentTypeStatusAndHeaders(): void
    {
        $controller = function (): string {
            $this->runs++;
            Response::setContentType('application/json');
            Response::setHeader('X-Custom', 'kept');
            return '{"ok":true}';
        };

        $this->request($controller);
        Response::swap(new ResponseObject());
        $this->request($controller);

        self::assertSame(1, $this->runs);
        self::assertSame('application/json', Response::getContentType());
        self::assertSame('kept', Response::getHeader('X-Custom'));
    }

    public function testQueryOrderDoesNotSplitTheEntry(): void
    {
        $this->request();
        $_SERVER['REQUEST_URI'] = '/pricing?a=1&b=2';
        $this->request();

        self::assertSame(1, $this->runs);
    }

    public function testTheTtlParameterIsNotPassedToTheController(): void
    {
        $params = [CachePipeline::TTL_PARAM => '60', 'id' => '5'];
        (new CachePipeline())->handle(fn () => 'page', $params);

        self::assertSame(['id' => '5'], $params);
    }

    /*========================== NEVER STORED ==========================*/

    public function testAResponseThatIssuedACsrfTokenIsNeverStored(): void
    {
        // lf_header() prints a single-use token bound to this visitor's user
        // agent into the body. Replayed to anyone else it is useless at best.
        $controller = function (): string {
            $this->runs++;
            return 'const TOKEN = "' . CSRF::generate() . '";';
        };

        $this->request($controller);
        $this->request($controller);

        self::assertSame(2, $this->runs);
    }

    public function testANon200ResponseIsNeverStored(): void
    {
        $controller = function (): string {
            $this->runs++;
            Response::setStatus(404);
            return 'missing';
        };

        $this->request($controller);
        $this->request($controller);

        self::assertSame(2, $this->runs);
    }

    public function testAnEmptyResponseIsNeverStored(): void
    {
        $controller = function (): string {
            $this->runs++;
            return '';
        };

        $this->request($controller);
        $this->request($controller);

        self::assertSame(2, $this->runs);
    }

    /*========================== NEVER CACHED ==========================*/

    /** @dataProvider uncacheableRequests */
    public function testRequestsThatMayBePersonalAreNeverCached(callable $arrange): void
    {
        $arrange();

        $this->request();
        $this->request();

        self::assertSame(2, $this->runs);
    }

    public static function uncacheableRequests(): array
    {
        return [
            'post'           => [static function (): void { $_SERVER['REQUEST_METHOD'] = 'POST'; }],
            'authorization'  => [static function (): void { $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer x'; }],
            'session cookie' => [static function (): void { $_COOKIE['LFSESS'] = 'abc'; }],
        ];
    }

    public function testRegisteredAsAGlobalPipelineItDeclinesToCache(): void
    {
        // Global pipelines run before a route's own authorization, so a hit
        // there would serve a protected page to anyone.
        $property = new \ReflectionProperty(Handler::class, 'globalPipelines');
        $before = $property->getValue();
        $property->setValue(null, [CachePipeline::class . '|cache_ttl=60']);

        try {
            $this->request();
            $this->request();
        } finally {
            $property->setValue(null, $before);
        }

        self::assertSame(2, $this->runs);
    }

    public function testABrokenCacheStillServesThePage(): void
    {
        $manager = new CacheManager(['driver' => 'broken']);
        $manager->extend('broken', static fn () => throw new \RuntimeException('down'));
        Cache::swap($manager);

        self::assertSame('page', $this->request());
        self::assertSame(1, $this->runs);
    }
}
