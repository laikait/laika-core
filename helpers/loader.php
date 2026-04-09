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

// Providers From Config
$path = realpath(__DIR__ . '../../../../../lf-config/providers.php');
if ($path) {
    $configProviders = require $path;
    if (is_array($configProviders)) {
        foreach ($configProviders as $provider) {
            $providers->register($provider);
        }
    }
}


// Register App Services


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
