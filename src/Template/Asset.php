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

namespace Laika\Core\Template;

use Laika\Service\Url;
use Laika\Service\CSRF;
use Laika\Service\Response;

class Asset
{
    /** @var array $styles */
    private static array $styles  = [];

    /** @var array $scripts */
    private static array $scripts = [];

    /** @var string Version used when a file cannot be found on disk */
    private const FALLBACK_VERSION = '1.0.0';

    /**
     * Add Style
     * @param string $handle
     * @param string $src
     * @param string $version Empty derives it from the file, see version()
     * @param string $media
     * @return void
     */
    public static function addStyle(string $handle, string $src, string $version = '', string $media = 'all'): void
    {
        if (isset(static::$styles[$handle])) {
            return;
        }
        // Derived from the source as given, before the base URL is prepended
        $version = static::resolveVersion($src, $version);
        $src = parse_url($src, PHP_URL_HOST) ? $src : Url::base() . trim($src, '/');
        static::$styles[$handle] = compact('src', 'version', 'media');
    }

    /**
     * Add Script
     * @param string $handle
     * @param string $src
     * @param string $version Empty derives it from the file, see version()
     * @param bool $defer
     * @return void
     */
    public static function addScript(string $handle, string $src, string $version = '', bool $defer = false): void
    {
        if (isset(static::$scripts[$handle])) {
            return;
        }
        $version = static::resolveVersion($src, $version);
        $src = parse_url($src, PHP_URL_HOST) ? $src : Url::base() . trim($src, '/');
        static::$scripts[$handle] = compact('src', 'version', 'defer');
    }

    /**
     * Version Tag for a Local File
     *
     * The hex modification time, the same value the ETag is built from, so a
     * file that changes changes the URL that points at it. That matters
     * because a URL carrying "?v=" is served immutable for a year: a version
     * that does not track the file pins the stale copy for that long.
     *
     * Returns an empty string for anything not on disk -- an external URL, or
     * a path that does not resolve -- and the caller then leaves "?v=" off
     * rather than inventing a version.
     *
     * @param string $path Source as the caller wrote it, relative to APP_PATH
     * @return string Hex mtime, or '' when there is no such file
     */
    public static function version(string $path): string
    {
        if (parse_url($path, PHP_URL_HOST)) {
            return '';
        }

        // The caller may have written a query of their own
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        if ($path === '') {
            return '';
        }

        $file = APP_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');

        // is_file() first: asset() is called from templates, where a warning
        // would be printed into the markup
        return is_file($file) ? dechex((int) filemtime($file)) : '';
    }

    /**
     * Append a Version to a URL
     *
     * Separator picked from what the URL already carries; concatenating "?v="
     * unconditionally turned an external "https://cdn/x.css?a=1" into
     * "...?a=1?v=1.0.0".
     *
     * @param string $url URL to tag
     * @param string $version Empty leaves the URL untouched
     * @return string
     */
    public static function appendVersion(string $url, string $version): string
    {
        if ($version === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    }

    /**
     * Version to Store for a Source
     * @param string $src Source as the caller wrote it
     * @param string $given Version the caller passed, if any
     * @return string
     */
    private static function resolveVersion(string $src, string $given): string
    {
        if ($given !== '') {
            return $given;
        }

        // An enqueued tag has always carried a version, so keep emitting one
        // even when the file is missing rather than changing the tag's shape
        return static::version($src) ?: static::FALLBACK_VERSION;
    }

    /**
     * Print Styles
     * @return void
     */
    public static function printStyles(): void
    {
        foreach (static::$styles as $handle => $s) {
            // Built first, escaped once: an "&" separator has to leave here as
            // "&amp;" to be a valid attribute
            $href = htmlspecialchars(static::appendVersion($s['src'], $s['version']));
            $med = htmlspecialchars($s['media']);
            $comment = ucfirst($handle);
            echo "<!-- {$comment} CSS -->\n<link id=\"{$handle}-css\" rel=\"stylesheet\" href=\"{$href}\" media=\"{$med}\">\n";
        }
    }

    /**
     * Print Scripts
     * @return void
     */
    public static function printScripts(): void
    {
        foreach (static::$scripts as $handle => $s) {
            $src   = htmlspecialchars(static::appendVersion($s['src'], $s['version']));
            $defer = $s['defer'] ? ' defer' : '';
            $comment = ucfirst($handle);
            echo "<!-- {$comment} JS -->\n<script id=\"{$handle}-js\" src=\"{$src}\"{$defer}></script>\n";
        }
    }

    /**
     * Header Default Scripts
     * @return void
     */
    public static function headerScripts(): void
    {
        $str = "<!-- System Default Scripts -->\n<script>\n";
        $vars = [
            'TOKEN' => htmlspecialchars(CSRF::generate()),
            'APP_URI' => rtrim(Url::base(), '/'),
        ];

        foreach ($vars as $k => $v) {
            $str .= "const {$k} = \"{$v}\";\n";
        }

        $str .= "</script>\n";

        echo $str;
    }
}
