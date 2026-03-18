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

namespace Laika\Core\Route;

use RuntimeException;

class Handler
{
    /**
     * Current active group prefix
     * @var string $group
     */
    private static string $group = '';

    /**
     * FIX 3: Stack of parent group prefixes for nested group support.
     * Each Router::group() call pushes the current prefix onto the stack
     * and pops it back when the callback finishes, allowing unlimited nesting.
     * @var array $groupStack
     */
    private static array $groupStack = [];

    /**
     * @var array $groups
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
     * FIX 4: Initialize with empty string to prevent fatal errors if
     * Router::middleware() or Router::afterware() is called before any route is registered.
     * @var string $lastMethod
     */
    private static string $lastMethod = '';

    /**
     * FIX 4: Same as $lastMethod — initialized to prevent undefined property access.
     * @var string $lastUri
     */
    private static string $lastUri = '';

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
     * @param string|array $middlewares Register Middleware for Route
     */
    public static function register(
        string $method,
        string $uri,
        callable|string|array|null|object $controller,
        string|array $middlewares = []
    ): void
    {
        self::$lastMethod = \strtoupper($method);
        self::$lastUri    = Url::normalize(self::$group . Url::normalize($uri));

        self::$onlyRoutes[self::$lastMethod][] = self::$lastUri;

        self::$routes[self::$lastMethod][self::$lastUri]['controller'] = $controller;

        self::$routes[self::$lastMethod][self::$lastUri]['middlewares'] = [
            'global' => self::$globalMiddlewares,
            'group'  => self::$groupMiddlewares,
            'route'  => (array) $middlewares
        ];

        self::$routes[self::$lastMethod][self::$lastUri]['afterwares'] = [
            'global' => self::$globalAfterwares,
            'group'  => self::$groupAfterwares,
            'route'  => (array) self::$afterwares
        ];

        self::$afterwares = [];
        return;
    }

    /**
     * Register Group of Routes
     *
     * FIX 3: Uses a group stack so nested groups correctly accumulate prefixes.
     *
     * Before (broken):
     *   Router::group('/admin', function() {
     *       Router::group('/users', function() {
     *           Router::get('/list', ...); // was: /users/list  (lost /admin)
     *       });
     *       Router::get('/dashboard', ...); // was: /dashboard (lost /admin)
     *   });
     *
     * After (correct):
     *   /admin/users/list
     *   /admin/dashboard
     *
     * @param string $prefix Route Prefix. Example: '/admin' or 'admin'
     * @param callable $callback Register Routes Under Group
     * @param string|array $middlewares Middlewares for all routes in this group
     * @param string|array $afterwares Afterwares for all routes in this group
     */
    public static function registerGroup(
        string $prefix,
        callable $callback,
        string|array $middlewares,
        string|array $afterwares
    ): void
    {
        // Snapshot current state onto the stack before entering nested group
        self::$groupStack[] = [
            'group'             => self::$group,
            'groupMiddlewares'  => self::$groupMiddlewares,
            'groupAfterwares'   => self::$groupAfterwares,
        ];

        // Accumulate prefix on top of any existing group prefix
        self::$group            = Url::normalize(self::$group . Url::normalize($prefix));
        self::$groupMiddlewares = (array) $middlewares;
        self::$groupAfterwares  = (array) $afterwares;

        $gkey = \trim(self::$group, '/');
        self::$groups = \array_merge(self::$groups, [$gkey => $gkey]);

        // Register all routes inside this group
        $callback();

        // Restore previous state from stack
        $previous               = \array_pop(self::$groupStack);
        self::$group            = $previous['group'];
        self::$groupMiddlewares = $previous['groupMiddlewares'];
        self::$groupAfterwares  = $previous['groupAfterwares'];

        return;
    }

    /**
     * Register Fallback
     * @param callable|string|array|null|object $callable Controller
     * @param ?string $group Fallback scope. Default '/' for global.
     */
    public static function registerFallback(?string $group = null, callable|string|array|null|object $callable = null, string|array $middlewares = []): void
    {
        $group = Url::normalizeFallbackKey($group);
        self::$fallbacks[$group] = [
            'controller'  => $callable,
            'middlewares' => (array) $middlewares,
        ];
        return;
    }

    /**
     * Global Middleware Register
     * @param string|array $middlewares Middlewares to Register
     * @return void
     */
    public static function globalMiddlewareRegister(string|array $middlewares): void
    {
        self::$globalMiddlewares = \array_merge(
            self::$globalMiddlewares,
            (array) $middlewares
        );
        return;
    }

    /**
     * Global Afterware Register
     * @param string|array $afterwares Afterwares to Register
     * @return void
     */
    public static function globalAfterwareRegister(string|array $afterwares): void
    {
        self::$globalAfterwares = \array_merge(
            self::$globalAfterwares,
            (array) $afterwares
        );
        return;
    }

    /**
     * Route-level Middleware Register
     * @param string|array $middlewares Middleware to Register
     * @return void
     */
    public static function middlewareRegister(string|array $middlewares): void
    {
        self::$routes[self::$lastMethod][self::$lastUri]['middlewares']['route'] = \array_merge(
            self::$routes[self::$lastMethod][self::$lastUri]['middlewares']['route'] ?? [],
            (array) $middlewares
        );
        return;
    }

    /**
     * Route-level Afterware Register
     * @param string|array $afterwares Afterware to Register
     * @return void
     */
    public static function afterwareRegister(string|array $afterwares): void
    {
        self::$routes[self::$lastMethod][self::$lastUri]['afterwares']['route'] = \array_merge(
            self::$routes[self::$lastMethod][self::$lastUri]['afterwares']['route'] ?? [],
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
        if (empty($method)) {
            return self::$routes;
        }
        $method = \strtoupper($method);
        return self::$routes[$method] ?? [];
    }

    /**
     * Get All Registered Route URLs Only
     * @param ?string $method Routes for Request Method
     * @return array
     */
    public static function getOnlyRoutes(?string $method = null): array
    {
        if ($method == null) {
            return self::$onlyRoutes;
        }
        $method = \strtoupper($method);
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
            throw new RuntimeException("Name Route [{$name}] Already Exists!");
        }
        self::$routes[self::$lastMethod][self::$lastUri]['name'] = $name;
        self::$namedRoutes[$name] = self::$lastUri;
        return;
    }

    /**
     * Get Named Route URL
     * @param string $name Route Name. Example: 'page' or 'post.id'
     * @param array $params Route Named Parameters. Example: ['id'=>34, 'action'=>'delete']
     * @return string
     */
    public static function namedUrl(string $name, array $params = []): string
    {
        $namedRoutes = self::getNamedRoutes();
        $uri = $namedRoutes[$name] ?? \trim($name, '/');

        foreach ($params as $key => $value) {
            $uri = \preg_replace('/\{' . $key . '(:[^}]*)?\}/', trim((string) $value, '/'), $uri);
        }

        $uri = \preg_replace('/\{[^}]+\}/', '', $uri);

        return Url::normalize($uri);
    }
}
