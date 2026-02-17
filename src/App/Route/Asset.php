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

namespace Laika\Core\App\Route;

use Laika\Core\App\Router;

class Asset
{
    /**
     * @var ?Asset $asset
     */
    protected static ?Asset $asset = null;

    /**
     * @var string $appSrc
     */
    private string $app = '/app-src';

    /**
     * @var string $templateSrc
     */
    private string $template = '/tpl-src';

    /**
     * Accepted File Types
     * @var array $acceptedTypes
     */
    private array $acceptedTypes = [
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

    public static function instance(): self
    {
        self::$asset ??= new self();

        return self::$asset;
    }

    /**
     * Add Accepted File Type for Resource
     * @return void
     */
    public function addType(string $ext, string $mime): void
    {
        $ext = \strtolower(trim($ext));
        $mime = \strtolower(trim($mime));
        self::instance()->acceptedTypes = \array_merge(self::instance()->acceptedTypes, [$ext => $mime]);
        return;
    }

    /**
     * Register Asset Routes
     * @return void
     */
    public function registerAssetRoute(): void
    {
        // Register App Resources
        Router::get(self::instance()->app . '/{name:.+}', function($name) {
            // Trim leading/trailing slashes
            $name = \str_replace('../', '', $name);
            $name = \str_replace('./', '', $name);
            $name = \trim($name, '/');

            // Supported Content Types
            $types = self::instance()->acceptedTypes;

            // Get Asset File Path
            $file = APP_PATH . "/lf-assets/{$name}";
            if(!\is_file($file)){
                \http_response_code(404);
                return;
            }

            // Read File
            $ext = \pathinfo($file, PATHINFO_EXTENSION);
            if (\array_key_exists(\strtolower($ext), $types)) {
                \header("Content-Type: {$types[$ext]}");
            }
            \readfile($file);
            return;
        })->name('app.src');

        // Register Template Resources
        Router::get(self::instance()->template . '/{name:.+}', function($name) {
            // Trim leading/trailing slashes
            $name = \str_replace('../', '', $name);
            $name = \str_replace('./', '', $name);
            $name = \trim($name, '/');

            // Supported Content Types
            $types = self::instance()->acceptedTypes;

            // Get Asset File Path
            $file = APP_PATH."/lf-templates/{$name}";
            if(!\is_file($file)){
                \http_response_code(404);
                return;
            }

            // Read File
            $ext = \pathinfo($file, PATHINFO_EXTENSION);
            if (\array_key_exists(strtolower($ext), $types)) {
                \header("Content-Type: {$types[$ext]}");
            }
            \readfile($file);
            return;
        })->name('tpl.src');
    }

    public function __isset($prop): bool
    {
        return self::instance()->$prop;
    }

    public function __get($prop)
    {
        return self::instance()->$prop;
    }
}
