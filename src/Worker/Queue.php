<?php

declare(strict_types=1);

namespace Laika\Core\Worker;

use Laika\Model\Connection;
use Laika\Queue\Driver\DatabaseDriver;
use Laika\Queue\Driver\DatabaseFailedJobProvider;
use Laika\Queue\Driver\JsonDriver;
use Laika\Queue\Driver\JsonFailedJobProvider;
use Laika\Queue\Driver\RedisDriver;
use Laika\Queue\Interfaces\FailedJobProviderInterface;
use Laika\Queue\Interfaces\QueueDriverInterface;

/**
 * Resolves the queue driver / failed-job provider from lf-config/queue.php
 * — same selection logic as vendor/laikait/laika-queue/bin/worker, kept
 * here so the queue:* CLI commands (retry, failed, flush) can reach the
 * driver/failer directly without spinning up a full Worker.
 */
class Queue
{
    /**
     * @param string $default Driver name used when lf-config/queue.php doesn't set one
     */
    public static function driver(string $default = 'json'): QueueDriverInterface
    {
        $driverName = strtolower((string) config('queue', 'driver', $default));
        $connection = (string) config('queue', 'connection', 'default');

        if ($driverName === 'redis') {
            if (!class_exists(\Redis::class)) {
                throw new \RuntimeException("queue.driver is 'redis' but the redis extension isn't loaded.");
            }

            // Connection settings come from lf-config/redis.php via RedisConnection
            $prefix = trim((string) config('redis', 'prefix', 'laika'), ':') . ':queue';

            return RedisDriver::fromConfig([], $prefix);
        }

        if ($driverName === 'json') {
            return new JsonDriver();
        }

        Connection::add(config('database', $connection));
        $driver = new DatabaseDriver($connection);
        return $driver;
    }

    /**
     * @param string $default Driver name used when lf-config/queue.php doesn't set one
     */
    public static function failedProvider(string $default = 'database'): FailedJobProviderInterface
    {
        $driverName = strtolower((string) config('queue', 'driver', $default));
        $connection = (string) config('queue', 'connection', 'default');
        $failedDriverName = strtolower((string) config(
            'queue',
            'failed_driver',
            $driverName === 'database' ? 'database' : 'json'
        ));

        if ($failedDriverName === 'database') {
            // Tables come from `php laika app:migrate` — the schemas are
            // registered under extra.laika.resources in laika-queue's composer.json
            Connection::add(config('database', $connection));
            return new DatabaseFailedJobProvider($connection);
        }

        return new JsonFailedJobProvider();
    }
}
