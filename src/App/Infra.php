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

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relay;

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
                $models[] = 'App\\Model\\' . $class;
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
                $schemas[] = 'App\\Migration\\' . $class;
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
        $paths = Directory::scan($base, true, 'php');
        $controllers = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                // Strip base path, normalise to forward slashes, remove extension
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $controllers[] = 'App\\Controller\\' . $class;
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
        $paths = Directory::scan($base, true, 'php');
        $middlewares = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $middlewares[] = 'App\\Middleware\\' . $class;
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
        $paths = Directory::scan($base, true, 'php');
        $afterwares = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('/', '\\', $path);
                $relative = ltrim(str_replace($base, '', $path), '/\\');
                $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $afterwares[] = 'App\\Afterware\\' . $class;
            }
        }
        return $afterwares;
    }

    /*============================ Template Info ============================*/
    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
    {
        $base = str_replace('/', '\\', APP_PATH . '/lf-templates');
        $paths = Directory::scan($base, true, 'php');
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

    /*============================ Relay Info ============================*/
    /**
     * Get Relay Names
     * @return array
     */
    public function getRelayClasses(): array
    {
        $relays = ['system' => [], 'user' => []];
        // System Generated
        $systemBase = str_replace('/', '\\', realpath(__DIR__ . '/../Relay/Relays'));
        $systemRelayPaths = Directory::scan($systemBase, true, 'php');

        foreach ($systemRelayPaths as $sPath) {
            if (is_file($sPath)) {
                $sPath = str_replace('/', '\\', $sPath);
                $relative = ltrim(str_replace($systemBase, '', $sPath), '/\\');
                $sClass = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
                $relays['system'][] = 'Laika\\Core\\Relay\\Relays\\' . $sClass;
            }
        }

        // User Generated
        $userBase = str_replace('/', '\\', APP_PATH . '/lf-app/Relay');
        $userRelayPaths = Directory::scan($userBase, true, 'php');

        foreach ($userRelayPaths as $uPath) {
            if (is_file($uPath)) {
                $uPath = str_replace('/', '\\', $uPath);
                $uRelative = ltrim(str_replace($userBase, '', $uPath), '/\\');
                $uClass = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $uRelative);
                $relays['user'][] = 'App\\Relay\\' . $uClass;
            }
        }
        return $relays;
    }
}
