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

use Laika\Core\Helper\Directory;

class Url
{
    /**
     * Normalize Uri
     * @param string $uri Uri to Normalize
     * @return string
     */
    public static function normalize(string $uri): string
    {
        return '/' . \trim($uri, '/');
    }

    /**
     * @param string $key Falback key Normalize
     * @return string
     */
    public static function normalizeFallbackKey(?string $key): string
    {
        $key = self::normalize((string) $key);
        return ($key === "/") ? "/" : "{$key}/";
    }

    /**
     * Router Request Method
     * @return string
     */
    public static function method(): string
    {
        return \strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Match Request Url with Router Urls
     * @return array{route:?string,params:array}
     */
    public static function matchRequestRoute(?string $requestUrl): array
    {
        // Get Routes by Request Method
        $routes = Handler::getOnlyRoutes(self::method()) ?? null;
        if ($routes === null) {
            return [
                'route'     =>  null,
                'params'    =>  []
            ];
        }
        
        // Convert Route Placeholders to Regex Patterns
        foreach ($routes as $route) {
            $pattern = \preg_replace_callback(
                '#\{(\w+)(?::([^/]+))?\}#',
                function ($matches) {
                    $name = $matches[1];
                    $regex = isset($matches[2]) ? $matches[2] : '[^/]+';
                    return '(?P<' . $name . '>' . $regex . ')';
                },
                $route
            );
            // Add Regex Anchors
            $pattern = '#^' . $pattern . '$#';

            // Try to match
            if (\preg_match($pattern, $requestUrl, $matches)) {
                // Filter Only Named Captures
                return [
                    'route'     =>  $route,
                    'params'    =>  \array_filter($matches, fn($k) => !\is_int($k), ARRAY_FILTER_USE_KEY)
                ];
            }
        }

        return [
            'route'     =>  null,
            'params'    =>  []
        ];
    }

    /**
     * Load URL Routes From File
     * @return void
     */
    public static function LoadRoutes(): void
    {
        // Load Routes
        $routes = Directory::files(APP_PATH . '/lf-routes', 'php');
        \array_map(function ($route) { require_once $route; }, $routes);
        return;
    }
}
