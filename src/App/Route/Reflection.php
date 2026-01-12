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

use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Closure;

class Reflection
{
    /**
     * @var array $params
     */
    private array $params;

    /**
     * @var callable|array $callable
     */
    private $callable;

    /**
     * @var ReflectionFunctionAbstract $reflection
     */
    private ReflectionFunctionAbstract $reflection;

    public function __construct(callable|array $callable, array $params = [])
    {
        $this->callable = $callable;
        $this->params = $params;
        $this->reflection = $this->resolveReflection($callable);
    }

    /**
     * Resolve parameters to ordered list for invocation
     * @param array{string,mixed}
     */
    public function namedArgs(): array
    {
        $resolved = [];
        foreach ($this->reflection->getParameters() as $param) {
            $name = $param->getName();

            // Match by name
            if (array_key_exists($name, $this->params)) {
                $resolved[$name] = $this->params[$name];
                continue;
            }

            // Optional parameter with default
            if ($param->isDefaultValueAvailable()) {
                $resolved[$name] = $param->getDefaultValue();
                continue;
            }

            // Variadic parameter (optional)
            if ($param->isVariadic()) {
                $resolved[$name] = [];
                continue;
            }

            // Required parameter missing
            throw new RuntimeException("Missing required parameter: \${$name}");
        }
        return $resolved;
    }

    /**
     * Developer-friendly callable info
     * @return string
     */
    public function __toString(): string
    {
        try {
            $signature = '';
            $callable = $this->callable;

            if ($callable instanceof Closure) {
                $type = 'Closure';
            } elseif (\is_array($callable)) {
                [$classOrObject, $method] = $callable;
                $className = \is_object($classOrObject)
                    ? \get_class($classOrObject)
                    : (string) $classOrObject;
                $type = 'Method';
                $signature = "{$className}::{$method}";
            } elseif (\is_string($callable)) {
                $type = \function_exists($callable) ? 'Function' : 'StaticCallable';
                $signature = $callable;
            } elseif (\is_object($callable)) {
                $type = 'InvokableObject';
                $signature = \get_class($callable) . '::__invoke';
            } else {
                $type = 'Unknown';
            }

            // Parameter info
            $paramLines = [];
            foreach ($this->reflection->getParameters() as $param) {
                $line = "- $" . $param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $line .= " = " . \var_export($param->getDefaultValue(), true);
                }
                $paramLines[] = $line;
            }

            $paramText = $paramLines
                ? "  Parameters:\n    " . \implode("\n    ", $paramLines)
                : "  Parameters: (none)";

            return "{$type} Reflection {\n  Callable: {$signature}\n{$paramText}\n}";
        } catch (\Throwable $e) {
            return "Reflection Error: " . $e->getMessage();
        }
    }

    ###########################################################
    /*--------------------- PRIVATE API ---------------------*/
    ###########################################################
    /**
     * Build reflection based on callable type
     */
    private function resolveReflection(callable|array $callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        } elseif (\is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        } elseif (\is_string($callable) && \strpos($callable, '::') !== false) {
            return new ReflectionMethod(...\explode('::', $callable, 2));
        } elseif (\is_string($callable)) {
            return new ReflectionFunction($callable);
        } elseif (\is_object($callable) && \method_exists($callable, '__invoke')) {
            return new ReflectionMethod($callable, '__invoke');
        }
        throw new RuntimeException('Invalid callable type for Reflection');
    }
}
