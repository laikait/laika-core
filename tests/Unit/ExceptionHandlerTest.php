<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Laika\Core\Exceptions\Handler;

final class ExceptionHandlerTest extends TestCase
{
    private string $missing;

    protected function setUp(): void
    {
        $this->missing = sys_get_temp_dir() . '/laika-missing-' . bin2hex(random_bytes(6));
        Handler::register();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function testSuppressedWarningDoesNotThrow(): void
    {
        $this->assertFalse(@file_get_contents($this->missing));
    }

    public function testSuppressedMkdirRaceDoesNotThrow(): void
    {
        // Mirrors RateLimiter: a concurrent worker already created the directory
        $this->assertFalse(@mkdir(sys_get_temp_dir()));
    }

    public function testUnsuppressedWarningThrows(): void
    {
        $this->expectException(ErrorException::class);
        file_get_contents($this->missing);
    }
}
