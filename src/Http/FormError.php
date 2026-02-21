<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Http;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

class FormError
{
    // /**
    //  * @var FormError $formError Form Static Object
    //  */
    // private static FormError $formError;

    /**
     * @var array $errors Form Errors
     */
    private static array $errors = [];

    // /**
    //  * Initiate Form Object
    //  * @return FormError
    //  */
    // private static function instance(): FormError
    // {
    //     self::$formError ??= new self();
    //     return self::$formError;
    // }

    /**
     * Add Bulk Errors
     * @param array{string:array} $errors Form Errors
     * @return void
     */
    public static function addBulk(array $errors): void
    {
        self::$errors = \array_merge(self::$errors, $errors);
        return;
    }

    /**
     * Add Form Error
     * @param string $key Form Error Key
     * @param string $error Error Message
     * @return void
     */
    public static function add(string $key, string $error): void
    {
        self::$errors[$key][] = $error;
        return;
    }

    /**
     * Check Form Error Exists
     * @return bool
     */
    public static function exists(): bool
    {
        return !empty(self::$errors);
    }

    /**
     * Get Form Errors
     * @param string|null $key Get Specific Key Errors
     * @return array
     */
    public static function get(?string $key = null): array
    {
        return empty($key) ? self::$errors : self::$errors[$key] ?? [];
    }
}