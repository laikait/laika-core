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

use RuntimeException;
use Throwable;
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
        $name  = $name ?? 'default';
        $cache = strtolower($name);

        // Skip If Already Booted
        if (self::$connections[$cache] ?? false) return;

        // The flag used to be set only inside the branch below, so a connection
        // registered elsewhere never took this fast path.
        if (Connection::has($name)) {
            self::$connections[$cache] = true;
            return;
        }

        $config = Config::get('database', $name);

        // By far the likeliest failure, and one that used to surface as a bare
        // TypeError from Connection::add(null) rather than the message below.
        if (!is_array($config) || $config === []) {
            throw new RuntimeException("Database connection [{$name}] is not defined in lf-config/database.php.");
        }

        try {
            Connection::add($config);
        } catch (Throwable $e) {
            // Not just PDOException: a bad DSN raises InvalidArgumentException
            // or TypeError, neither of which the old clause caught.
            throw new RuntimeException("Framework Failed To Connect [{$name}] Database: " . $e->getMessage(), 0, $e);
        }

        self::$connections[$cache] = true;
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
