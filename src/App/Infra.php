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

use Laika\Core\Service\Directory;
use Laika\Core\Service\File;

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
        $files = Directory::files(APP_PATH . '/lf-app/Model', 'php');
        return array_map(function ($file) { return 'App\\Model\\' . File::name($file); }, $files);
    }

    /**
     * Get All Schema Classes
     * @return array
     */
    public function getSchemaClasses(): array
    {
        $files = Directory::files(APP_PATH . '/lf-app/Migration', 'php');
        return array_map(function ($file) { return 'App\\Migration\\' . File::name($file); }, $files);
    }

    /*============================ Controllers Info ============================*/
    /**
     * Get Controller Classes
     * @return array
     */
    public function getControllerClasses(): array
    {
        $files = Directory::files(APP_PATH . '/lf-app/Controller', 'php');
        return array_map(function ($file) { return 'App\\Controller\\' . File::name($file); }, $files);
    }

    /*============================ Middlewares Info ============================*/
    /**
     * Get Middlewar Classes
     * @return array
     */
    public function getMiddlewareClasses(): array
    {
        $files = Directory::scan(APP_PATH . '/lf-app/Middleware', true, 'php');
        return array_map(function ($file) { return 'App\\Middleware\\' . File::name($file); }, $files);
    }

    /*============================ Afterwares Info ============================*/
    /**
     * Get Afterware Classes
     * @return array
     */
    public function getAfterwareClasses(): array
    {
        $files = Directory::files(APP_PATH . '/lf-app/Afterware', 'php');
        return array_map(function ($file) { return 'App\\Afterware\\' . File::name($file); }, $files);
    }

    /*============================ Template Info ============================*/
    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
    {
        $base = str_replace('\\', '/', APP_PATH . '/lf-templates');
        $paths = Directory::scan($base, true, ['html','twig']);
        $templates = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $path = str_replace('\\', '/', $path);
                $templates[] = str_replace($base, '', $path);
            }
        }
        return $templates;
    }
}
