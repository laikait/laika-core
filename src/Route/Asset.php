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

namespace Laika\Core\Route;

use Laika\Core\App\Router;

class Asset
{
    /** @var string $path */
    private string $path = '/tpl-src';

    /** @var string $named */
    private string $named = 'asset.src';

    /** @var array $mimes */
    private array $mimes = [
        // Stylesheets & Scripts
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'ts'    => 'application/typescript',

        // Images
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'bmp'   => 'image/bmp',
        'avif'  => 'image/avif',

        // Fonts
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'eot'   => 'application/vnd.ms-fontobject',

        // Media
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'ogg'   => 'audio/ogg',
        'mp3'   => 'audio/mpeg',
        'wav'   => 'audio/wav',

        // Documents & Data
        'pdf'   => 'application/pdf',
        'json'  => 'application/json',
        'xml'   => 'application/xml',
        'txt'   => 'text/plain',
        'csv'   => 'text/csv',

        // Archives
        'zip'   => 'application/zip',
        'gz'    => 'application/gzip',
        'tar'   => 'application/x-tar',
    ];

    /**
     * Register Asset Routes
     * @return void
     */
    public function registerAssetRoute(): void
    {
        // Register App Resources
        Router::get("{$this->path}/{path:.+}", function($path) {
            // Trim leading/trailing slashes
            $path = trim($path, './\\');

            // Get Asset File Path
            $file = APP_PATH . "/lf-templates/{$path}";
            if(!is_file($file)){
                http_response_code(404);
                return;
            }

            // Read File
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mime = $this->mimes[$ext] ?? 'application/octet-stream';
            header("Content-Type: {$mime}");
            readfile($file);
            return;
        })->name($this->named);
    }

    public function __isset($prop): bool
    {
        return $this->{$prop};
    }

    public function __get($prop)
    {
        return $this->{$prop};
    }
}
