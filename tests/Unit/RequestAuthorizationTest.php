<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\Http\Request;

final class RequestAuthorizationTest extends TestCase
{
    private const KEYS = ['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST'];

    /** @var array<string,mixed> */
    private array $saved = [];

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = $_SERVER[$key] ?? null;
            unset($_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
    }

    public function testRedirectAuthorizationIsRecovered(): void
    {
        // Apache + php-fpm: the .htaccess rule lands here after the rewrite
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer abc123';
        $request = new Request();

        $this->assertSame('Bearer abc123', $request->header('authorization'));
        $this->assertSame('Bearer abc123', $request->headers()['Authorization']);
    }

    public function testBasicCredentialsAreRebuilt(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'user';
        $_SERVER['PHP_AUTH_PW']   = 'secret';

        $this->assertSame('Basic ' . base64_encode('user:secret'), (new Request())->header('Authorization'));
    }

    public function testDirectHeaderWinsOverRedirect(): void
    {
        $_SERVER['HTTP_AUTHORIZATION']          = 'Bearer direct';
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer redirected';

        $this->assertSame('Bearer direct', (new Request())->header('Authorization'));
    }

    public function testNoAuthorizationReturnsNull(): void
    {
        $this->assertNull((new Request())->header('Authorization'));
    }
}
