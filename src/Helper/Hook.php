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

class Hook
{
    /** @var array $actions */
    private static array $actions = [];

    /**
     * Add Hook
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public static function add(string $hook, callable $callback, int $priority = 10): void
    {
        static::$actions[$hook][$priority][] = $callback;
    }

    /**
     * Do Hook
     * @param string $hook
     * @param mixed[] $args
     * @return void
     */
    public static function do(string $hook, mixed ...$args): void
    {
        $callbacks = static::$actions[$hook] ?? [];
        ksort($callbacks);
        foreach ($callbacks as $priority) {
            foreach ($priority as $callback) {
                $callback(...$args);
            }
        }
    }

    /**
     * Apply Hook
     * @param string $hook
     * @param mixed $default
     * @param mixed[] $args
     * @return mixed
     */
    public static function apply(string $hook, mixed $default = null, mixed ...$args): mixed
    {
        $callbacks = static::$actions[$hook] ?? [];
        ksort($callbacks);
        foreach ($callbacks as $priority) {
            foreach ($priority as $callback) {
                $default = $callback($default, ...$args);
            }
        }
        return $default;
    }
}
