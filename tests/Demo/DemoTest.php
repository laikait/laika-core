<?php
declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\App\Http;
use Laika\Service\Date;

final class DemoTest extends TestCase
{
    public function testRouter()
    {
        Http::get('/', function() {
            return 'Hello, World!';
        })->name('home');
        $path = Http::url('home');
        $this->assertNotNull($path ?: null, "Failed to Initialize Router or Generate URL");
    }

    public function testDate(): void
    {
        $this->assertIsInt(Date::getTimeStamp(), "Failed to Initialize Date or Get Timestamp");
    }
}