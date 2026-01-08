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

class Filter
{
    /** @var array<string, array<int, callable[]>> $filters */
    private static array $filters = [];

    /**
     * Register a filter callback.
     * @param string   $filter   Filter name.
     * @param callable $callback The function to execute.
     * @param int      $priority Priority for execution (lower runs first).
     * @return void
     */
    public static function add_filter(string $filter, callable $callback, int $priority = 10): void
    {
        self::$filters[$filter][$priority][] = $callback;
        \ksort(self::$filters[$filter]);
    }

    /**
     * Apply all filters on a value.
     * @param string $filter Filter name.
     * @param mixed  $value  Value to filter.
     * @param mixed  ...$args Additional arguments to pass to callbacks.
     * @return mixed
     */
    public static function apply_filter(string $filter, mixed $value = null, mixed ...$args): mixed
    {
        if (!isset(self::$filters[$filter])) {
            return $value;
        }

        foreach (self::$filters[$filter] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = ($value === null && empty($args)) ? $callback() : $callback($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Get Filter Callbacks
     * @param ?string $filter Filter Name
     * @return array
     */
    public static function filter_info(string $filter)
    {
        if (!$filter) {
            return self::$filters;
        }
        return self::$filters[$filter] ?? throw new \InvalidArgumentException("Filter [{$filter} Doesn't Exists!]");
    }
}
