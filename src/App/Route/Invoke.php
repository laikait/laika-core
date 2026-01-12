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
use Throwable;

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
        // Build the Chain in Normal Order (global → group → route)
        $next = \array_reduce(
            \array_reverse($middlewares), // Reverse to Preserve Execution order
            function ($next, $middleware) use ($params) {
                return function ($params) use ($middleware, $next) {
                    // Separate Parameters From Middleware
                    $parts = \explode('|', $middleware);
                    $parts[0] = \trim($parts[0], '\\');
                    // Get Class
                    $middleware = \class_exists($parts[0]) ? $parts[0] : "Laika\\App\\Middleware\\{$parts[0]}";
                    if (isset($parts[1])) {
                        $args = [];
                        $paramParts = \explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v] = \explode('=', $paramPart);
                            $args[\trim($k)] = \trim($v);
                        }
                        $params = \array_merge($params, $args);
                    }
                    if (!\class_exists($middleware)) {
                        \report_bug(new RuntimeException("Invalid Middleware: [{$middleware}]"));
                    }
                    $obj = new $middleware;

                    // Check handle Method Exists
                    if (!\method_exists($obj, 'handle')) {
                        \report_bug(new RuntimeException("Method Not Found: [{$middleware}::handle()]"));
                    }
                    try {
                        return $obj->handle($next, $params);
                    } catch (\Throwable $th) {
                        \report_bug($th);
                    }
                };
            },
            function ($params) use ($controller) { // Final callable (controller)
                return self::controller($controller, $params);
            }
        );
        return $next($params); // Execute the full chain
    }

    /**
     * Invoke Afterware
     * @param array $afterwares Afterwares to Invoke
     * @param ?string $response Rresponse to Show
     * @param array $params Parameters
     * @return ?string Return Response From Controller
     */
    public static function afterware(array $afterwares, ?string $response, array $params = []): ?string
    {
        // Build the Chain in Normal Order (global → group → route)
        $next = \array_reduce(
            \array_reverse($afterwares), // Reverse to Preserve Execution order
            function ($next, $afterware) use ($params) {
                return function ($response) use ($afterware, $next, $params) {
                    // Separate Parameters From Afterware
                    $parts = \explode('|', $afterware);
                    $parts[0] = \trim($parts[0], '\\');
                    $afterware = \class_exists($parts[0]) ? $parts[0] : "Laika\\App\\Middleware\\{$parts[0]}";
                    if (isset($parts[1])) {
                        $args = [];
                        $paramParts = \explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v] = \explode('=', $paramPart);
                            $args[\trim($k)] = \trim($v);
                        }
                        $params = \array_merge($params, $args);
                    }
                    if (!\class_exists($afterware)) {
                        \report_bug(new RuntimeException("Invalid Afterware: [{$afterware}]"));
                    }

                    $obj = new $afterware;

                    if (!\method_exists($obj, 'terminate')) {
                        \report_bug(new RuntimeException("Method Not Found: [{$afterware}::terminate()]"));
                    }

                    // Execute the current afterware, passing response and chain
                    try {
                        return $obj->terminate($response, function ($newResponse) use ($next, $params) {
                            return $next($newResponse);
                        }, $params);
                    } catch (\Throwable $th) {
                        \report_bug($th);
                    }
                };
            },
            fn($response) => $response // Initial chain returns final response
        );

        return $next($response);
    }

    /**
     * @param callable|string|null $handler Controller. Example: HomeController@index or Closure or null or Function
     * @param array $args Args to Pass to Controller
     * @throws RuntimeException
     * @return ?string
     */
    public static function controller(callable|string|null $handler, array $args): ?string
    {
        // Execute Null
        if (\is_null($handler)) {
            return null;
        }

        // Execute Callable
        if (\is_callable($handler)) {
            $reflection = new Reflection($handler, $args);
            try {
                return \call_user_func($handler, ...$reflection->namedArgs());
            } catch (\Throwable $th) {
                \report_bug($th);
            }
            return null;
        }

        // Execute String
        if (\is_string($handler)) {
            $parts = \explode('@', $handler);
            $controller = $parts[0];
            if (!isset($parts[1])) {
                \report_bug(new RuntimeException("Invalid Controller: {$handler}"));
            }
            [$controller, $method] = \explode('@', $handler);
            $controller = "Laika\\App\\Controller\\{$controller}";
            // Check Controller Exists
            if (!\class_exists($controller)) {
                \report_bug(new RuntimeException("Invalid Controller: {$handler}"));
            }
            // Check Method Exists
            if (!\method_exists($controller, $method)) {
                \report_bug(new RuntimeException("Invalid Method: {$handler}"));
            }
            // Call Controller
            $obj = new $controller();
            $reflection = new Reflection([$controller, $method], $args);
            try {
                return \call_user_func([$obj, $method], ...$reflection->namedArgs());
            } catch (\Throwable $th) {
                \report_bug($th);
            }
            return null;
        }

        // Throw RuntimeException
        throw new RuntimeException("Invalid Controller: " . print_r($handler, true));
    }
}
