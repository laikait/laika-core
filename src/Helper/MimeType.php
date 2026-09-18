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

namespace Laika\Core\Helper;

final class MimeType
{
    /** @var array Mime Types */
    private static array $types = [
        // Text
        'html'  => 'text/html',
        'htm'   => 'text/html',
        'css'   => 'text/css',
        'csv'   => 'text/csv',
        'txt'   => 'text/plain',
        'xml'   => 'text/xml',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'map'   => 'application/json',
        'pdf'   => 'application/pdf',
        'zip'   => 'application/zip',
        'gz'    => 'application/gzip',
        'tar'   => 'application/x-tar',
        'rar'   => 'application/x-rar-compressed',
        '7z'    => 'application/x-7z-compressed',

        // Images
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'bmp'   => 'image/bmp',
        'tiff'  => 'image/tiff',
        'tif'   => 'image/tiff',

        // Audio
        'mp3'   => 'audio/mpeg',
        'wav'   => 'audio/wav',
        'ogg'   => 'audio/ogg',
        'aac'   => 'audio/aac',
        'flac'  => 'audio/flac',

        // Video
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'avi'   => 'video/x-msvideo',
        'mov'   => 'video/quicktime',
        'mkv'   => 'video/x-matroska',

        // Fonts
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',

        // Documents
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /**
     * @var string[] Extensions whose type needs an explicit charset.
     * Without one the browser decodes the file with its own locale default,
     * so a UTF-8 stylesheet or caption file renders as mojibake. The charset
     * is not baked into the table above because fromFile(), fromContent() and
     * File::download() hand their result to callers that compare exact types.
     */
    private const CHARSET_TYPES = ['html', 'htm', 'css', 'csv', 'txt', 'xml', 'js', 'json', 'map', 'svg'];

    ##########################################################################
    /*============================ EXTERNAL API ============================*/
    ##########################################################################
    /**
     * Get Mime Type From Extension
     * @param string $extension Example: css, html, jpg
     * @param bool $withCharset Append "; charset=utf-8" to a text type
     * @return string
     */
    public static function fromExtension(string $extension, bool $withCharset = false): string
    {
        $extension = strtolower($extension);
        $type = static::$types[$extension] ?? 'application/octet-stream';

        if ($withCharset && in_array($extension, static::CHARSET_TYPES, true)) {
            $type .= '; charset=utf-8';
        }

        return $type;
    }

    /**
     * Get Mime Type From File
     * @param string $filename Example: style.css, index.html, image.jpg
     * @param bool $withCharset Append "; charset=utf-8" to a text type
     * @return string
     */
    public static function fromFile(string $filename, bool $withCharset = false): string
    {
        return static::fromExtension(pathinfo($filename, PATHINFO_EXTENSION), $withCharset);
    }

    /**
     * Get Mime Type From Content
     * @param string $content
     * @return string
     */
    public static function fromContent(string $content): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($content) ?: 'application/octet-stream';
    }

    /**
     * Get All Supported Content Types
     * @return array
     */
    public static function all(): array
    {
        return static::$types;
    }

    /**
     * Register Extension & Mime Type
     * @param string $content
     * @return string
     */
    public static function register(string $extension, string $mimeType): void
    {
        static::$types[strtolower($extension)] = strtolower($mimeType);
    }
}
