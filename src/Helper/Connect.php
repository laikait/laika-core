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
use Laika\Model\ConnectionManager;
use Laika\Session\SessionManager;

class Connect
{
    /**
     * Database Connection
     * @return void
     */
    public static function db(): void
    {
        // Get Database Configs
        $configs = Config::get('database', default:[]);
        // Start All Connections
        if (!empty($configs)) {
            foreach ($configs as $name => $config) {
                try {
                    ConnectionManager::add($config, $name);
                } catch (\Throwable $th) {
                    throw new HttpException(500, "'{$name}' Database Error: {$th->getMessage()}", $th->getCode());
                }
            }
        }
        return;
    }

    /**
     * Set Time Zone
     * @return void
     */
    public static function timezone(): void
    {
        // Set Date Time
        date_default_timezone_set(option('time.zone', 'Europe/London'));
    }

    /**
     * Set Session
     * @return void
     */
    public static function session(): void
    {
        if (option_as_bool('dbsession', false)) {
            SessionManager::config(ConnectionManager::get());
            return;
        }
        SessionManager::config();
        return;
    }
}
