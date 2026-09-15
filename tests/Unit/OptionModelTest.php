<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Model\Connection;
use Laika\Core\Model\OptionModel;

final class OptionModelTest extends TestCase
{
    private string $connection;

    public static function setUpBeforeClass(): void
    {
        // convert_to_string() lives with the framework's global helpers
        if (!function_exists('convert_to_string')) {
            require_once __DIR__ . '/../../helpers/functions/system.php';
        }
    }

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required.');
        }

        // A fresh in-memory database per test; OptionModel keeps per-connection statics
        $this->connection = 'opt_test_' . bin2hex(random_bytes(4));
        Connection::add(['driver' => 'sqlite', 'database' => ':memory:'], $this->connection);
    }

    public function testConstructorAcceptsAConnectionName(): void
    {
        // v5.1.0 called the name as a function: "Call to undefined function opt_test_...()"
        $option = new OptionModel($this->connection);

        $this->assertSame($this->connection, $option->connection);
    }

    public function testTableIsCreatedAndDefaultsAreSeeded(): void
    {
        $option = new OptionModel($this->connection);

        $tables = Connection::get($this->connection)
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'options'")
            ->fetchAll();

        $this->assertCount(1, $tables);
        $this->assertSame('Laika Framework', $option->single('app_name'));
        $this->assertSame('20', $option->single('data_limit'));
    }

    public function testInsertSingleUpdateRoundTrip(): void
    {
        $option = new OptionModel($this->connection);

        $this->assertNull($option->single('site_theme'));
        $this->assertSame('fallback', $option->single('site_theme', 'fallback'));

        $this->assertTrue($option->insert('site_theme', 'dark'));
        $this->assertFalse($option->insert('site_theme', 'light'), 'an existing key is not inserted twice');
        $this->assertSame('dark', $option->single('site_theme'));

        $this->assertTrue($option->update('site_theme', 'light'));
        $this->assertSame('light', $option->single('site_theme'));

        $this->assertFalse($option->update('no_such_key', 'x'));
    }

    public function testAStoredEmptyValueCountsAsExisting(): void
    {
        $option = new OptionModel($this->connection);

        $this->assertTrue($option->insert('blank', ''));
        $this->assertFalse($option->insert('blank', 'again'));
        $this->assertSame('', $option->single('blank', 'default'));
    }

    public function testConnectionsAreIsolated(): void
    {
        $other = 'opt_test_' . bin2hex(random_bytes(4));
        Connection::add(['driver' => 'sqlite', 'database' => ':memory:'], $other);

        $first  = new OptionModel($this->connection);
        $second = new OptionModel($other);

        $first->insert('only_here', 'yes');

        $this->assertSame('yes', $first->single('only_here'));
        $this->assertNull($second->single('only_here'));
        $this->assertSame('Laika Framework', $second->single('app_name'), 'each connection is seeded');
    }
}
