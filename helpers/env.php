<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Dotenv\Dotenv;

// Define Constants (mirrors loader.php — this file is required before the
// rest of loader.php runs, so it can't assume APP_PATH exists yet).
defined('APP_PATH') || define('APP_PATH', realpath(__DIR__ . '/../../../../'));

// Load `.env` (project root) into $_ENV/$_SERVER/getenv(), without
// overriding real environment variables already set by the server/CLI.
// safeLoad() is a no-op (not an error) when no `.env` file exists.
Dotenv::createImmutable(APP_PATH)->safeLoad();

if (!function_exists('env')) {
    /**
     * Get an Environment Variable (from .env / getenv), with type coercion.
     *
     * Framework and package internals (database, mail, redis, memcached,
     * queue, auth guards, app metadata, DEBUG/MEMORY_LIMIT/CLI_MEMORY_LIMIT,
     * ...) read their config values through this helper directly at every
     * call site — there's no PHP config-file layer, and (aside from
     * structural values like APP_PATH/DS) no boot-time constants either.
     * Defined here (rather than in the app-loaded function files) so it —
     * and .env — are available immediately, before the rest of loader.php
     * (and everything downstream of it) runs.
     *
     * @param string $key Environment variable name, e.g. 'DB_HOST'
     * @param mixed $default Value to return if the variable isn't set
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}
