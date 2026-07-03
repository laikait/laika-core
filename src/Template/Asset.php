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

use Laika\Service\{Response, Url, CSRF};

class Asset
{
    /** @var array $styles */
    private static array $styles  = [];

    /** @var array $scripts */
    private static array $scripts = [];

    /**
     * Add Style
     * @param string $handle
     * @param string $src
     * @param string $version
     * @param string $media
     * @return void
     */
    public static function addStyle(string $handle, string $src, string $version = '1.0.0', string $media = 'all'): void
    {
        if (isset(static::$styles[$handle])) return;
        $src = parse_url($src, PHP_URL_HOST) ? $src : Url::base() . trim($src, '/');
        static::$styles[$handle] = compact('src', 'version', 'media');
    }

    /**
     * Add Script
     * @param string $handle
     * @param string $src
     * @param string $version
     * @param bool $defer
     * @return void
     */
    public static function addScript(string $handle, string $src, string $version = '1.0.0', bool $defer = false): void
    {
        if (isset(static::$scripts[$handle])) return;
        $src = parse_url($src, PHP_URL_HOST) ? $src : Url::base() . trim($src, '/');
        static::$scripts[$handle] = compact('src', 'version', 'defer');
    }

    /**
     * Print Styles
     * @return void
     */
    public static function printStyles(): void
    {
        foreach (static::$styles as $handle => $s) {
            $ver = htmlspecialchars($s['version']);
            $src = htmlspecialchars($s['src']);
            $med = htmlspecialchars($s['media']);
            $comment = ucfirst($handle);
            echo "<!-- {$comment} CSS -->\n<link id=\"{$handle}-css\" rel=\"stylesheet\" href=\"{$src}?v={$ver}\" media=\"{$med}\">\n";
        }
    }

    /**
     * Print Scripts
     * @return void
     */
    public static function printScripts(): void
    {
        foreach (static::$scripts as $handle => $s) {
            $ver   = htmlspecialchars($s['version']);
            $src   = htmlspecialchars($s['src']);
            $defer = $s['defer'] ? ' defer' : '';
            $comment = ucfirst($handle);
            echo "<!-- {$comment} JS -->\n<script id=\"{$handle}-js\" src=\"{$src}?v={$ver}\"{$defer}></script>\n";
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
