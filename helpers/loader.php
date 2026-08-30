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

####################################################################################
/*--------------------------------- RELAY LOADER ---------------------------------*/
####################################################################################

// Get Relay Registry Object
$registry = new RelayRegistry();
$providers = new ProviderRegistry($registry);

// Register Core Services
$providers->register(CoreProviders::class);

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
Invoke::setResolver(static fn (string $class): object => $registry->make($class));

// Boot Providers
$providers->boot();

// Relay providers are the only resource read during autoload. Every other resource
// stays lazy: packages - this one included - declare them in composer.json under
// extra.laika.resources, and Resource discovers them from
// vendor/composer/installed.json on first use.
