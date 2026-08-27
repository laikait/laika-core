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

namespace Laika\Core\Helper;

use PDOException;
use RuntimeException;
use Laika\Service\Config;
use Laika\Model\Connection;
use Laika\Session\SessionConfig;
use Laika\Core\Storage\Connection\RedisConnection;
use Laika\Core\Storage\Connection\MemcachedConnection;

/**
 * Bootstrap Helper
 *
 * Wires framework services to the config files in lf-config/, so an application
 * bootstrap stays a one liner per service.
 *
 * db() Registers a Database Connection. Everything Else Here Selects a Session
 * Driver & Mirrors The Method Names of Laika\Session\SessionConfig:
 *
 *   Init::file(['path' => APP_PATH . '/lf-storage/sessions']);
 *   Init::model('default');
 *   Init::mysql('default');
 *   Init::redis();
 *   Init::memcached();
 *
 * Call One of Them Before The First Session:: Call. Clients Are Built From
 * lf-config/, So No Credential Ever Passes Through The Session Package.
 */
class Init
{
    /** @var array<string,bool> Init connections status */
    protected static array $connections = [];

    /**
     * Connect DB
     * @param ?string $name Connection Name. Default is 'default'
     * @return void
     */
    public function db(?string $name = null): void
    {
        $name = $name ?? 'default';

        // Skip If Already Booted
        if (array_key_exists(strtolower($name), self::$connections) && self::$connections[strtolower($name)]) return;

        if (!Connection::has($name)) {
            try {
                Connection::add(Config::get('database', $name));
            } catch (PDOException $e) {
                throw new RuntimeException("Framework Failed To Connect [{$name}] Database: " . $e->getMessage());
            }

            self::$connections[strtolower($name)] = true;
        }
    }

    /**
     * Session in Files
     * @param array $params Optional: 'path', 'prefix'. Default Path is session_save_path()
     * @return void
     */
    public function file(array $params = []): void
    {
        SessionConfig::file($params);
    }

    /**
     * Session in DB Through Laika Model
     * @param ?string $name Connection Name. Default is 'default'
     * @param bool $install Create The Sessions Table on First Use. Default is false
     * @return void
     */
    public function model(?string $name = null, bool $install = false): void
    {
        // The Handler Builds its Model Right Away & a Model Needs a Registered
        // Connection, Which Nothing Else Registers During a Web Request.
        $this->db($name);

        SessionConfig::model(['connection' => $name ?? 'default', 'install' => $install]);
    }

    /**
     * Session in DB Through Raw PDO. No Laika Model Required
     * @param ?string $name Connection Name. Default is 'default'
     * @param array $params Optional: 'table', 'install'
     * @return void
     */
    public function mysql(?string $name = null, array $params = []): void
    {
        $this->db($name);

        SessionConfig::mysql(Connection::get($name), $params);
    }

    /**
     * Session in Redis. Client Comes From lf-config/redis.php
     * @param array $params Optional: 'prefix', 'lifetime'
     * @return void
     */
    public function redis(array $params = []): void
    {
        SessionConfig::redis(RedisConnection::make(), $params);
    }

    /**
     * Session in Memcached. Client Comes From lf-config/memcached.php
     * @param array $params Optional: 'prefix', 'lifetime'
     * @return void
     */
    public function memcached(array $params = []): void
    {
        SessionConfig::memcached(MemcachedConnection::make(), $params);
    }
}
