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
use Laika\Service\Config;
use Laika\Model\Connection;
use Laika\Session\SessionManager;

class Init
{
    /** @var bool Init connections status */
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
