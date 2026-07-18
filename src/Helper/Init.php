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
use Laika\Model\Connection;
use Laika\Session\SessionManager;

class Init
{
    /** @var bool Init status */
    protected static bool $booted = false;

    /**
     * Connect DB
     * @param ?string $name Connection Name. Default is 'default'
     * @return void
     */
    public function db(?string $name = null): void
    {
        // Skip If Already Booted
        if (self::$booted) return;

        $name = $name ?? 'default';

        if (!Connection::has($name)) {
            try {
                Connection::add(config('database', $name));
            } catch (PDOException $e) {
                throw new RuntimeException("Framework Failed To Connect [{$name}] Database: " . $e->getMessage());
            }

            self::$booted = true;
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

    /**
     * Init App Default
     * Start Database & DB Session
     * @param ?string $connection Connection Name. Default is null
     * @return void
     */
    public function default(?string $connection = null): void
    {
        $this->db($connection);
        $this->dbSession($connection);
    }
}
