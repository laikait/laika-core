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
use Laika\Session\Service\Session;

class DB
{
    protected bool $booted = false;

    /**
     * Run Connection
     * @param string $name Connection Name. Default is 'default'
     * @return void
     */
    public function run(string $name = 'default'): void
    {
        // Skip If Already Booted
        if ($this->booted) return;

        if (!Connection::has($name)) {
            try {
                Connection::add(config('database', $name));
            } catch (PDOException $e) {
                throw new RuntimeException("Framework Failed To Connect [{$name}] Database: " . $e->getMessage());
            } finally {
                $this->booted = true;
            }
        }
    }

    /**
     * Get Connection
     * @param string $name Connection Name. Default is 'default'
     * @return PDO
     */
    public function connection(string $name = 'default'): PDO
    {
        return Connection::get($name);
    }

    /**
     * Session in DB
     * @param string $name Connection Name. Default is 'default'
     * @return void
     */
    public function session(string $name = 'default'): void
    {
        Session::config($this->connection($name));
    }
}