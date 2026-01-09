<?php
declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\App\Router;
use Laika\Core\Http\Request;
use Laika\Core\Helper\Date;

final class DemoTest extends TestCase
{
    public function testRouter()
    {
        Router::get('/', function() {
            return 'Hello, World!';
        })->name('home');
        $path = Router::url('home');
        $this->assertNotNull($path ?: null, "Failed to Initialize Router or Generate URL");
    }

    public function testDate(): void
    {
        $date = new Date('1 day');
        $this->assertIsInt($date->getTimeStamp(), "Failed to Initialize Date or Get Timestamp");
    }

    public function testFile(): void
    {
        echo APP_PATH;
        $this->assertNotNull(is_file(__DIR__.'/DemoTest.php'));
    }

    public function testRequest(): void
    {
        $this->assertTrue(call_user_func([new Request, 'isGet']), "Failed to Detect GET Request");
    }
}