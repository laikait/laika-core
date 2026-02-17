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

namespace Laika\Core\App\Route;

use Laika\Core\Http\Request;
use Laika\Core\Http\Response;

class Invoke
{
    /**
     * Invoke Middleware
     * @param array $middlewares Middlewares to Invoke
     * @param callable|string|array|null|object $controller Controller to Call After Middlewares Run
     * @param array $params Parameters
     * @param ?Request $request Request Object
     * @param ?Response $response Response Object
     * @return ?string Return Response From Controller
     */
    public static function middleware(array $middlewares, callable|string|array|null|object $controller, array $params = [], ?Request $request = null, ?Response $response = null): ?string
    {
        $request  ??= new \Laika\Core\Http\Request();
        $response ??= new \Laika\Core\Http\Response();

        $next = \array_reduce(
            \array_reverse($middlewares),
            function ($next, $middleware) use ($params, $request, $response) {
                return function ($params) use ($middleware, $next, $request, $response) {
                    $parts    = \explode('|', $middleware);
                    $parts[0] = \trim($parts[0], '\\');

                    $middleware = \class_exists($parts[0]) ? $parts[0] : "Laika\\App\\Middleware\\{$parts[0]}";

                    if (isset($parts[1])) {
                        $args       = [];
                        $paramParts = \explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v]    = \explode('=', $paramPart);
                            $args[\trim($k)] = \trim($v);
                        }
                        $params = \array_merge($params, $args);
                    }

                    if (!\class_exists($middleware)) {
                        \report_bug(new \RuntimeException("Invalid Middleware: [{$middleware}]"));
                    }

                    $obj = new $middleware;

                    if (!\method_exists($obj, 'handle')) {
                        \report_bug(new \RuntimeException("Method Not Found: [{$middleware}::handle()]"));
                    }

                    try {
                        return $obj->handle($next, $request, $response, $params);
                    } catch (\Throwable $th) {
                        \report_bug($th);
                    }
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
     * @param ?Request $request Request Object
     * @param ?Response $response Response Object
     * @param array $params Parameters
     * @return ?string Return Response From Controller
     */
    public static function afterware(array $afterwares, ?string $output, array $params = [], ?Request $request = null, ?Response $response = null): ?string
    {
        $request  ??= new \Laika\Core\Http\Request();
        $response ??= new \Laika\Core\Http\Response();

        $next = \array_reduce(
            \array_reverse($afterwares),
            function ($next, $afterware) use ($params, $request, $response) {
                return function ($output) use ($afterware, $next, $params, $request, $response) {
                    $parts    = \explode('|', $afterware);
                    $parts[0] = \trim($parts[0], '\\');

                    $afterware = \class_exists($parts[0]) ? $parts[0] : "Laika\\App\\Afterware\\{$parts[0]}";

                    if (isset($parts[1])) {
                        $args       = [];
                        $paramParts = \explode(',', $parts[1]);
                        foreach ($paramParts as $paramPart) {
                            [$k, $v]    = \explode('=', $paramPart);
                            $args[\trim($k)] = \trim($v);
                        }
                        $params = \array_merge($params, $args);
                    }

                    if (!\class_exists($afterware)) {
                        \report_bug(new \RuntimeException("Invalid Afterware: [{$afterware}]"));
                    }

                    $obj = new $afterware;

                    if (!\method_exists($obj, 'terminate')) {
                        \report_bug(new \RuntimeException("Method Not Found: [{$afterware}::terminate()]"));
                    }

                    try {
                        return $obj->terminate(
                            function ($newOutput) use ($next, $params) {
                                return $next($newOutput);
                            },
                            $request,
                            $response,
                            $output,
                            $params
                        );
                    } catch (\Throwable $th) {
                        \report_bug($th);
                    }
                };
            },
            fn($output) => $output
        );

        return $next($output);
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
                throw new \RuntimeException("Controller Error: {$th->getMessage()}");
            }
            return null;
        }

        // Execute String
        if (\is_string($handler)) {
            // Get Controler Class & Method Name
            try {
                [$controller, $method] = \explode('@', $handler);
            } catch (\Throwable $th) {
                throw new \RuntimeException("Invalid Controller Assigned: [{$handler}]");
            }

            $controller = "Laika\\App\\Controller\\{$controller}";

            // Check Controller Exists
            if (!\class_exists($controller)) {
                throw new \RuntimeException("Invalid Controller: [{$controller}]");
            }
            // Check Method Exists
            if (!\method_exists($controller, $method)) {
                throw new \RuntimeException("Invalid Method: [{$method}]");
            }
            // Call Controller
            $obj = new $controller();
            $reflection = new Reflection([$obj, $method], $args);
            try {
                return \call_user_func([$obj, $method], ...$reflection->namedArgs());
            } catch (\Throwable $th) {
                \report_bug($th);
            }
            return null;
        }

        // Throw RuntimeException
        throw new \RuntimeException("Invalid Controller: " . print_r($handler, true));
    }
}
