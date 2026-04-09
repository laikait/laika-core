<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay;

use Laika\Core\Exceptions\RelayException;

/**
 * Relay — Static proxy base class for Laika services.
 *
 * Extend this class to create a clean static entry point for any service
 * registered in the RelayRegistry. All static calls are forwarded to the
 * resolved instance via __callStatic.
 *
 * Instance caching lives entirely in the RelayRegistry. The Relay class
 * holds no local cache — this ensures that re-registering or forgetting
 * an instance in the registry is always reflected immediately.
 *
 * Usage:
 *   1. Extend Relay and implement getRelayAccessor().
 *   2. Add @method static docblocks for IDE support.
 *   3. Register the underlying service in RelayRegistry.
 *   4. Call Relay::setRegistry() once at bootstrap.
 *
 * Example:
 *   Session::get('user_id');
 *   Auth::check();
 *   Config::get('app.name');
 */
abstract class Relay
{
    /**
     * The single RelayRegistry shared by all Relay classes.
     * Set once at application bootstrap via setRegistry().
     */
    private static RelayRegistry $registry;

    // -------------------------------------------------------------------------
    // Abstract contract
    // -------------------------------------------------------------------------

    /**
     * Return the registry key that identifies this Relay's underlying service.
     *
     * Example:
     *   return 'session';
     *   return 'auth';
     */
    abstract protected static function getRelayAccessor(): string;

    // -------------------------------------------------------------------------
    // Registry management
    // -------------------------------------------------------------------------

    /**
     * Wire the RelayRegistry into the Relay system.
     *
     * Call this exactly once during application bootstrap, after all
     * service providers have been registered and booted.
     *
     * @throws RelayException If called more than once.
     */
    public static function setRegistry(RelayRegistry $registry): void
    {
        if (isset(self::$registry)) {
            throw new RelayException(
                'RelayRegistry has already been set. ' .
                'Relay::setRegistry() must only be called once during bootstrap.'
            );
        }

        self::$registry = $registry;
    }

    /**
     * Retrieve the shared RelayRegistry.
     *
     * @throws RelayException If setRegistry() has not been called yet.
     */
    public static function getRegistry(): RelayRegistry
    {
        if (!isset(self::$registry)) {
            throw new RelayException(
                'RelayRegistry has not been set. ' .
                'Call Relay::setRegistry($registry) during application bootstrap.'
            );
        }

        return self::$registry;
    }

    /**
     * Replace the registry entirely.
     *
     * FOR TESTING ONLY. Do not call this in production code.
     * Use this in test setUp/tearDown to isolate tests from each other.
     */
    public static function swapRegistry(RelayRegistry $registry): void
    {
        self::$registry = $registry;
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve the underlying service instance from the registry.
     *
     * Delegates entirely to the registry — no local caching here.
     * This ensures re-registration and forgetInstance() are always respected.
     */
    protected static function resolveInstance(): object
    {
        return static::getRegistry()->make(static::getRelayAccessor());
    }

    /**
     * Return the resolved root instance directly.
     *
     * Useful when you need the real typed object rather than going
     * through static proxy calls.
     *
     * Example:
     *   $auth = Auth::relayRoot(); // returns Laika\Core\Auth\Auth instance
     */
    public static function relayRoot(): object
    {
        return static::resolveInstance();
    }

    // -------------------------------------------------------------------------
    // Swap (testing / runtime override)
    // -------------------------------------------------------------------------

    /**
     * Swap the resolved instance with a custom object.
     *
     * Registers the object directly in the registry via instance(),
     * replacing whatever was previously bound for this accessor.
     *
     * Use in tests to inject mocks/stubs, or in middleware to switch
     * service context (e.g. switching auth guard).
     *
     * Example:
     *   Auth::swap(new Auth(guard: 'admin'));
     */
    public static function swap(object $instance): void
    {
        static::getRegistry()->instance(static::getRelayAccessor(), $instance);
    }

    /**
     * Clear the cached instance for this Relay from the registry.
     *
     * Forces re-resolution on the next call. Useful after re-registering
     * a service with new parameters.
     *
     * Example:
     *   Auth::clearResolvedInstance();
     *   $registry->singleton('auth', Auth::class, ['admin']);
     */
    public static function clearResolvedInstance(): void
    {
        static::getRegistry()->forgetInstance(static::getRelayAccessor());
    }

    // -------------------------------------------------------------------------
    // Magic static proxy
    // -------------------------------------------------------------------------

    /**
     * Forward any static call to the resolved service instance.
     *
     * This is the core of the Relay system. Any static method call on a
     * Relay subclass that is not explicitly defined will land here, resolve
     * the underlying instance, and forward the call.
     *
     * @throws RelayException When the method does not exist on the resolved instance.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveInstance();

        if (!method_exists($instance, $method)) {
            $class = $instance::class;
            throw new RelayException("Method [{$method}] does not exist on [{$class}].");
        }

        return $instance->$method(...$args);
    }
}
