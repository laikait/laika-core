<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay;

use Laika\Core\Exceptions\RelayException;

/**
 * ProviderRegistry — Manages the loading and booting of RelayProviders.
 *
 * Orchestrates the two-phase provider lifecycle:
 *   Phase 1 — register(): all providers bind their services into the registry.
 *   Phase 2 — boot():     all providers run post-registration setup.
 *
 * The two phases are kept separate so that when boot() runs, every service
 * registered by every other provider is guaranteed to be available in the
 * registry — regardless of the order providers were registered.
 *
 * Typical bootstrap usage:
 *
 *   $registry  = new RelayRegistry();
 *   $providers = new ProviderRegistry($registry);
 *
 *   $providers->register(CoreRelayProvider::class);
 *   $providers->register(AuthRelayProvider::class);
 *
 *   foreach (config('app.providers') as $provider) {
 *       $providers->register($provider);
 *   }
 *
 *   $providers->boot();
 *
 *   Relay::setRegistry($registry);
 */
class ProviderRegistry
{
    /**
     * All registered provider instances, in registration order.
     *
     * @var RelayProvider[]
     */
    private array $providers = [];

    /**
     * Tracks registered provider class names to prevent duplicate registration.
     *
     * @var array<string,bool>
     */
    private array $registered = [];

    public function __construct(
        private RelayRegistry $registry
    ) {}

    /**
     * Register a service provider.
     *
     * Accepts either a fully-qualified class name string or an already-
     * constructed RelayProvider instance.
     *
     * Calls register() on the provider immediately. boot() is deferred
     * until ProviderRegistry::boot() is called explicitly.
     *
     * Duplicate registrations (same class registered twice) are silently
     * ignored — register() will not be called a second time.
     *
     * @param string|RelayProvider $provider FQCN or instance.
     *
     * @throws RelayException If the class does not exist or is not a RelayProvider.
     */
    public function register(string|RelayProvider $provider): static
    {
        // Resolve string to instance
        if (is_string($provider)) {
            if (!class_exists($provider)) {
                throw new RelayException(
                    "RelayProvider [{$provider}] class not found. " .
                    "Ensure the package is installed and autoloaded."
                );
            }

            if (!is_subclass_of($provider, RelayProvider::class)) {
                throw new RelayException(
                    "[{$provider}] must extend Laika\\Core\\Relay\\RelayProvider."
                );
            }

            $provider = new $provider($this->registry);
        }

        $class = $provider::class;

        // Prevent duplicate registration
        if (isset($this->registered[$class])) {
            return $this;
        }

        $this->registered[$class] = true;
        $this->providers[]        = $provider;

        // Phase 1 — register bindings immediately
        $provider->register();

        return $this;
    }

    /**
     * Boot all registered providers.
     *
     * Must be called after all register() calls are complete.
     * Calls boot() on every provider in registration order.
     */
    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }

    /**
     * Check whether a provider class has been registered.
     *
     * @param string $class Fully-qualified class name.
     */
    public function has(string $class): bool
    {
        return isset($this->registered[$class]);
    }

    /**
     * Return all registered provider instances.
     *
     * @return RelayProvider[]
     */
    public function providers(): array
    {
        return $this->providers;
    }
}
