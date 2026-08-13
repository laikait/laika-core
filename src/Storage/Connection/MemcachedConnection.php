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

namespace Laika\Core\Storage\Connection;

use Memcached;
use Laika\Core\Exceptions\ExtensionException;

/**
 * Memcached Connection Factory
 *
 * Builds a Configured, Bare Memcached Client From lf-config/memcached.php.
 * No Key Prefix is Applied Here. That Belongs to The Caller, So a Queue or
 * Session Store Managing its Own Keys Gets The Same Connection Handling as a Cache.
 *
 * addServer() Only Registers a Server, it Does Not Connect. An Unreachable Server
 * is Not Reported Here. It Surfaces Later as false From set() & null From get().
 */
class MemcachedConnection
{
    /**
     * Open a Configured Connection
     * @param array $overrides Explicit Values That Win Over lf-config/memcached.php
     * @return Memcached
     * @throws ExtensionException
     */
    public static function make(array $overrides = []): Memcached
    {
        // Check Extension Loaded
        if (!\extension_loaded('memcached')) {
            throw new ExtensionException("Extension not loaded: [php-memcached]!");
        }

        $host   =   (string) self::value($overrides, 'host', '127.0.0.1');
        $port   =   (int) self::value($overrides, 'port', 11211);

        $client = new Memcached();
        $client->addServer($host, $port);

        // SASL auth (needs binary protocol)
        $username = (string) self::value($overrides, 'username', '');
        $password = (string) self::value($overrides, 'password', '');

        if ($username !== '' && $password !== '') {
            $client->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $client->setSaslAuthData($username, $password);
        }

        return $client;
    }

    /**
     * An Override Wins, Otherwise Fall Back to lf-config/memcached.php
     * @param array $overrides
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected static function value(array $overrides, string $key, mixed $default): mixed
    {
        if (isset($overrides[$key]) && $overrides[$key] !== null && $overrides[$key] !== '') {
            return $overrides[$key];
        }

        return config('memcached', $key, $default);
    }
}
