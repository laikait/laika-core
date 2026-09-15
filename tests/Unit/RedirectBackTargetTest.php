<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\Http\Redirect;

final class RedirectBackTargetTest extends TestCase
{
    private object $redirect;

    protected function setUp(): void
    {
        // backTarget() is the pure half of back(); send() exits, so it is tested alone
        $this->redirect = new class extends Redirect {
            public function target(?string $referer, string $host): string
            {
                return $this->backTarget($referer, $host);
            }
        };
    }

    public function testSameHostOnANonStandardPortIsFollowed(): void
    {
        // HTTP_HOST would be "localhost:8000"; the host from Url::host() has no port
        $this->assertSame('/users?page=2', $this->redirect->target('http://localhost:8000/users?page=2', 'localhost'));
    }

    public function testHostIsComparedCaseInsensitively(): void
    {
        $this->assertSame('/a', $this->redirect->target('https://Example.COM/a', 'example.com'));
    }

    public function testForeignHostFallsBackToRoot(): void
    {
        $this->assertSame('/', $this->redirect->target('https://evil.com/phish', 'example.com'));
    }

    public function testMissingOrRelativeRefererFallsBackToRoot(): void
    {
        $this->assertSame('/', $this->redirect->target(null, 'example.com'));
        $this->assertSame('/', $this->redirect->target('', 'example.com'));
        $this->assertSame('/', $this->redirect->target('/relative/path', 'example.com'));
    }

    public function testHeaderInjectionFallsBackToRoot(): void
    {
        $this->assertSame('/', $this->redirect->target("https://example.com/a\r\nSet-Cookie: x=1", 'example.com'));
    }

    public function testProtocolRelativePathsAreCollapsed(): void
    {
        // "Location: //evil.com/x" and "/\evil.com" would leave the site
        $this->assertSame('/evil.com/x', $this->redirect->target('https://example.com//evil.com/x', 'example.com'));
        $this->assertSame('/evil.com/x', $this->redirect->target('https://example.com/\\evil.com/x', 'example.com'));
    }

    public function testMissingPathBecomesRoot(): void
    {
        $this->assertSame('/', $this->redirect->target('https://example.com', 'example.com'));
        $this->assertSame('/?q=1', $this->redirect->target('https://example.com?q=1', 'example.com'));
    }
}
