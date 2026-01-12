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

use InvalidArgumentException;

class Directory
{
    // Get Directories List From Directory
    /**
     * @param string $path Directory path
     * @return array
     * @throws InvalidArgumentException
     */
    public static function folders(string $path): array
    {
        $path = \realpath($path);
        if (!$path || !\is_dir($path)) {
            throw new InvalidArgumentException("Invalid directory: '{$path}'");
        }
        return \glob("{$path}/*", GLOB_ONLYDIR) ?: [];
    }

    // Get Files List From Directory
    /**
     * @param string $path Directory path
     * @param string $ext File extension(s) to filter (e.g., 'php' or ['php','json']), or '*' for all
     * @return array
     * @throws InvalidArgumentException
     */
    public static function files(string $path, string $ext = '*'): array
    {
        $path = \realpath($path);
        if (!$path || !\is_dir($path)) {
            throw new InvalidArgumentException("Invalid directory: '{$path}'");
        }
        $ext = \ltrim($ext, '.');
        return \glob("{$path}/*.{$ext}") ?: [];
    }

    /**
     * Check Directory Exists
     * @param string $path Directory Path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return \is_dir($path);
    }

    /**
     * Make Directory
     * @param string $path Directory Path
     * @param int $permissions Directory Permission. Default is 0755
     * @param bool $recursive Make Recursive Paths. Default is true
     * @return bool
     */
    public static function make(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        // Check Already Exists
        if (self::exists($path)) {
            return true;
        }
        return \mkdir($path, $permissions, $recursive);
    }

    /**
     * Delete Directories
     * @param $path Directory to Remove
     * @return bool
     */
    public static function pop(string $path): bool
    {
        // Return if Not Exists
        if (!self::exists($path)) {
            return true;
        }

        // Empty the Directory
        if (!self::empty($path)) {
            return false;
        }

        // Remove Directory
        return \rmdir($path);
    }

    public static function empty(string $path): bool
    {
        if (!self::exists($path)) {
            return true;
        }

        $files = \array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $fullPath = "{$path}/{$file}";
            if (\is_writable($fullPath)) {
                \is_file($fullPath) ? \unlink($fullPath) : self::empty($fullPath);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Recursively scans a directory.
     * @param string $path Directory path
     * @param bool $includeDirs Whether to include directories in the result
     * @param string|array $ext File extension(s) to filter (e.g., 'php' or ['php','json']), or '*' for all
     * @return array
     * @throws InvalidArgumentException
     */
    public static function scanRecursive(string $path, bool $includeDirs = true, string|array $ext = '*'): array
    {
        $path = \realpath($path);
        if (!$path || !is_dir($path)) {
            throw new InvalidArgumentException("Invalid directory: '{$path}'");
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        // Normalize extension filter
        $extList = \is_array($ext) ? \array_map('strtolower', $ext) : [$ext];
        $extList = \array_map(fn ($e) => \ltrim($e, '.'), $extList);

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if ($includeDirs) {
                    $result[] = $item->getPathname();
                }
            } else {
                if ($extList !== ['*']) {
                    $fileExt = \strtolower(\pathinfo($item->getFilename(), PATHINFO_EXTENSION));
                    if (!\in_array($fileExt, $extList, true)) {
                        continue;
                    }
                }
                $result[] = $item->getPathname();
            }
        }

        return $result;
    }
}
