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

use PDO;
use PDOException;
use RuntimeException;
use Laika\Model\Connection;
use Laika\Session\SessionManager;

class Init
{
    /** @var bool Init connections status */
    protected static array $connections = [];

    /**
     * Connect DB
     *
     * The 'default' connection is built from the DB_* environment variables.
     * Any other named connection must already be registered — via
     * `Connection::add($config, $name)` — before it's used here; there's no
     * generic env-var scheme for an arbitrary number of connections.
     *
     * @param ?string $name Connection Name. Default is 'default'
     * @return void
     */
    public function db(?string $name = null): void
    {
        $name = $name ?? 'default';

        // Skip If Already Booted
        if (array_key_exists(strtolower($name), self::$connections) && self::$connections[strtolower($name)]) return;

        if (!Connection::has($name)) {
            if (strtolower($name) !== 'default') {
                throw new RuntimeException(
                    "Database Connection [{$name}] Is Not Registered. Call Connection::add(\$config, '{$name}') before use."
                );
            }

            try {
                Connection::add([
                    'driver'   => env('DB_DRIVER', 'mysql'),
                    'host'     => env('DB_HOST', 'localhost'),
                    'port'     => (int) env('DB_PORT', 3306),
                    'database' => env('DB_DATABASE', 'test'),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                ], $name);
            } catch (PDOException $e) {
                throw new RuntimeException("Framework Failed To Connect [{$name}] Database: " . $e->getMessage());
            }

            self::$connections[strtolower($name)] = true;
        }
    }

    /**
     * Session in DB
     * @param ?string $name Connection Name. Default is 'default'
     * @return void
     */
    public function dbSession(?string $name = null): void
    {
        SessionManager::dbSessionConfig($name);
    }

    /**
     * Session in DB
     * @param array $params Connection Name. Default is 'default'
     * @return void
     */
    public function fileSession(array $params = []): void
    {
        SessionManager::fileSessionConfig($params);
    }
}
