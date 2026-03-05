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
    /**
     * Get All Model Classes
     * @return array
     */
    public function getModelClasses(): array
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
     * Get All Schema Classes
     * @return array
     */
    public function getSchemaClasses(): array
    {
        $files = APP_PATH . '/lf-app/Migration';
        // Get Model Paths
        $paths = Directory::files($files, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $info = pathinfo($path, PATHINFO_FILENAME);
                $models[$info] = "Laika\\App\\Migration\\{$info}";
            }
        }
        return $models;
    }

    /*============================ Controllers Info ============================*/
    /**
     * Get Controller Classes
     * @return array
     */
    public function getControllerClasses(): array
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
    /**
     * Get Middlewar Classes
     * @return array
     */
    public function getMiddlewareClasses(): array
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
    /**
     * Get Afterware Classes
     * @return array
     */
    public function getAfterwareClasses(): array
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
    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
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
