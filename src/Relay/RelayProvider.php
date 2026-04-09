<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay;

/**
 * RelayProvider — Base class for registering services into the Laika container.
 *
 * Every package that wants to integrate with the Laika framework extends this
 * class and implements register(). Optionally override boot() for any logic
 * that depends on other services being registered first.
 *
 * Lifecycle:
 *   1. All providers' register() methods are called in order.
 *   2. All providers' boot() methods are called in order.
 *
 * This two-phase approach guarantees that by the time boot() runs, every
 * service registered by every other provider is already available in the
 * registry — regardless of provider registration order.
 *
 * Example (third-party package):
 *
 *   class PaymentRelayProvider extends RelayProvider
 *   {
 *       public function register(): void
 *       {
 *           $this->registry->singleton('payment', PaymentGateway::class);
 *       }
 *
 *       public function boot(): void
 *       {
 *           $key = $this->registry->make('config')->get('payment.key');
 *           $this->registry->make('payment')->setApiKey($key);
 *       }
 *   }
 */
abstract class RelayProvider
{
    public function __construct(
        protected RelayRegistry $registry
    ) {}

    /**
     * Register bindings into the registry.
     *
     * ONLY call registry->bind(), ->singleton(), or ->instance() here.
     * Do NOT call registry->make() — other providers may not have registered
     * their services yet.
     */
    abstract public function register(): void;

    /**
     * Bootstrap any post-registration logic.
     *
     * Called after ALL providers have run register(). Safe to call
     * registry->make() here. Use for:
     *   - Config-driven setup
     *   - Extending other services
     *   - Registering middleware or routes
     *   - Attaching event listeners
     */
    public function boot(): void
    {
        // Optional — override if needed
    }
}
