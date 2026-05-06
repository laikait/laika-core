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

use Laika\Core\Exceptions\RouteException;

class Router
{
    /** @var string $group */
    private static string $group = '';

    /** @var string $lastGroupKey */
    private static string $lastGroupKey = '';

    /** @var array $groupStack */
    private static array $groupStack = [];

    /** @var array $groups */
    private static array $groups = [];

    /** @var array $routes */
    private static array $routes = [];

    /** @var array $onlyRoutes */
    private static array $onlyRoutes = [];

    /** @var string $lastMethod */
    private static string $lastMethod = '';

    /** @var string $lastUri */
    private static string $lastUri = '';

    /** @var array $namedRoutes */
    private static array $namedRoutes = [];

    /** @var array $fallbacks */
    private static array $fallbacks = [];

    /** @var string $lastFallbackKey */
    private static string $lastFallbackKey = '';

    /** @var array $globalMiddlewares */
    private static array $globalMiddlewares = [];

    /** @var array $globalAfterwares */
    private static array $globalAfterwares = [];

    /** @var array $groupMiddlewares */
    private static array $groupMiddlewares = [];

    /** @var array $groupAfterwares */
    private static array $groupAfterwares = [];

    /** @var array $afterwares */
    private static array $afterwares = [];

    /**
     * Register Route
     * @param string $method Request Method. Example: 'get'
     * @param string $uri Request Url. Example: '/user' or 'user'
     * @param callable|string|null $controller Controller
     * @param bool $isAsset Default is false
     * @return void
     */
    public static function register(string $method, string $uri, callable|string|null $controller, bool $isAsset = false): void
    {
        self::$lastMethod = strtoupper($method);
        self::$lastUri    = Url::normalize(self::$group . Url::normalize($uri));

        self::$onlyRoutes[self::$lastMethod][] = self::$lastUri;

        self::$routes[self::$lastMethod][self::$lastUri]['controller'] = $controller;

        self::$routes[self::$lastMethod][self::$lastUri]['middlewares'] = [
            'global' => $isAsset ? [] : self::$globalMiddlewares,
            'group'  => [],
            'route'  => []
        ];

        self::$routes[self::$lastMethod][self::$lastUri]['afterwares'] = [
            'global' => $isAsset ? [] : self::$globalAfterwares,
            'group'  => [],
            'route'  => []
        ];

        self::$afterwares = [];
        return;
    }

    /**
     * @param string $prefix Route Prefix. Example: '/admin' or 'admin'
     * @param callable $callback Register Routes Under Group
     */
    public static function registerGroup(string $prefix, callable $callback): void {
        self::$groupStack[] = [
            'group'            => self::$group,
            'groupMiddlewares' => self::$groupMiddlewares,
            'groupAfterwares'  => self::$groupAfterwares,
        ];

        self::$group            = Url::normalize(self::$group . Url::normalize($prefix));

        $currentGroupKey = self::$group; // capture BEFORE callback

        $gkey = trim(self::$group, '/');
        self::$groups = array_merge(self::$groups, [$gkey => $gkey]);

        $callback(); // nested groups may overwrite $lastGroupKey

        $previous               = array_pop(self::$groupStack);
        self::$group            = $previous['group'];
        self::$groupMiddlewares = $previous['groupMiddlewares'];
        self::$groupAfterwares  = $previous['groupAfterwares'];

        self::$lastGroupKey = $currentGroupKey; // restore to THIS group after callback
    }

    public static function getCurrentGroup(): string
    {
        return self::$group;
    }

    public static function getLastGroupKey(): string
    {
        return self::$lastGroupKey;
    }

    public static function appendGroupMiddlewares(string $groupKey, array $middlewares): void
    {
        foreach (self::$routes as &$uriRoutes) {
            foreach ($uriRoutes as $uri => &$route) {
                if (str_starts_with($uri, $groupKey . '/') || $uri === $groupKey) {
                    $route['middlewares']['group'] = array_merge(
                        $middlewares,
                        $route['middlewares']['group']
                    );
                }
            }
        }
    }

    public static function appendGroupAfterwares(string $groupKey, array $afterwares): void
    {
        foreach (self::$routes as &$uriRoutes) {
            foreach ($uriRoutes as $uri => &$route) {
                if (str_starts_with($uri, $groupKey . '/') || $uri === $groupKey) {
                    $route['afterwares']['group'] = array_merge(
                        $afterwares,
                        $route['afterwares']['group']
                    );
                }
            }
        }
    }

    /**
     * Register Fallback
     * @param callable|string|null $callable Controller
     * @param ?string $group Fallback scope. Default '/' for global.
     * @return void
     */
    public static function registerFallback(?string $group = null, callable|string|null $callable = null): void
    {
        $key = Url::normalizeFallbackKey($group);
        self::$fallbacks[$key] = [
            'controller'  => $callable,
            'middlewares' => [],
            'afterwares'  => [],
        ];
        self::$lastFallbackKey = $key;
    }

    public static function getLastFallbackKey(): string
    {
        return self::$lastFallbackKey;
    }

    public static function appendFallbackMiddlewares(string $key, array $middlewares): void
    {
        self::$fallbacks[$key]['middlewares'] = array_merge(
            self::$fallbacks[$key]['middlewares'] ?? [],
            $middlewares
        );
    }

    public static function appendFallbackAfterwares(string $key, array $afterwares): void
    {
        self::$fallbacks[$key]['afterwares'] = array_merge(
            self::$fallbacks[$key]['afterwares'] ?? [],
            $afterwares
        );
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
     * @param string|array $afterwares Afterwares to Register
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
     * Route-level Middleware Register
     * @param string|array $middlewares Middleware to Register
     * @return void
     */
    public static function middlewareRegister(string|array $middlewares): void
    {
        self::$routes[self::$lastMethod][self::$lastUri]['middlewares']['route'] = array_merge(
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
        self::$routes[self::$lastMethod][self::$lastUri]['afterwares']['route'] = array_merge(
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
        $method = strtoupper($method);
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
     * @throws RouteException
     * @return void
     */
    public static function name(string $name): void
    {
        if (isset(self::$namedRoutes[$name])) {
            throw new RouteException("Name Route [{$name}] Already Exists!");
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
     * @throws RouteException
     */
    public static function namedUrl(string $name, array $params = []): string
    {
        $namedRoutes = self::getNamedRoutes();

        if (empty($namedRoutes[$name])) {
            throw new RouteException("Invalid Named Route: [{$name}]");
        }

        $uri = $namedRoutes[$name];

        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '(:[^}]*)?\}/', trim((string) $value, '/'), $uri);
        }

        $uri = preg_replace('/\{[^}]+\}/', '', $uri);

        return Url::normalize($uri);
    }
}
