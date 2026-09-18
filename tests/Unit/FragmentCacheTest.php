<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use Laika\Cache\Cache as CacheManager;
use Laika\Core\Http\CSRF as CsrfObject;
use Laika\Core\Template\Twig\CacheTokenParser;
use Laika\Service\CSRF;
use Laika\Service\Cache;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * {% cache %} is compiled into every template that uses it, so it has to be
 * right under both of Twig's rendering modes: echo, the 3.x default, and yield,
 * which Twig 4 makes mandatory. Every test runs under both.
 */
final class FragmentCacheTest extends TestCase
{
    /** @var array<string,int> Body executions by name */
    private array $runs = [];

    protected function setUp(): void
    {
        // Newer than the published packages this suite installs on its own; see
        // CachePipelineTest for how to run it against an application's vendor tree
        if (!class_exists(CacheManager::class) || !class_exists(Cache::class)) {
            self::markTestSkipped('laika-cache is not installed alongside this laika-core.');
        }

        Cache::swap(new CacheManager(['driver' => 'array']));
        CSRF::swap(self::countingCsrf());
        $this->runs = [];
    }

    protected function tearDown(): void
    {
        Cache::clearResolvedInstance();
        CSRF::clearResolvedInstance();
    }

    /**
     * Counts tokens the way the real one does, but does not sign them: signing
     * needs lf-storage/keys/app.key, which a standalone checkout does not have.
     * The cache reads only issued(), so the signature is irrelevant here.
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

    public static function modes(): array
    {
        return ['echo' => [false], 'yield' => [true]];
    }

    private function twig(bool $yield, array $templates): Environment
    {
        $twig = new Environment(new ArrayLoader($templates), [
            'use_yield'  => $yield,
            'cache'      => false,
            'autoescape' => 'html',
        ]);

        $twig->addTokenParser(new CacheTokenParser());
        $twig->addFunction(new TwigFunction('tick', function (string $name): int {
            return $this->runs[$name] = ($this->runs[$name] ?? 0) + 1;
        }));
        $twig->addFunction(new TwigFunction('token', static fn (): string => CSRF::generate()));

        return $twig;
    }

    /** @dataProvider modes */
    public function testAHitSkipsTheBody(bool $yield): void
    {
        $twig = $this->twig($yield, ['t' => "{% cache 'k' %}[{{ tick('body') }}]{% endcache %}"]);

        self::assertSame('[1]', $twig->render('t'));
        self::assertSame('[1]', $twig->render('t'));
        self::assertSame(1, $this->runs['body']);
    }

    /** @dataProvider modes */
    public function testEscapingIsTheSameOnAHitAsOnAMiss(bool $yield): void
    {
        // Captured output is already escaped: a hit must neither escape it again
        // nor hand it back raw
        $twig = $this->twig($yield, ['t' => "{% cache 'k' 60 %}{{ html }}{% endcache %}"]);
        $context = ['html' => '<script>x</script>'];

        $miss = $twig->render('t', $context);
        $hit = $twig->render('t', $context);

        self::assertSame('&lt;script&gt;x&lt;/script&gt;', $miss);
        self::assertSame($miss, $hit);
    }

    /** @dataProvider modes */
    public function testTheKeyIsAnExpression(bool $yield): void
    {
        $twig = $this->twig($yield, ['t' => "{% cache 'row-' ~ id %}{{ id }}{{ tick('row' ~ id) }}{% endcache %}"]);

        self::assertSame('71', $twig->render('t', ['id' => 7]));
        self::assertSame('81', $twig->render('t', ['id' => 8]));
        self::assertSame('71', $twig->render('t', ['id' => 7]));
    }

    /** @dataProvider modes */
    public function testCacheTagsNest(bool $yield): void
    {
        $twig = $this->twig($yield, [
            't' => "{% cache 'outer' %}o{{ tick('outer') }}{% cache 'inner' %}i{{ tick('inner') }}{% endcache %}{% endcache %}",
        ]);

        self::assertSame('o1i1', $twig->render('t'));
        self::assertSame('o1i1', $twig->render('t'));
        self::assertSame(['outer' => 1, 'inner' => 1], $this->runs);
    }

    /** @dataProvider modes */
    public function testAFragmentThatIssuedACsrfTokenIsNeverStored(bool $yield): void
    {
        // Single-use and bound to this visitor's user agent. Stored, the first
        // person to submit it would burn it for everyone.
        $twig = $this->twig($yield, ['t' => "{% cache 'k' %}{{ token() }}{{ tick('body') }}{% endcache %}"]);

        $first = $twig->render('t');
        $second = $twig->render('t');

        self::assertSame(2, $this->runs['body']);
        self::assertNotSame($first, $second);
    }

    /** @dataProvider modes */
    public function testABrokenCacheStillRendersTheBody(bool $yield): void
    {
        $manager = new CacheManager(['driver' => 'broken']);
        $manager->extend('broken', static fn () => throw new \RuntimeException('down'));
        Cache::swap($manager);

        $twig = $this->twig($yield, ['t' => "{% cache 'k' %}ok{% endcache %}"]);

        self::assertSame('ok', $twig->render('t'));
    }
}
