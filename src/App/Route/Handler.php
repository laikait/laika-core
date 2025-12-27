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

use RuntimeException;

class Handler
{
    /**
     * @var string $group
     */
    private static string $group = '';

    /**
     * @var string $group
     */
    private static array $groups = [];

    /**
     * @var array $routes
     */
    private static array $routes = [];

    /**
     * @var array $onlyRoutes
     */
    private static array $onlyRoutes = [];

    /**
     * @var string $lastMethod
     */
    private static string $lastMethod;

    /**
     * @var string $lastUri
     */
    private static string $lastUri;

    /**
     * @var array $namedRoutes
     */
    private static array $namedRoutes = [];

    /**
     * @var array $fallbacks
     */
    private static array $fallbacks = [];

    /**
     * @var array $globalMiddlewares
     */
    private static array $globalMiddlewares = [];

    /**
     * @var array $globalAfterwares
     */
    private static array $globalAfterwares = [];

    /**
     * @var array $groupMiddlewares
     */
    private static array $groupMiddlewares = [];

    /**
     * @var array $groupAfterwares
     */
    private static array $groupAfterwares = [];

    /**
     * @var array $afterwares
     */
    private static array $afterwares = [];

    /**
     * Register Route
     * @param string $method Request Method. Example: 'get'
     * @param string $uri Request Url. Example: '/user' or 'user'
     * @param callable|string|array|null|object $controller Controller
     * @param string|array $middlewares Register Miidleware for Route
     */
    public static function register(
        string $method,
        string $uri,
        callable|string|array|null|object $controller,
        string|array $middlewares = []
    ): void
    {
        // Capitalize Method
        self::$lastMethod = strtoupper($method);
        // Create Uri
        self::$lastUri = Url::normalize(self::$group . Url::normalize($uri));
        self::$onlyRoutes[self::$lastMethod][] = self::$lastUri;
        // Set Route
        self::$routes[self::$lastMethod][self::$lastUri]['controller'] = $controller;
        // Set Middlewares
        self::$routes[self::$lastMethod][self::$lastUri]['middlewares'] = [
            'global'    =>  self::$globalMiddlewares,
            'group'     =>  self::$groupMiddlewares,
            'route'     =>  (array) $middlewares
        ];
        // Set Afterwares
        self::$routes[self::$lastMethod][self::$lastUri]['afterwares'] = [
            'global'    =>  self::$globalAfterwares,
            'group'     =>  self::$groupAfterwares,
            'route'     =>  (array) self::$afterwares
        ];
        // Reset Afterwares
        self::$afterwares = [];
        return;
    }

    /**
     * Register Group of Route
     * @param string $prefix Route Prefix. Example: '/admin' or 'admin'
     * @param callable $callback Register Router Under Group
     * @param string|array $middlewares Register Miidleware for Route
     * @param string|array $afterwares Register Afterware for Route
     */
    public static function registerGroup(
        string $prefix,
        callable $callback,
        string|array $middlewares,
        string|array $afterwares
    ): void
    {
        // push normalized prefix fragment onto stack (ensures leading slash, no trailing)
        self::$group = Url::normalize($prefix);
        $gkey = trim($prefix, '/');
        self::$groups = array_merge(self::$groups, [$gkey => $gkey]);

        self::$groupMiddlewares = (array) $middlewares;
        self::$groupAfterwares = (array) $afterwares;

        // call user callback (allows Http::get() calls inside)
        $callback();

        // Reset Group, Middleware & Afterwares
        self::$group = '';
        self::$groupMiddlewares = [];
        self::$groupAfterwares = [];
        return;
    }

    /**
     * Register Fallback
     * @param callable|string|array|null|object $callable Controller
     * @param ?string $group Register Fallback for Group or Global. Default is null for Global
     */
    public static function registerFallback(callable|string|array|null|object $callable = null, ?string $group = null): void
    {
        $group = Url::normalizeFallbackKey($group);
        self::$fallbacks[$group] = $callable;
        return;
    }

