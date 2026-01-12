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

use Laika\Core\Exceptions\HttpException;
use Laika\Session\SessionManager;
use Laika\Model\Connection;

class Connect
{
    /**
     * Database Connection
     * @return void
     */
    public static function db(): void
    {
        // Get Database Configs
        $config = Config::get('database', 'default');
        // Check 'default' Connection Exists
        if (empty($config)) {
            throw new \RuntimeException("Database [default] Connection Name Doesn't Exists!");
        }
        // Start Default Connections
        try {
            Connection::add($config);
        } catch (\Throwable $e) {
            throw new HttpException(500, "Database Connection Error: {$e->getMessage()}");
        }
        return;
    }

    /**
     * Set DB Session
     * @return void
     */
    public static function session(): void
    {
        if (Connection::has('default')) {
            SessionManager::config(Connection::get());
            return;
        }
        SessionManager::config();
        return;
    }
}
