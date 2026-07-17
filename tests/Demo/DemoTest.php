<?php
declare(strict_types=1);

namespace Laika\Tests\Unit;

use Laika\Route\Url;
use Laika\Route\Handler;
use Laika\Service\Date;
use PHPUnit\Framework\TestCase;

final class DemoTest extends TestCase
{
    public function testRouter()
    {
        Url::get('/', function() {
            return 'Hello, World!';
        })->name('home');
        $path = Handler::namedUrl('home');
        $this->assertNotNull($path ?: null, "Failed to Initialize Router or Generate URL");
    }

    public function testDate(): void
    {
        $this->assertIsInt(Date::getTimeStamp(), "Failed to Initialize Date or Get Timestamp");
    }
}