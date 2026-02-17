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

use Laika\Core\Helper\Directory;

// Application Infrastructure Info
class Infra
{
    /*============================ Model Info ============================*/
    public function getModels(): array
    {
        $files = APP_PATH . '/lf-app/Model';
        // Get Model Paths
        $paths = Directory::files($files, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $info = pathinfo($path, PATHINFO_FILENAME);
                $models[$info] = "Laika\\App\\Model\\{$info}";
            }
        }
        return $models;
    }

    /**
     * Migrate Models
     * @return void
     */
    public function migrateModels(): void
    {
        $models = array_keys($this->getModels());
        foreach ($models as $name) {
            $class = "\\Laika\\App\\Migration\\{$name}";

            // Check Class Exists
            if (!class_exists($class)) {
                throw new \Exception("Migration Class Not Found: {$class}");
            }
            // Migrate Model
            \call_user_func([new $class, 'migrate']);
        }
        return;
    }

    /*============================ Controllers Info ============================*/
    public function getControllers(): array
    {
        $files = str_replace('/', '\\', APP_PATH . '/lf-app/Controller');
        // Get Controller Paths
        $paths = Directory::scanRecursive($files, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = 'Laika\\App\\Controller\\' . str_replace(["{$files}\\", '.php', '/'], ['', '', '\\'], $path);
            }
        }
        return $models;
    }

    /*============================ Middlewares Info ============================*/
    public function getMiddlewares(): array
    {
        $files = str_replace('/', '\\', APP_PATH . '/lf-app/Middleware');
        // Get Middleware Paths
        $paths = Directory::scanRecursive($files, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = 'Laika\\App\\Middleware\\' . str_replace(["{$files}\\", '.php', '/'], ['', '', '\\'], $path);
            }
        }
        return $models;
    }

    /*============================ Afterwares Info ============================*/
    public function getAfterwares(): array
    {
        $files = str_replace('/', '\\', APP_PATH . '/lf-app/Afterware');
        // Get Afterware Paths
        $paths = Directory::scanRecursive($files, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = 'Laika\\App\\Afterware\\' . str_replace(["{$files}\\", '.php', '/'], ['', '', '\\'], $path);
            }
        }
        return $models;
    }

    /*============================ Views Info ============================*/
    public function getViews(): array
    {
        $files = str_replace('/', '\\', APP_PATH . '/lf-templates');
        // Get View Paths
        $paths = Directory::scanRecursive($files, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = str_replace(["{$files}\\", '/'], ['', '\\'], $path);
            }
        }
        return $models;
    }
}