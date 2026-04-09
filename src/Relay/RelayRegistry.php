<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Laika\Core\Exceptions\RelayException;

/**
 * RelayRegistry — The Laika Service Container.
 *
 * Manages all service bindings and resolves instances via:
 *   - instance()   : pre-built object, stored as-is
 *   - singleton()  : built once on first make(), cached for the lifetime of the request
 *   - bind()       : built fresh on every make(), never cached
 *
 * Supports reflection-based constructor auto-wiring. Dependencies that are
 * registered in the container are resolved automatically. Primitive or
 * non-registered values can be provided via the $args array.
 */
class RelayRegistry
{
    /**
     * Transient bindings — new instance on every make().
     *
     * @var array<string, array{concrete: Closure|string, args: array}>
     */
    private array $bindings = [];

    /**
     * Singleton bindings — built once, then promoted to $instances.
     *
     * @var array<string, array{concrete: Closure|string, args: array}>
     */
    private array $singletons = [];

    /**
     * Resolved/pre-bound instances — returned directly on every make().
     *
     * @var array<string, object>
     */
    private array $instances = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a transient binding.
     *
     * A brand-new instance is created on every make() call. Nothing is cached.
     * Use for stateless, disposable objects (validators, form requests, DTOs).
     *
     * @param string         $key      Registry key used to resolve this binding.
     * @param Closure|string $concrete Closure factory or fully-qualified class name.
     * @param array          $args     Positional or named args for unresolvable constructor parameters.
     */
    public function bind(string $key, Closure|string $concrete, array $args = []): static
    {
        $this->bindings[$key] = compact('concrete', 'args');
        return $this;
    }

    /**
     * Register a singleton binding.
     *
     * The instance is built once on the first make() call, then cached and
     * returned on every subsequent call. Use for stateful services shared
     * across the entire request (session, auth, config, mailer).
     *
     * @param string         $key      Registry key used to resolve this binding.
     * @param Closure|string $concrete Closure factory or fully-qualified class name.
     * @param array          $args     Positional or named args for unresolvable constructor parameters.
     */
    public function singleton(string $key, Closure|string $concrete, array $args = []): static
    {
        $this->singletons[$key] = compact('concrete', 'args');
        return $this;
    }

    /**
     * Register an already-constructed object directly.
     *
     * The registry stores exactly the object you provide. It never calls new.
     * Use when the object already exists before the registry is set up, or when
     * construction has side effects that must be controlled manually (e.g. a PDO
     * connection established at boot).
     *
     * @param string $key      Registry key used to resolve this binding.
     * @param object $instance The pre-built object to store.
     */
    public function instance(string $key, object $instance): static
    {
        $this->instances[$key] = $instance;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve a binding by key.
     *
     * Resolution order:
     *   1. Pre-bound instance  (instance())
     *   2. Cached singleton    (already resolved on a previous make() call)
     *   3. Singleton binding   — build, cache, return
     *   4. Transient binding   — build and return (no caching)
     *   5. Bare class name     — attempt direct auto-wire if class exists
     *   6. RelayException      — nothing matched
     *
     * @throws RelayException When no binding is found or construction fails.
     */
    public function make(string $key): object
    {
        // 1 & 2. Pre-bound or cached singleton instance
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // 3. Singleton — build once, cache, return
        if (isset($this->singletons[$key])) {
            $entry    = $this->singletons[$key];
            $instance = $this->build($entry['concrete'], $entry['args']);
            $this->instances[$key] = $instance;
            return $instance;
        }

        // 4. Transient — build fresh every time
        if (isset($this->bindings[$key])) {
            $entry = $this->bindings[$key];
            return $this->build($entry['concrete'], $entry['args']);
        }

        // 5. Last resort — auto-wire the key itself as a class name
        if (class_exists($key)) {
            return $this->build($key, []);
        }

        throw new RelayException(
            "No binding registered for [{$key}]. " .
            "Register it via RelayRegistry::singleton(), ::bind(), or ::instance()."
        );
    }

    /**
     * Check whether a key has any binding registered.
     */
    public function has(string $key): bool
    {
        return isset($this->bindings[$key])
            || isset($this->singletons[$key])
            || isset($this->instances[$key]);
    }

    /**
     * Clear a cached singleton instance, forcing re-resolution on the next make().
     *
     * Useful when re-registering a service with different parameters at runtime.
     */
    public function forgetInstance(string $key): static
    {
        unset($this->instances[$key]);
        return $this;
    }

    // -------------------------------------------------------------------------
    // Build
    // -------------------------------------------------------------------------

    /**
     * Build a concrete binding into an object.
     *
     * - Closure: called with ($this, ...$args)
     * - Class string: constructor parameters are auto-wired via reflection;
     *   $args fills any gaps that cannot be resolved from the registry.
     *
     * @param Closure|string $concrete
     * @param array          $args     Explicit overrides for non-resolvable parameters.
     *
     * @throws RelayException
     */
    private function build(Closure|string $concrete, array $args = []): object
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, ...$args);
        }

        if (!class_exists($concrete)) {
            throw new RelayException("Class [{$concrete}] not found.");
        }

        try {
            $ref         = new ReflectionClass($concrete);
            $constructor = $ref->getConstructor();

            if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                return $ref->newInstance();
            }

            $resolved = $this->resolveParameters($constructor->getParameters(), $args);
            return $ref->newInstanceArgs($resolved);

        } catch (ReflectionException $e) {
            throw new RelayException(
                "Failed to build [{$concrete}]: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Resolve constructor parameters for auto-wiring.
     *
     * Per-parameter resolution order:
     *   1. Type-hinted class found in registry   → make() it
     *   2. Type-hinted class exists (not in registry) → build() recursively
     *   3. Primitive / override                  → pull from $args (named or positional)
     *   4. Has default value                     → use it
     *   5. Nullable                              → pass null
     *   6. Nothing matched                       → throw RelayException
     *
     * @param ReflectionParameter[] $parameters
     * @param array                 $args        Positional or named explicit values.
     *
     * @throws RelayException
     */
    private function resolveParameters(array $parameters, array $args): array
    {
        $resolved   = [];
        $argsCursor = 0;

        foreach ($parameters as $param) {
            $type = $param->getType();

            // 1 & 2. Type-hinted class — try registry then direct build
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if ($this->has($typeName)) {
                    $resolved[] = $this->make($typeName);
                    continue;
                }

                if (class_exists($typeName)) {
                    try {
                        $resolved[] = $this->build($typeName, []);
                        continue;
                    } catch (RelayException) {
                        // Fall through to $args / default
                    }
                }
            }

            // 3a. Named key in $args
            if (array_key_exists($param->getName(), $args)) {
                $resolved[] = $args[$param->getName()];
                continue;
            }

            // 3b. Positional $args
            if (array_key_exists($argsCursor, $args)) {
                $resolved[] = $args[$argsCursor++];
                continue;
            }

            // 4. Default value
            if ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
                continue;
            }

            // 5. Nullable
            if ($param->allowsNull()) {
                $resolved[] = null;
                continue;
            }

            throw new RelayException(
                "Cannot resolve parameter [\${$param->getName()}] " .
                "for [{$param->getDeclaringClass()?->getName()}]. " .
                "Pass it via the \$args array."
            );
        }

        return $resolved;
    }
}
