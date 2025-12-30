<?php

/**
 * Laika PHP MMC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
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
        $files = str_replace('/', '\\', APP_PATH . '/lf-app/Model');
        // Get Model Paths
        $paths = Directory::scanRecursive($files, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = 'Laika\\App\\Model\\' . str_replace(["{$files}\\", '.php', '/'], ['', '', '\\'], $path);
            }
        }
        return $models;
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