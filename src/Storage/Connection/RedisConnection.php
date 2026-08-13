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

use Redis;
use RedisException;
use Laika\Core\Exceptions\ExtensionException;

/**
 * Redis Connection Factory
 *
 * Builds a Connected, Bare Redis Client From lf-config/redis.php.
 * No Key Prefix & No Serialization Are Applied Here. Those Belong to The Caller,
 * So a Queue Driver Managing its Own Keys Gets The Same Connection Handling as a Cache.
 */
class RedisConnection
{
    /**
     * Open a Configured Connection
     * @param array $overrides Explicit Values That Win Over lf-config/redis.php
     * @return Redis
     * @throws ExtensionException
     */
    public static function make(array $overrides = []): Redis
    {
        // Check Extension Loaded
        if (!\extension_loaded('redis')) {
            throw new ExtensionException("Extension Not Loaded: [php-redis]!", 500);
        }

        $host        =  (string) self::value($overrides, 'host', '127.0.0.1');
        $port        =  (int) self::value($overrides, 'port', 6379);
        $timeout     =  (float) self::value($overrides, 'timeout', 2.5);
        $readTimeout =  (float) self::value($overrides, 'read_timeout', 2.5);

        $client = new Redis();

        try {
            if (!$client->connect($host, $port, $timeout, null, 0, $readTimeout)) {
                throw new ExtensionException("Unable to connect to Redis at {$host}:{$port}", 500);
            }
        } catch (RedisException $e) {
            throw new ExtensionException("Unable to connect to Redis at {$host}:{$port}", 500, $e);
        }

        // Auth Only When a Password is Actually Set. Empty String Means No Auth
        // 'auth' is a Deprecated Alias For 'password'
        $password = (string) (self::value($overrides, 'password', '') ?: self::value($overrides, 'auth', ''));

        if ($password !== '') {
            $username   =   (string) self::value($overrides, 'username', '');
            // Redis 6 ACL Needs [username, password] Pair
            $credential =   $username !== '' ? ['user' => $username, 'pass' => $password] : $password;

            try {
                if (!$client->auth($credential)) {
                    throw new ExtensionException("Redis authentication failed!", 500);
                }
            } catch (RedisException $e) {
                throw new ExtensionException($e->getMessage(), 500, $e);
            }
        }

        // Select Database Only When Not The Default [0]
        $database = (int) self::value($overrides, 'database', 0);

        if ($database !== 0) {
            try {
                if (!$client->select($database)) {
                    throw new ExtensionException("Unable to Select Redis Database [{$database}]", 500);
                }
            } catch (RedisException $e) {
                throw new ExtensionException($e->getMessage(), 500, $e);
            }
        }

        return $client;
    }

    /**
     * An Override Wins, Otherwise Fall Back to lf-config/redis.php
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

        return config('redis', $key, $default);
    }
}
