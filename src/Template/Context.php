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

namespace Laika\Core\Template;

use Laika\Core\Exceptions\ContextException;

class Context
{
    /** @var array Context */
    private static array $context = [];

    ########################################################################################
    /*=================================== EXTERNAL API ===================================*/
    ########################################################################################
    /**
     * Set a value in the context.
     * @param string $key The key to set.
     * @param mixed $value The value to set.
     */
    public static function set(string $key, mixed $value): void
    {
        // Validate the key
        self::validateKey($key);

        self::$context[$key] = $value;
    }

    /**
     * Get a value from the context.
     * @param ?string $key The key to retrieve.
     * @return mixed The value associated with the key, or null if not found.
     */
    public static function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return self::$context;

        // Validate the key
        self::validateKey($key);

        return self::$context[$key] ?? $default;
    }

    /**
     * Check if a key exists in the context.
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public static function has(string $key): bool
    {
        self::validateKey($key);
        return array_key_exists($key, self::$context);
    }

    /**
     * Remove a key from the context.
     * @param string $key The key to remove.
     * @return void
     */
    public static function pop(string $key): void
    {
        self::validateKey($key);
        unset(self::$context[$key]);
    }

    /**
     * Clear the entire context.
     * @return void
     */
    public static function clear(): void
    {
        self::$context = [];
    }

    ########################################################################################
    /*=================================== INTERNAL API ===================================*/
    ########################################################################################
    /**
     * Validate the key format.
     * @param string $key The key to validate.
     * @throws ContextException If the key format is invalid.
     * @return void
     */
    private static function validateKey(string $key): void
    {
        if (!preg_match('/^\w+$/', $key)) {
            throw new ContextException("Invalid key format: [{$key}]. Key must be a non-empty string containing only alphanumeric characters and underscores.");
        }
    }
}
