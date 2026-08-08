<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Storage;

use Laika\Core\Exceptions\ExtensionException;
use Redis as PhPRedis;
use RuntimeException;

/**
 * Redis Storage
 */
class RedisStorage
{
    /**
     * @var PhPRedis
     */
    protected PhPRedis $client;

    /**
     * @var string $host
     */
    protected string $host;

    /**
     * @var int $port
     */
    protected int $port;

    /**
     * @var string $prefic
     */
    protected string $prefix;

    /**
     * @var int $expire
     */
    protected int $expire;

    public function __construct()
    {
        // Check Extension Loaded
        if (!\extension_loaded('redis')) {
            throw new ExtensionException("Extension Not Loaded: [php-redis]!", 500);
        }

        // Get Config
        $password       =   env('REDIS_PASSWORD', '');
        $this->host     =   env('REDIS_HOST', '127.0.0.1');
        $this->port     =   (int) env('REDIS_PORT', 6379);
        $this->prefix   =   env('REDIS_PREFIX', 'laika');
        $this->expire   =   86400; // 1 Day

        $this->client   =   new PhPRedis();

        if (!$this->client->connect($this->host, $this->port)) {
            throw new RuntimeException("Unable to connect to Redis at {$this->host}:{$this->port}");
        }

        if ($password && !$this->client->auth($password)) {
            throw new RuntimeException("Redis authentication failed!");
        }
    }

    /**
     * Set Expire
     * @param int $expire
     * @return void
     */
    public function expire(int $seconds): void
    {
        $this->expire = $seconds;
        return;
    }

    /**
     * Set Value
     * @param string $key Key Name
     * @param mixed $value Key Value
     * @param int $expiration Default is 0 for No Expire Time
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        $key = $this->prefix . ':' . $key;
        return ($this->expire > 0)
            ? $this->client->setex($key, $this->expire, \serialize($value))
            : $this->client->set($key, \serialize($value));
    }

    /**
     * Get Value
     * @param string $key Key Name
     * @return mixed
     */
    public function get($key): mixed
    {
        $key = $this->prefix . ':' . $key;

        $value = $this->client->get($key);
        return $value !== false ? \unserialize($value) : null;
    }

    /**
     * Pop Data
     * @param string $key Key Name
     * @return bool
     */
    public function pop($key): bool
    {
        $key = $this->prefix . ':' . $key;

        return (bool) $this->client->del($key);
    }
}
