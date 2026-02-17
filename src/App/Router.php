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

namespace Laika\Core\App;

class Router
{
    /**
     * Router Get Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|array|null|object $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function get(string $uri, callable|string|array|null|object $controller = null, string|array $middlewares = []): self
    {
        Route\Handler::register('get', $uri, $controller, $middlewares);
        return new self();
    }

    /**
     * Router Post Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|array|null|object $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function post(string $uri, callable|string|array|null|object $controller = null, string|array $middlewares = []): self
    {
        Route\Handler::register('post', $uri, $controller, $middlewares);
        return new self();
    }

    /**
     * Router Put Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|array|null|object $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function put(string $uri, callable|string|array|null|object $controller = null, string|array $middlewares = []): self
    {
        Route\Handler::register('put', $uri, $controller, $middlewares);
        return new self();
    }

    /**
     * Router Patch Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|array|null|object $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function patch(string $uri, callable|string|array|null|object $controller = null, string|array $middlewares = []): self
    {
        Route\Handler::register('patch', $uri, $controller, $middlewares);
        return new self();
    }

    /**
     * Router Options Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|array|null|object $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function options(string $uri, callable|string|array|null|object $controller = null, string|array $middlewares = []): self
    {
        Route\Handler::register('options', $uri, $controller, $middlewares);
        return new self();
    }

    /**
     * Router Group Register
     * @param string $prefix Register Router Group Prefix. Example: '/admin' or 'admin'
     * @param callable $handler Handler for Group Router Url(s).
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @param string|array $afterwares Register Router Url Afterware(s). Example: ['Sample/Namespace/Afterware'] or 'Sample/Namespace/Afterware'. Example Using Parameters: ['Sample/Namespace/Afterware|type=admin'] or 'Sample/Namespace/Afterware|type=admin'
     * @return self
     */
    public static function group(string $prefix, callable $handler, string|array $middlewares = [], string|array $afterwares = []): void
    {
        Route\Handler::registerGroup($prefix, $handler, $middlewares, $afterwares);
        return;
    }

    /**
     * Register Url Middleware
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function middleware(string|array $middlewares): self
    {
        Route\Handler::middlewareRegister($middlewares);
        return new self();
    }

    /**
     * Register Url Middleware
     * @param string|array $afterware Register Router Url Afterware(s). Example: ['Sample/Namespace/Afterware'] or 'Sample/Namespace/Afterware'. Example Using Parameters: ['Sample/Namespace/Afterware|type=admin'] or 'Sample/Namespace/Afterware|type=admin'
     * @return self
     */
    public static function afterware(string|array $afterware): self
    {
        Route\Handler::afterwareRegister($afterware);
        return new self();
    }

    /**
     * Register Global Middleware
     * @param string|array $middlewares Register Router Url Middleware(s). Example: ['Sample/Namespace/Middleware'] or 'Sample/Namespace/Middleware'. Example Using Parameters: ['Sample/Namespace/Middleware|type=admin'] or 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function globalMiddleware(string|array $middlewares): void
    {
        Route\Handler::globalMiddlewareRegister($middlewares);
        return;
    }

    /**
     * Register Global Afterware
     * @param string|array $afterwares Register Router Url Afterware(s). Example: ['Sample/Namespace/Afterware'] or 'Sample/Namespace/Afterware'. Example Using Parameters: ['Sample/Namespace/Afterware|type=admin'] or 'Sample/Namespace/Afterware|type=admin'
     * @return self
     */
    public static function globalAfterware(string|array $afterwares): void
    {
        Route\Handler::globalAfterwareRegister($afterwares);
        return;
    }

    /**
     * Dispatch Router & Run Application
     * @param ?string $requestUrl Server Request Uri
     * @return void
     */
    public static function dispatch(?string $requestUrl = null): void
    {
        Route\Dispatcher::dispatch($requestUrl);
    }

    /**
     * Register Fallback
     * @param callable|string|array|null|object $callable Register Router Fallnack Controller.
     * Example: 'Sample/Namespace/CallableClass@index' or ['Sample/Namespace/CallableClass','index'] or new Sample/Namespace/CallableClass() (it will call 'index' method)
     * @return void
     */
    public static function fallback(callable|string|array|null|object $callable = null, string $group = '/'): void
    {
        Route\Handler::registerFallback($callable, $group);
    }

    /**
     * Register Named Router
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        Route\Handler::name($name);
        return new self();
    }

    /**
     * Get Nameded Router Url
     * @param string $name
     * @return string
     */
    public static function url(string $name, array $param = []): string
    {
        return Route\Handler::namedUrl($name, $param);
    }
}
