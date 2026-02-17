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

namespace Laika\Core\App;

// Application Environments
class Env
{
    // Environment Obkect
    private static ?object $instance = null;

    /**
     * Parameters
     * @var array<string,mixed>
     */
    private array $params = [];

    // Singleton Process
    private function __construct(){}

    // Get Instance
    private static function getInstance()
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    /**
     * Set Env
     * @param string $key Key Name of Environment. Example: 'route' or 'route|get'
     * @param mixed $value Key Value of Environment.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $instance = self::getInstance();
        $keys = array_filter(explode('|', $key), fn ($k) => $k !== '');

        $ref = &$instance->params;
        foreach ($keys as $k) {
            if (!isset($ref[$k]) || !is_array($ref[$k])) {
                $ref[$k] = [];
            }
            $ref = &$ref[$k];
        }
        $ref = $value;
        return;
    }

    /**
     * Get Env
     * @param string $key Key Name of Environment. Example: 'route' or 'route|get'
     * @param mixed $default Return Default Value if Key Does not Exists.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = self::getInstance();
        $keys = array_filter(explode('|', $key), fn ($k) => $k !== '');

        $ref = $instance->params;
        foreach ($keys as $k) {
            if (!isset($ref[$k])) {
                return $default;
            }
            $ref = $ref[$k];
        }
        return $ref;
    }

    /**
     * Get All Params
     * @return array
     */
    public static function all(): array
    {
        $instance = self::getInstance();
        return $instance->params;
    }
}
