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

use Laika\Core\Relay\Relay;
use Laika\Core\Relay\RelayRegistry;
use Laika\Core\Relay\ProviderRegistry;
use Laika\Core\Relay\Providers\CoreServiceProvider;

####################################################################################
/*--------------------------------- RELAY LOADER ---------------------------------*/
####################################################################################

// Get Relay Registry Object
$register = new RelayRegistry();
$providers = new ProviderRegistry($register);

// Register Core Services
$providers->register(CoreServiceProvider::class);

// Auto-discover from installed packages
$autoDiscoverJsonFile = realpath(__DIR__ . '/../../../../vendor/composer/installed.json');
if ($autoDiscoverJsonFile && is_file($autoDiscoverJsonFile)) {
    $installed = json_decode(file_get_contents($autoDiscoverJsonFile), true);
    $installed = $installed['packages'] ?? $installed;

    foreach ($installed as $package) {
        $package = $package['extra']['laika']['providers'] ?? [];
        foreach ($package as $provider) {
            $providers->register($provider);
        }
    }
}

// Auto Discover App Providers
$appProviderDir = realpath(__DIR__ . '/../../../../lf-app/Provider');
if ($appProviderDir && is_file($appProviderDir)) {
    $appProviderFiles = glob("{$appProviderDir}/*.php");
    foreach($appProviderFiles as $file) {
        require $file;
    }
}

// Wire Registry
Relay::setRegistry($register);

// Boot Providers
$providers->boot();

###############################################################################
/*------------------------- FUNCTION & HOOKS LOADER -------------------------*/
###############################################################################

// Require All Functions File
array_map(function($file) { require $file; }, glob(__DIR__ . '/functions/*.func.php'));

// Require All Hooks File
array_map(function($file) { require $file; }, glob(__DIR__ . '/hooks/*.hook.php'));
