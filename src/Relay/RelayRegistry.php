<?php

declare(strict_types=1);

namespace Laika\Core\Relay;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Laika\Core\Exceptions\RelayException;

class RelayRegistry
{
    /** @var array<string, array{concrete: Closure|string, args: array}> */
    private array $bindings = [];

    /** @var array<string, array{concrete: Closure|string, args: array}> */
    private array $singletons = [];

    /** @var array<string, object> */
    private array $instances = [];

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    /**
     * Register a transient binding.
     * A new instance is created on every make().
     *
     * @param array $args Explicit args for unresolvable constructor parameters.
     */
    public function bind(string $key, Closure|string $concrete, array $args = []): static
    {
        $this->bindings[$key] = compact('concrete', 'args');
        return $this;
    }

    /**
     * Register a singleton binding.
     * The same instance is returned on every make().
     *
     * @param array $args Explicit args for unresolvable constructor parameters.
     */
    public function singleton(string $key, Closure|string $concrete, array $args = []): static
    {
        $this->singletons[$key] = compact('concrete', 'args');
        return $this;
    }

    /**
     * Register an already-constructed object directly.
     */
    public function instance(string $key, object $instance): static
    {
        $this->instances[$key] = $instance;
        return $this;
    }

    // -----------------------------------------------------------------------
    // Resolution
    // -----------------------------------------------------------------------

    /**
     * Resolve a binding by key.
     *
     * @throws RelayException
     */
    public function make(string $key): object
    {
        // 1. Pre-bound instance (highest priority)
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // 2. Singleton — resolve once, then cache as instance
        if (isset($this->singletons[$key])) {
            $entry    = $this->singletons[$key];
            $instance = $this->build($entry['concrete'], $entry['args']);
            $this->instances[$key] = $instance;
            return $instance;
        }

        // 3. Transient — new instance every call
        if (isset($this->bindings[$key])) {
            $entry = $this->bindings[$key];
            return $this->build($entry['concrete'], $entry['args']);
        }

        // 4. Last resort: attempt to auto-wire the key as a class name
        if (class_exists($key)) {
            return $this->build($key, []);
        }

        throw new RelayException("No binding registered for [{$key}]. Register it via RelayRegistry::singleton() or ::bind().");
    }

    public function has(string $key): bool
    {
        return isset($this->bindings[$key])
            || isset($this->singletons[$key])
            || isset($this->instances[$key]);
    }

    public function forgetInstance(string $key): static
    {
        unset($this->instances[$key]);
        return $this;
    }

    // -----------------------------------------------------------------------
    // Build
    // -----------------------------------------------------------------------

    /**
     * Build a concrete into an object.
     *
     * If $concrete is a Closure, it is called with ($this, ...$args).
     * If $concrete is a class string, its constructor is auto-wired via
     * reflection. Dependencies that exist in the registry are resolved
     * automatically; everything else is filled from $args in order.
     *
     * @param array $args Explicit overrides for non-resolvable parameters.
     *
     * @throws RelayException
     */
    private function build(Closure|string $concrete, array $args = []): object
    {
        // Closure path — pass registry + any extra args
        if ($concrete instanceof Closure) {
            return $concrete($this, ...$args);
        }

        if (!class_exists($concrete)) {
            throw new RelayException("Class [{$concrete}] not found.");
        }

        try {
            $ref         = new ReflectionClass($concrete);
            $constructor = $ref->getConstructor();

            // No constructor or no parameters — plain instantiation
            if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                return $ref->newInstance();
            }

            $resolved = $this->resolveParameters(
                $constructor->getParameters(),
                $args
            );

            return $ref->newInstanceArgs($resolved);

        } catch (ReflectionException $e) {
            throw new RelayException(
                "Failed to build [{$concrete}]: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Resolve constructor parameters using:
     *   1. Registry auto-wiring (when type-hint is a known class/interface key)
     *   2. Explicit $args array (positional, for primitives)
     *   3. Parameter default values
     *
     * @param ReflectionParameter[] $parameters
     * @param array                 $args        Positional values for primitives.
     *
     * @throws RelayException
     */
    private function resolveParameters(array $parameters, array $args): array
    {
        $resolved  = [];
        $argsCursor = 0;

        foreach ($parameters as $param) {
            $type = $param->getType();

            // --- Type-hinted class: try auto-wiring from registry first ---
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if ($this->has($typeName)) {
                    $resolved[] = $this->make($typeName);
                    continue;
                }

                // Not in registry — try resolving the class directly
                if (class_exists($typeName)) {
                    try {
                        $resolved[] = $this->build($typeName, []);
                        continue;
                    } catch (RelayException) {
                        // Fall through to $args / default below
                    }
                }
            }

            // --- Explicit $args (positional) for primitives or overrides ---
            if (array_key_exists($argsCursor, $args)) {
                $resolved[] = $args[$argsCursor++];
                continue;
            }

            // --- Named key in $args ---
            if (array_key_exists($param->getName(), $args)) {
                $resolved[] = $args[$param->getName()];
                continue;
            }

            // --- Default value ---
            if ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
                continue;
            }

            // --- Nullable ---
            if ($param->allowsNull()) {
                $resolved[] = null;
                continue;
            }

            throw new RelayException(
                "Cannot resolve parameter [\${$param->getName()}] " .
                "for class [{$param->getDeclaringClass()?->getName()}]. " .
                "Pass it via the \$args array."
            );
        }

        return $resolved;
    }
}
