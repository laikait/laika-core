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

namespace Laika\Core\Relay;

use Laika\Core\Exceptions\RelayException;

/**
 * Relay — Static proxy base class for Laika services.
 *
 * Extend this class to create a static entry point for any
 * service registered in the RelayRegistry.
 *
 * Example:
 *   Session::get('user_id');
 *   Config::get('app.name');
 */
abstract class Relay
{
    private static RelayRegistry $registry;

    abstract protected static function getRelayAccessor(): string;

    public static function setRegistry(RelayRegistry $registry): void
    {
        if (isset(self::$registry)) {
            throw new RelayException('RelayRegistry has already been set. setRegistry() must only be called once during bootstrap.'
            );
        }

        self::$registry = $registry;
    }

    public static function getRegistry(): RelayRegistry
    {
        if (!isset(self::$registry)) {
            throw new RelayException('RelayRegistry has not been set. Call Relay::setRegistry($registry) during bootstrap.'
            );
        }

        return self::$registry;
    }

    /**
     * Always delegates to the registry.
     * Singleton caching lives in the registry — not here.
     */
    protected static function resolveInstance(): object
    {
        return static::getRegistry()->make(static::getRelayAccessor());
    }

    public static function relayRoot(): object
    {
        return static::resolveInstance();
    }

    /**
     * Swap the resolved instance — clears registry cache too.
     */
    public static function swap(object $instance): void
    {
        static::getRegistry()->instance(static::getRelayAccessor(), $instance);
    }

    /**
     * Clear the cached instance from the registry,
     * forcing re-resolution on next call.
     */
    public static function clearResolvedInstance(): void
    {
        static::getRegistry()->forgetInstance(static::getRelayAccessor());
    }

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
