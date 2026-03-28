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
        $base = str_replace('/', '\\', APP_PATH . '/lf-app/Model');
        $paths = Directory::files($base, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                // Strip base path, normalise to forward slashes, remove extension
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $models[] = 'Laika\\App\\Model\\' . $class;
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
        $base = str_replace('/', '\\', APP_PATH . '/lf-app/Migration');
        $paths = Directory::files($base, 'php');
        $schemas = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                // Strip base path, normalise to forward slashes, remove extension
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $schemas[] = 'Laika\\App\\Migration\\' . $class;
            }
        }
        return $schemas;
    }

    /*============================ Controllers Info ============================*/
    /**
     * Get Controller Classes
     * @return array
     */
    public function getControllerClasses(): array
    {
        $base = str_replace('/', '\\', APP_PATH . '/lf-app/Controller');
        $paths = Directory::scanRecursive($base, true, 'php');
        $controllers = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                // Strip base path, normalise to forward slashes, remove extension
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $controllers[] = 'Laika\\App\\Controller\\' . $class;
            }
        }
        return $controllers;
    }

    /*============================ Middlewares Info ============================*/
    /**
     * Get Middlewar Classes
     * @return array
     */
    public function getMiddlewareClasses(): array
    {
        $base = str_replace('/', '\\', APP_PATH . '/lf-app/Middleware');
        $paths = Directory::scanRecursive($base, true, 'php');
        $middlewares = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $middlewares[] = 'Laika\\App\\Middleware\\' . $class;
            }
        }
        return $middlewares;
    }

    /*============================ Afterwares Info ============================*/
    /**
     * Get Afterware Classes
     * @return array
     */
    public function getAfterwareClasses(): array
    {
        $base = str_replace('/', '\\', APP_PATH . '/lf-app/Afterware');
        $paths = Directory::scanRecursive($base, true, 'php');
        $afterwares = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $afterwares[] = 'Laika\\App\\Afterware\\' . $class;
            }
        }
        return $afterwares;
    }

    /*============================ Views Info ============================*/
    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
    {
        $base = str_replace('/', '\\', APP_PATH . '/lf-templates');
        $paths = Directory::scanRecursive($base, true, 'php');
        print_r($paths);
        $templates = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $templates[] = str_replace(['/', '\\'], ['\\', '\\'], $relative);
            }
        }
        return $templates;
    }
}