    /**
     * Global Middleware Register
     * @param string|array $middlewares Middlewares to Register
     * @return void
     */
    public static function globalMiddlewareRegister(string|array $middlewares): void
    {
        self::$globalMiddlewares = array_merge(
            self::$globalMiddlewares,
            (array) $middlewares
        );
        return;
    }

    /**
     * Global Afterware Register
     * @param string|array $afterwares Afterware to Register
     * @return void
     */
    public static function globalAfterwareRegister(string|array $afterwares): void
    {
        self::$globalAfterwares = array_merge(
            self::$globalAfterwares,
            (array) $afterwares
        );
        return;
    }

    /**
     * Middleware Register
     * @param string|array $middleware Middleware to Register
     * @return void
     */
    public static function middlewareRegister(string|array $middlewares): void
    {
        self::$routes[self::$lastMethod][self::$lastUri]['middlewares']['route'] = array_merge(
            self::$routes[self::$lastMethod][self::$lastUri]['middlewares']['route'],
            (array) $middlewares
        );
        return;
    }

    /**
     * Afterware Register
     * @param string|array $afterwares Afterware to Register
     * @return void
     */
    public static function afterwareRegister(string|array $afterwares): void
    {
        self::$routes[self::$lastMethod][self::$lastUri]['afterwares']['route'] = array_merge(
            self::$routes[self::$lastMethod][self::$lastUri]['afterwares']['route'],
            (array) $afterwares
        );
        return;
    }

    /**
     * Get All Registered Routes
     * @param ?string $method Routes for Request Method. Example: 'get' or 'post'
     * @return array
     */
    public static function getRoutes(?string $method = null): array
    {
        if ($method == null) {
            return self::$routes;
        }
        $method = strtoupper($method);
        return self::$routes[$method] ?? [];
    }

    /**
     * Get All Registered Routes Urls Only
     * @param ?string $method Routes Url for Request Method. Example: 'get' or 'post'
     * @return array
     */
    public static function getOnlyRoutes(?string $method = null): array
    {
        if ($method == null) {
            return self::$onlyRoutes;
        }
        $method = strtoupper($method);
        return self::$onlyRoutes[$method] ?? [];
    }

    /**
     * Get All Registered Named Routes
     * @return array
     */
    public static function getNamedRoutes(): array
    {
        return self::$namedRoutes;
    }
    
    /**
     * Get All Groups
     * @return array
     */
    public static function getGroups(): array
    {
        return self::$groups;
    }

    /**
     * Get All Registered Fallbacks
     * @return array
     */
    public static function getFallbacks(): array
    {
        return self::$fallbacks;
    }

    /**
     * Set Named Route
     * @param string $name Route Name. Example: 'page' or 'post.id'
     * @return void
     */
    public static function name(string $name): void
    {
        if (isset(self::$namedRoutes[$name])) {
            throw new RuntimeException("Name Route '{$name}' Already Exists!");
        }
        self::$routes[self::$lastMethod][self::$lastUri]['name'] = $name;
        self::$namedRoutes[$name] = self::$lastUri;
        return;
    }

    /**
     * Get Named Route Url
     * @param string $name Route Name. Example: 'page' or 'post.id'
     * @param array $params Route Named Parameters. Example: ['id'=>34, 'action'=>'delete']
     * @return string
     */
    public static function namedUrl(string $name, array $params = []): string
    {
        $namedRoutes = self::getNamedRoutes();
        $uri = $namedRoutes[$name] ?? trim($name, '/');
        // Replace {param} placeholders
        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '(:[^}]*)?\}/', (string) trim($value, '/'), $uri);
        }
        // Remove unreplaced params
        $uri = preg_replace('/\{[^}]+\}/', '', $uri);

        return Url::normalize($uri);
    }
}
