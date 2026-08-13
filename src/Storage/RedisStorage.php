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

use Redis;
use Laika\Core\Storage\Connection\RedisConnection;

/**
 * Redis Storage
 */
class RedisStorage
{
    /**
     * @var Redis
     */
    protected Redis $client;

    /**
     * @var string $host
     */
    protected string $host;

    /**
     * @var int $port
     */
    protected int $port;

    /**
     * @var int $expire
     */
    protected int $expire;

    public function __construct()
    {
        // Extension Check, Connect, Auth & Database Select All Live in The Shared Factory
        $this->expire   =   (int) config('redis', 'expire', 0);
        $this->client   =   RedisConnection::make();

        // Prefix All Keys Automatically
        $this->prefix((string) config('redis', 'prefix', 'laika'));
    }

    /**
     * Set Expire
     * @param int $seconds Time to Live. 0 or Less Means No Expire Time
     * @return void
     */
    public function expire(int $seconds): void
    {
        $this->expire = $seconds;
    }

    /**
     * Prefix All Keys
     * @param string $prefix Key Prefix. A Trailing Colon is Added Automatically
     * @return void
     */
    public function prefix(string $prefix): void
    {
        $prefix = strtolower(\rtrim($prefix, ':') . ':');
        $this->client->setOption(Redis::OPT_PREFIX, $prefix);
    }

    /**
     * Set Value
     * @param string $key Key Name
     * @param mixed $value Key Value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        return ($this->expire > 0)
            ? $this->client->setex($key, $this->expire, \serialize($value))
            : $this->client->set($key, \serialize($value));
    }

    /**
     * Get Value
     * @param string $key Key Name
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $value = $this->client->get($key);

        // Key Does Not Exist
        if ($value === false) {
            return null;
        }

        // 'b:0;' is a Legitimately Stored false. Anything Else Returning false is Corrupt Data
        $data = @\unserialize($value);
        return ($data === false && $value !== 'b:0;') ? null : $data;
    }

    /**
     * Pop Data
     * @param string $key Key Name
     * @return bool
     */
    public function pop(string $key): bool
    {
        return (bool) $this->client->del($key);
    }
}
