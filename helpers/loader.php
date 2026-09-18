<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Laika\Relay\Relay;
use Laika\Relay\RelayRegistry;
use Laika\Relay\CoreProviders;
use Laika\Relay\ProviderRegistry;
use Laika\Relay\RelayProvider;
use Laika\Core\App\Resource;
use Laika\Core\System\MemoryManager;
use Laika\Route\Invoke;

// Define APP Path
defined('APP_PATH') || define('APP_PATH', realpath(__DIR__ . '/../../../../'));

// Define DEBUG
defined('DEBUG') || define('DEBUG', true);

// Define Directory Separator
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Defiene Storage Paht
defined('STORAGE_PATH') || define('STORAGE_PATH', APP_PATH . DS . 'lf-storage');

// Define Template Path
defined('TEMPLATE_PATH') || define('TEMPLATE_PATH', APP_PATH . DS . 'template');

// Define Template Cache Path
defined('TEMPLATE_CACHE_PATH') || define('TEMPLATE_CACHE_PATH', STORAGE_PATH . DS . 'cache' . DS . 'template');

// Define Config Path
defined('CONFIG_PATH') || define('CONFIG_PATH', APP_PATH . DS . 'lf-config');

// Define Language Path
defined('LANG_PATH') || define('LANG_PATH', APP_PATH . DS . 'lf-lang');

// Apply lf-inc/const.php's MEMORY_LIMIT (web) or CLI_MEMORY_LIMIT (CLI). It only
// ever lowers memory_limit. Only the queue worker used to apply it, so
// MEMORY_LIMIT had no effect on web requests.
(new MemoryManager())->apply();

####################################################################################
/*--------------------------------- RELAY LOADER ---------------------------------*/
####################################################################################

// Get Relay Registry Object
$registry = new RelayRegistry();
$providers = new ProviderRegistry($registry);

// Register Core Services
$providers->register(CoreProviders::class);

// Register The Cache
//
// laika-cache ships its own provider and declares it as a `relays` resource, so
// once installed through Composer auto-discovery below finds it too. Registering
// it here as well makes the cache a core service regardless of discovery, and is
// harmless alongside it: ProviderRegistry de-duplicates by class name. It comes
// before discovery so an application provider binding 'cache' still wins.
if (class_exists(\Laika\Cache\Relay\CacheRelay::class)) {
    $providers->register(\Laika\Cache\Relay\CacheRelay::class);
}

// Auto Discover Relay Providers
//
// Packages and the application both declare a `relays` resource - a directory of
// RelayProvider classes. Packages are registered first so an application provider
// can still override a package binding: RelayRegistry::singleton() is
// last-write-wins, and Resource seeds framework defaults before packages.
$packageRelays = [];
$appRelays = [];

foreach (Resource::definitions('relays') as $definition) {
    if (in_array($definition->source, ['default', 'app'], true)) {
        $appRelays[] = $definition;
    } else {
        $packageRelays[] = $definition;
    }
}

foreach (array_merge($packageRelays, $appRelays) as $definition) {
    foreach (Resource::entries($definition) as $className) {
        // Tolerant on purpose: the shipped lf-app/Relay/Example.php stub is fully
        // commented out, and this runs before Handler::register() below - a throw
        // here would be an uncatchable fatal with no error page.
        if (class_exists($className) && is_subclass_of($className, RelayProvider::class)) {
            $providers->register($className);
        }
    }
}

// Wire Registry
Relay::setRegistry($registry);

// Wire The Router To The Container
//
// laika-route has no container of its own - it requires nothing but PHP. This is
// the single line that joins the two, and it lives here because laika-core is the
// package that already requires both. Pipelines, filters and controllers are built
// through RelayRegistry::make() from here on, so their constructor dependencies are
// auto-wired; without this call the router falls back to a plain `new`.
Invoke::setResolver(static fn(string $class): object => $registry->make($class));

// Query Result Caching
//
// Off unless lf-config/cache.php turns it on, and even then a query is only
// cached when it calls ->remember(). laika-model does not require laika-cache,
// so the store is handed over as a resolver: nothing is built or connected
// until a remembered query runs. Guarded like the worker hook below, so an
// older laika-model without the method cannot take the site down.
try {
    $queryCache = (array) (\Laika\Core\Helper\Config::get('cache', 'query') ?? []);
} catch (\Throwable) {
    $queryCache = [];
}

if (!empty($queryCache['enabled']) && method_exists(\Laika\Model\Model::class, 'setQueryCache')) {
    \Laika\Model\Model::setQueryCache(
        static fn (): object => \Laika\Service\Cache::driver(),
        (int) ($queryCache['ttl'] ?? 60)
    );
}
unset($queryCache);

// Reset Per-Process State Between Queue Jobs
//
// The same shape as the line above: laika-queue requires nothing, so it cannot
// know what the framework memoises. Without pcntl it runs every job in one
// long-lived process, and config, options and the rest would otherwise stay as
// the first job left them for the life of the worker.
//
// Guarded: this file runs on every request, and a laika-queue older than the
// hook would otherwise turn the whole site into a fatal error.
if (method_exists(\Laika\Queue\Worker::class, 'beforeJob')) {
    \Laika\Queue\Worker::beforeJob(static function (): void {
        foreach (\Laika\Core\System\ProcessState::reset() as $failure) {
            fwrite(STDERR, "[laika] process reset: {$failure}\n");
        }
    });
}

// Boot Providers
$providers->boot();

// Relay providers are the only resource read during autoload. Every other resource
// stays lazy: packages - this one included - declare them in composer.json under
// extra.laika.resources, and Resource discovers them from
// vendor/composer/installed.json on first use.
