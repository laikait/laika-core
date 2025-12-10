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

namespace Laika\Core\App\Route;

use Laika\Core\App\Router;

class Resource
{
    /**
     * @var string $appSrc
     */
    public static string $app = '/app-src';

    /**
     * @var string $templateSrc
     */
    public static string $template = '/template-src';

    /**
     * Accepted File Types
     * @var array $acceptedTypes
     */
    private static array $acceptedTypes = [
                'css'   =>  'text/css',
                'js'    =>  'application/javascript',
                'png'   =>  'image/png',
                'jpg'   =>  'image/jpeg',
                'jpeg'  =>  'image/jpeg',
                'gif'   =>  'image/gif',
                'svg'   =>  'image/svg+xml',
                'webp'  =>  'image/webp',
                'ico'   =>  'image/x-icon',
    ];

    /**
     * Add Accepted File Type for Resource
     * @return void
     */
    public static function addType(string $ext, string $mime): void
    {
        $ext = strtolower(trim($ext));
        $mime = strtolower(trim($mime));
        self::$acceptedTypes = array_merge(self::$acceptedTypes, [$ext => $mime]);
        return;
    }

    /**
     * Register App Resource
     * @return void
     */
    public static function registerAppResource(): void
    {
        Router::get(self::$app . '/{name:.+}', function($name) {
            // Trim leading/trailing slashes
            $name = str_replace('../', '', $name);
            $name = str_replace('./', '', $name);
            $name = trim($name, '/');

            // Supported Content Types
            $types = self::$acceptedTypes;

            // Get Asset File Path
            $file = APP_PATH . "/lf-assets/{$name}";
            if(!is_file($file)){
                http_response_code(404);
                return;
            }

            // Read File
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (array_key_exists(strtolower($ext), $types)) header("Content-Type: {$types[$ext]}");
            readfile($file);
            return;
        })->name('app-src');
    }

    /**
     * Register Template Resource
     * @return void
     */
    public static function registerTemplateResource(): void
    {
        Router::get(self::$template . '/{name:.+}', function($name) {
            // Trim leading/trailing slashes
            $name = str_replace('../', '', $name);
            $name = str_replace('./', '', $name);
            $name = trim($name, '/');

            // Supported Content Types
            $types = self::$acceptedTypes;

            // Get Asset File Path
            $file = APP_PATH."/lf-templates/{$name}";
            if(!is_file($file)){
                http_response_code(404);
                return;
            }

            // Read File
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (array_key_exists(strtolower($ext), $types)) header("Content-Type: {$types[$ext]}");
            readfile($file);
            return;
        })->name('template-src');
    }
}
