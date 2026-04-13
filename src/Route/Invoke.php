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

use Laika\Core\Interfaces\MiddlewareInterface;
use Laika\Core\Interfaces\AfterwareInterface;
use RuntimeException;

class Invoke
{
    /**
     * Invoke Middleware
     * @param array $middlewares Middlewares to Invoke
     * @param callable|string|array|null|object $controller Controller to Call After Middlewares Run
     * @param array $params Parameters
     * @return ?string Return Response From Controller
     */
    public static function middleware(array $middlewares, callable|string|array|null|object $controller, array $params = []): ?string
    {
        $next = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) use ($params) {
                return function ($params) use ($middleware, $next) {
                    $parts = explode('|', $middleware);
                    $parts[0] = trim($parts[0], '\\');

                    $middleware = class_exists($parts[0]) ? $parts[0] : "App\\Middleware\\{$parts[0]}";

                    if (isset($parts[1])) {
                        $args = [];
                        $paramParts = explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v] = explode('=', $paramPart);
                            $args[trim($k)] = trim($v);
                        }
                        $params = array_merge($params, $args);
                    }

                    // it does NOT exit. Without return, execution falls through to `new $middleware`
                    // on an unresolvable class, causing a fatal error.
                    if (!class_exists($middleware)) {
                        throw new RuntimeException("Invalid Middleware: [{$middleware}]");
                        // report_bug(new RuntimeException("Invalid Middleware: [{$middleware}]"));
                        return null;
                    }

                    $obj = new $middleware;

                    if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                        throw new RuntimeException("Middleware Must Implement MiddlewareInterface: [{$middleware}]");
                        return null;
                    }

                    if (!method_exists($obj, 'handle')) {
                        throw new RuntimeException("Method Not Found: [{$middleware}::handle()]");
                        // report_bug(new RuntimeException("Method Not Found: [{$middleware}::handle()]"));
                        return null;
                    }

                    try {
                        return $obj->handle($next, $params);
                    } catch (\Throwable $th) {
                        throw new RuntimeException($th->getMessage(), (int) $th->getCode(), $th);
                        // report_bug($th);
                    }
                    return null;
                };
            },
            function ($params) use ($controller) {
                return self::controller($controller, $params);
            }
        );

        return $next($params);
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

                    if (isset($parts[1])) {
                        $args = [];
                        foreach (explode(',', $parts[1]) as $paramPart) {
                            [$k, $v] = explode('=', $paramPart);
                            $args[trim($k)] = trim($v);
                        }
                        $params = array_merge($params, $args);
                    }

                    if (!class_exists($afterware)) {
                        throw new RuntimeException("Invalid Afterware: [{$afterware}]");
                    }

                    if (!is_subclass_of($afterware, AfterwareInterface::class)) {
                        throw new RuntimeException("Afterware Must Implement Interface: [{$afterware}]");
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
     * @param callable|string|array|null|object $handler Controller. Example: HomeController@index or Closure or null or Function
     * @param array $args Args to Pass to Controller
     * @throws RuntimeException
     * @return ?string
     */
    public static function controller(callable|string|array|null|object $handler, array $args): ?string
    {
        // Execute Null
        if (is_null($handler)) {
            return null;
        }

        // ['HomeController', 'index'] must get the namespace prefix just like the
        // string format 'HomeController@index' does. Without this, array-format
        // controllers require a fully qualified class name while string-format does not.
        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $handler[0] = class_exists($handler[0])
                ? $handler[0]
                : "App\\Controller\\{$handler[0]}";
        }

        // Execute Callable (closures, functions, and now namespace-resolved array callables)
        if (is_callable($handler)) {
            $reflection = new Reflection($handler, $args);
            try {
                return call_user_func($handler, ...$reflection->namedArgs());
            } catch (\Throwable $th) {
                report_bug($th);
            }
            return null;
        }

        // Execute String
        if (is_string($handler)) {
            // Without this, explode('@', 'HomeController') returns a single-element array,
            // and [$controller, $method] destructuring silently sets $method to null.
            if (!str_contains($handler, '@')) {
                throw new RuntimeException("Invalid Controller Assigned: [{$handler}]. Expected 'ControllerClass@method' format.");
            }

            [$controller, $method] = explode('@', $handler, 2);

            $controller = "App\\Controller\\{$controller}";

            // Check Controller Exists
            if (!class_exists($controller)) {
                throw new RuntimeException("Invalid Controller: [{$controller}]");
            }
            // Check Method Exists
            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Invalid Method: [{$method}] on [{$controller}]");
            }
            // Call Controller
            $obj = new $controller();
            $reflection = new Reflection([$obj, $method], $args);
            try {
                return call_user_func([$obj, $method], ...$reflection->namedArgs());
            } catch (\Throwable $th) {
                report_bug($th);
            }
            return null;
        }

        // Throw RuntimeException
        throw new RuntimeException("Invalid Controller: " . print_r($handler, true));
    }
}
