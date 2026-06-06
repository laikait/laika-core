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

use Laika\Core\Route\Router;
use Laika\Core\Route\Dispatcher;

class Http
{
    /** @var string $context */
    private string $context    = 'route';

    /** @var string $contextKey */
    private string $contextKey = '';

    /**
     * Router Get Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function get(string $uri, callable|string|null $controller = null): self
    {
        Router::register('get', $uri, $controller);
        return new self();
    }

    /**
     * Router Post Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function post(string $uri, callable|string|null $controller = null): self
    {
        Router::register('post', $uri, $controller);
        return new self();
    }

    /**
     * Router Put Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function put(string $uri, callable|string|null $controller = null): self
    {
        Router::register('put', $uri, $controller);
        return new self();
    }

    /**
     * Router Patch Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function patch(string $uri, callable|string|null $controller = null): self
    {
        Router::register('patch', $uri, $controller);
        return new self();
    }

    /**
     * Router Delete Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function delete(string $uri, callable|string|null $controller = null): self
    {
        Router::register('delete', $uri, $controller);
        return new self();
    }

    /**
     * Router Options Request
     * @param string $uri Register Router Url. Example: '/home'
     * @param callable|string|null $controller Register Router Controller.
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function options(string $uri, callable|string|null $controller = null): self
    {
        Router::register('options', $uri, $controller);
        return new self();
    }

    /**
     * Router Group Register
     * @param string $prefix Register Router Group Prefix. Example: '/admin' or 'admin'
     * @param callable $handler Handler for Group Router Url(s).
     * Example: 'Sample/Namespace/Class@index' or ['Sample/Namespace/Class','index'] or new Sample/Namespace/Class() (it will call index method)
     * @return self
     */
    public static function group(string $prefix, callable $handler): self
    {
        Router::registerGroup($prefix, $handler);
        $instance             = new self();
        $instance->context    = 'group';
        $instance->contextKey = Router::getLastGroupKey();
        return $instance;
    }

    /**
     * Register Url Middleware
     * @param string|array $middlewares Register Router Url Middleware(s).
     * Example: 'Sample/Namespace/Middleware'. Example Using Parameters: 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public function middleware(string|array $middlewares): self
    {
        match ($this->context) {
            'group'    => Router::appendGroupMiddlewares($this->contextKey, (array) $middlewares),
            'fallback' => Router::appendFallbackMiddlewares($this->contextKey, (array) $middlewares),
            default    => Router::middlewareRegister($middlewares),
        };
        return $this;
    }

    /**
     * Register Url Middleware
     * @param string|array $afterwares Register Router Url Afterware(s).
     * Example: 'Sample/Namespace/Afterware'. Example Using Parameters: 'Sample/Namespace/Afterware|type=admin'
     * @return self
     */
    public function afterware(string|array $afterwares): self
    {
        match ($this->context) {
            'group'    => Router::appendGroupAfterwares($this->contextKey, (array) $afterwares),
            'fallback' => Router::appendFallbackAfterwares($this->contextKey, (array) $afterwares),
            default    => Router::afterwareRegister($afterwares),
        };
        return $this;
    }

    /**
     * Register Global Middleware
     * @param string|array $middlewares Register Router Url Middleware(s)
     * Example: 'Sample/Namespace/Middleware'. Example Using Parameters: 'Sample/Namespace/Middleware|type=admin'
     * @return self
     */
    public static function globalMiddleware(string|array $middlewares): void
    {
        Router::globalMiddlewareRegister($middlewares);
        return;
    }

    /**
     * Register Global Afterware
     * @param string|array $afterwares Register Router Url Afterware(s)
     * Example: 'Sample/Namespace/Afterware'. Example Using Parameters: 'Sample/Namespace/Afterware|type=admin'
     * @return self
     */
    public static function globalAfterware(string|array $afterwares): void
    {
        Router::globalAfterwareRegister($afterwares);
        return;
    }

    /**
     * Dispatch Router & Run Application
     * @return void
     */
    public static function dispatch(): void
    {
        Dispatcher::dispatch();
    }

    /**
     * Register Fallback
     * @param callable|string|null $callable Register Router Fallnack Controller.
     * Example: 'Sample/Namespace/CallableClass@index' or ['Sample/Namespace/CallableClass','index'] or new Sample/Namespace/CallableClass() (it will call 'index' method)
     * @return self
     */
    public static function fallback(callable|string|null $callable = null, string|array $middlewares = []): self
    {
        $group = Router::getCurrentGroup() ?: null;
        Router::registerFallback($group, $callable);

        $instance             = new self();
        $instance->context    = 'fallback';
        $instance->contextKey = Router::getLastFallbackKey();
        return $instance;
    }

    /**
     * Register Named Router
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        Router::name($name);
        return $this;
    }

    /**
     * Get Nameded Router Url
     * @param string $name
     * @return string
     */
    public static function url(string $name, array $param = []): string
    {
        return Router::namedUrl($name, $param);
    }
}
