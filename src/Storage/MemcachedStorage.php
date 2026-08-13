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

use Memcached;
use Laika\Core\Storage\Connection\MemcachedConnection;

/**
 * Memcached Storage
 *
 * addServer() Does Not Connect. A Dead Server is Not Reported Here,
 * It Surfaces Later as false From set() and null From get().
 */
class MemcachedStorage
{
    /**
     * @var Memcached
     */
    protected Memcached $client;

    /**
     * @var string $host
     */
    protected string $host;

    /**
     * @var int $port
     */
    protected int $port;

    /**
     * @var string $prefix
     */
    protected string $prefix;

    /**
     * @var int $expire
     */
    protected int $expire;

    public function __construct()
    {
        // Extension Check, Server Registration & SASL All Live in The Shared Factory
        $this->expire   =   (int) config('memcached', 'expire', 0);

        $this->client   =   MemcachedConnection::make();

        // Prefix All Keys Automatically
        $this->prefix((string) config('memcached', 'prefix', 'laika'));
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
        $this->prefix = strtolower(\rtrim($prefix, ':') . ':');
        $this->client->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
    }

    /**
     * Set Value
     * Memcached Limits a Key to 250 Bytes Including The Prefix
     * And Rejects Spaces & Control Characters
     * @param string $key Key Name
     * @param mixed $value Key Value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        // Memcached Reads Anything Over 30 Days as an Absolute Unix Timestamp
        $expire = ($this->expire > 2592000) ? \time() + $this->expire : $this->expire;

        return $this->client->set($key, $value, $expire);
    }

    /**
     * Get Value
     * @param string $key Key Name
     * @return mixed Null When The Key is Missing. A Dead Server Also Returns Null
     */
    public function get(string $key): mixed
    {
        $result = $this->client->get($key);

        // Return null if the key does not exist
        if (($result === false) && ($this->client->getResultCode() !== Memcached::RES_SUCCESS)) {
            return null;
        }
        return $result;
    }

    /**
     * Remove Value
     * @param string $key Key Name
     * @return bool False When The Key Does Not Exist
     */
    public function pop(string $key): bool
    {
        return $this->client->delete($key);
    }
}
