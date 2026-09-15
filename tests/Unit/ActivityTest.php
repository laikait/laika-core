<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Model\Connection;
use Laika\Core\Log\Activity;

final class ActivityTest extends TestCase
{
    private ?string $remoteAddr = null;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required.');
        }

        // from_ip is NOT NULL; the CLI has no client address
        $this->remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        if ($this->remoteAddr === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->remoteAddr;
        }
    }

    public function testSchemaIsCreatedOnTheNamedConnection(): void
    {
        // v5.1.0 built ActivitySchema without the connection, so the table
        // always landed on 'default' -- which is not even registered here
        $connection = 'act_test_' . bin2hex(random_bytes(4));
        Connection::add(['driver' => 'sqlite', 'database' => ':memory:'], $connection);

        $activity = new Activity();
        $activity->author('system')->log('Seeded a record')->event('test.created');

        $this->assertSame(1, $activity->insert($connection));

        $pdo = Connection::get($connection);
        $this->assertCount(1, $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'activities'")->fetchAll());
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM activities')->fetchColumn());
    }

    public function testNothingToInsertTouchesNoDatabase(): void
    {
        $this->assertSame(0, (new Activity())->insert('never_registered'));
    }
}
