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

use Memcached as PhPMemcached;
use Laika\Core\Exceptions\ExtensionException;

/**
 * Memcached Storage
 */
class MemcachedStorage
{
    /**
     * @var PhPMemcached
     */
    protected PhPMemcached $client;

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
        if (!extension_loaded('memcached')) {
            throw new ExtensionException("Extension Not Loaded: [php-memcached]!", 500);
        }

        // Get Config
        $username       =   env('MEMCACHED_USERNAME', '');
        $password       =   env('MEMCACHED_PASSWORD', '');
        $this->host     =   env('MEMCACHED_HOST', '127.0.0.1');
        $this->port     =   (int) env('MEMCACHED_PORT', 11211);
        $this->prefix   =   env('MEMCACHED_PREFIX', 'laika');
        $this->expire   =   86400; // 1 Day
        $this->client   =   new PhPMemcached();


        // Avoid adding duplicate servers if this constructor runs multiple times
        $servers = $this->client->getServerList();
        if (empty($servers)) {
            $this->client->addServer($this->host, $this->port);
        }

        $this->client->setOption(PhPMemcached::OPT_PREFIX_KEY, $this->prefix . ':');

        // SASL auth (needs binary protocol)
        if ($username && $password) {
            $this->client->setOption(PhPMemcached::OPT_BINARY_PROTOCOL, true);
            $this->client->setSaslAuthData($username, $password);
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
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        return $this->client->set($key, $value, $this->expire);
    }

    /**
     * Get Value
     * @param string $key Key Name
     * @return mixed
     */
    public function get($key): mixed
    {
        $result = $this->client->get($key);

        // Return null if the key does not exist
        if (($result === false) && ($this->client->getResultCode() !== PhPMemcached::RES_SUCCESS)) {
            return null;
        }
        return $result;
    }

    /**
     * Remove Value
     * @param string $key Key Name
     * @return bool
     */
    public function pop($key): bool
    {
        if ($this->get($key) !== null) {
            return $this->client->delete($key);
        }
        return false;
    }
}
