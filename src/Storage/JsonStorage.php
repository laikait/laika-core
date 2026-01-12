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

namespace Laika\Core\Storage;

use RuntimeException;

/**
 * JSON Storage
 */
class JsonStorage
{
    /**
     * @var string
     */
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: APP_PATH . '/lf-storage';
        if (!\realpath($this->path)) {
            throw new RuntimeException("Invalid Json Storage {$this->path}");
        }
    }

    /**
     * Set Json Key Value
     * @param string $name Json File Name
     * @param array $array Json Value
     */
    public function set(string $name, array $array): bool
    {
        $name = '/' . \trim($name, '/');
        $file = $this->path . $name . '.json';
        $old_contents = [];
        if (\is_file($file)) {
            $str = \file_get_contents($file);
            if (\is_string($str) && $str) {
                $decoded = \json_decode($str, true);
                $old_contents = $decoded ?: [];
            }
        }
        $contents = \array_merge($old_contents, $array);
        return \file_put_contents($file, \json_encode($contents, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_NUMERIC_CHECK)) !== false;
    }

    /**
     * Get Json Value
     * @param string $name Json File Name
     * @param ?string $key Json Key Name. Default is Null
     * @return int|null|string|array
     */
    public function get(string $name, ?string $key = null): int|null|string|array
    {
        $name = '/' . \trim($name, '/');
        $file = $this->path . $name . '.json';
        if (!\is_file($file)) {
            return null;
        }
        $array = \json_decode(\file_get_contents($file), true);
        if ($key === null) {
            return $array;
        }
        return $array[$key] ?? null;
    }


    /**
     * Pop A Value
     * @param string $name Json File Name
     * @param ?string $key Json Key Name. Default is Null
     * @return bool
     */
    public function pop(string $name, string $key): bool
    {
        $name = '/' . \trim($name, '/');
        $file = $this->path . $name . '.json';
        $arr = $this->get($name);
        \unlink($file);
        if (isset($arr[$key])) {
            unset($arr[$key]);
            $this->set($name, $arr);
            return true;
        }
        return false;
    }
}
