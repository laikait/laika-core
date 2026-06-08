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
use Laika\Core\Interfaces\{MiddlewareInterface, AfterwareInterface};

class Invoke
{
    /**
     * Invoke Middleware
     * @param array $middlewares Middlewares to Invoke
     * @param callable|string|array|null|object $controller Controller to Call After Middlewares Run
     * @param array $params Parameters
     * @return array Return Response From Controller
     */
    public static function middleware(array $middlewares, callable|string|array|null|object $controller, array $params = []): array
    {
        $finalParams = $params;

        $next = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($params) use ($middleware, $next) {
                    $parts = explode('|', $middleware);
                    $parts[0] = trim($parts[0], '\\');

                    $middleware = class_exists($parts[0]) ? $parts[0] : "App\\Middleware\\{$parts[0]}";

                    // Check Middleware is Exists
                    if (!class_exists($middleware)) {
                        throw new RouteException("Middleware Class [{$parts[0]}] Doesn't Exists!");
                    }

                    if (isset($parts[1])) {
                        $args = [];
                        $paramParts = explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v] = explode('=', $paramPart);
                            $args[trim($k)] = trim($v);
                        }
                        $params = array_merge($params, $args);
                    }

                    if (!class_exists($middleware)) {
                        throw new RouteException("Invalid Middleware: [{$middleware}]");
                    }

                    if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                        throw new RouteException("Middleware Must Implement MiddlewareInterface: [{$middleware}]");
                    }

                    $obj = new $middleware;

                    if (!method_exists($obj, 'handle')) {
                        throw new RouteException("Method Not Found: [{$middleware}::handle()]");
                    }

                    try {
                        return $obj->handle($next, $params);
                    } catch (\Throwable $th) {
                        report_error($th);
                    }
                };
            },
            function ($params) use ($controller, &$finalParams) {
                $finalParams = $params;
                return self::controller($controller, $params);
            }
        );

        $output = $next($params);

        return [$output, $finalParams];
    }

    /**
     * Invoke Afterware
     * @param array $afterwares Afterwares to Invoke
     * @param ?string $output Response body to show
     * @param array $params Parameters
     * @return ?string Return Response From Controller
     */
    public static function afterware(array $afterwares, ?string $output, array $params = []): ?string
    {
        $next = array_reduce(
            array_reverse($afterwares),
            function ($next, $afterware) {
                return function ($output, $params) use ($afterware, $next) {

                    $parts = explode('|', $afterware);
                    $parts[0] = trim($parts[0], '\\');

                    $afterware = class_exists($parts[0]) ? $parts[0] : "App\\Afterware\\{$parts[0]}";

                    // Check Afterware is Exists
                    if (!class_exists($afterware)) {
                        throw new RouteException("Afterware Class [{$parts[0]}] Doesn't Exists!");
                    }

                    if (isset($parts[1])) {
                        $args = [];
                        foreach (explode(',', $parts[1]) as $paramPart) {
                            [$k, $v] = explode('=', $paramPart);
                            $args[trim($k)] = trim($v);
                        }
                        $params = array_merge($params, $args);
                    }

                    if (!class_exists($afterware)) {
                        throw new RouteException("Invalid Afterware: [{$afterware}]");
                    }

                    if (!is_subclass_of($afterware, AfterwareInterface::class)) {
                        throw new RouteException("Afterware Must Implement Interface: [{$afterware}]");
                    }

                    $obj = new $afterware();

                    return $obj->terminate(
                        function ($newOutput, $newParams) use ($next) {
                            return $next($newOutput, $newParams);
                        },
                        $output,
                        $params
                    );
                };
            },
            fn($output, $params) => $output
        );

        return $next($output, $params);
    }

    /**
     * @param callable|string|null $handler Controller. Example: HomeController@index or Closure or null or Function
     * @param array $args Args to Pass to Controller
     * @throws RouteException
     * @return ?string
     */
    public static function controller(callable|string|null $handler, array $args): ?string
    {
        // Execute Null
        if (is_null($handler)) {
            return null;
        }

        // Execute Callable (closures, functions, and now namespace-resolved array callables)
        if (is_callable($handler)) {
            $reflection = new Reflection($handler, $args);
            try {
                return call_user_func($handler, ...$reflection->namedArgs());
            } catch (\Throwable $e) {
                report_error($e);
            }
        }

        // Execute String
        if (is_string($handler)) {
            if (!str_contains($handler, '@')) {
                throw new RouteException("Invalid Controller: [{$handler}]. Expected 'ControllerClass@method' format.");
            }

            [$controller, $method] = explode('@', $handler, 2);
            $controller = "App\\Controller\\{$controller}";

            if (!class_exists($controller)) {
                throw new RouteException("Invalid Controller: [{$controller}]");
            }
            if (!method_exists($controller, $method)) {
                throw new RouteException("Invalid Method: [{$method}] on [{$controller}]");
            }

            $obj = new $controller();
            $reflection = new Reflection([$obj, $method], $args);
            try {
                return $obj->{$method}(...$reflection->namedArgs());
            } catch (\Throwable $th) {
                report_error($th);
            }
        }

        // Throw RouteException
        throw new RouteException("Invalid Controller: " . print_r($handler, true));
    }
}
