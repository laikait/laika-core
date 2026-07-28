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

namespace Laika\Core\App;

use Laika\Core\Exceptions\ResourceException;

final class Resource
{
    /** @var array Registered Resources */
    private static array $resources = [];

    /**
     * Register Resource
     * @param string $name Resource Name. Example: model, afterware...
     * @param string $path Resource Path
     * @param ?string $base_namespace Resource Class Base Namespace
     * @return void
     */
    public static function register(string $name, string $path, ?string $base_namespace = null): void
    {
        // Validate Resource Name
        if (!preg_match('/^[a-z]+$/i', $name)) {
            throw new ResourceException("Invalid Resource Name [{$name}]");
        }

        // Validate Resource Path
        if (!is_dir($path)) {
            throw new ResourceException("Invalid Resource Path [{$path}]");
        }

        // Validate Resource Class Base Namespace
        if ($base_namespace && !preg_match('/^[a-zA-Z_\/\\\\]+$/', $base_namespace)) {
            throw new ResourceException("Invalid Resource Class Base Namespace [{$base_namespace}]");
        }

        $path = realpath($path);
        $name = strtolower($name);

        foreach (self::scan($path) as $f) {
            $f = realpath($f);
            if ($base_namespace) {
                $f = str_replace($path, '', $f);
                $info = pathinfo($f);
                $base_namespace = trim($base_namespace, '\\');
                $sub_namespace = trim($info['dirname'], './\\');
                $namespace = $sub_namespace ? "{$base_namespace}\\{$sub_namespace}" : $base_namespace;
                self::$resources[$name][] = "{$namespace}\\{$info['filename']}";
            } else {
                self::$resources[$name][] = $f;
            }
        }
    }

    /**
     * Get Resources
     * @param ?string $name Resource Name. Default is null
     * @return array
     */
    public static function getResources(?string $name = null): array
    {
        return $name ? (self::$resources[strtolower($name)] ?? []) : self::$resources;
    }

    ####################################################################################
    /*================================= INTERNAL API =================================*/
    ####################################################################################
    /**
     * Scan Directory for All php Files
     * @param string $dir
     * @return array
     */
    public static function scan(string $dir): array
    {
        // Scan for PHP files in the current folder
        $files = glob($dir . '/*.php') ?: [];
        
        // Find all subdirectories
        $subDirs = glob($dir . '/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];
        
        // Recurse through subdirectories and merge arrays
        foreach ($subDirs as $subDir) {
            $files = array_merge($files, self::scan($subDir));
        }
        
        return $files;
    }
}
