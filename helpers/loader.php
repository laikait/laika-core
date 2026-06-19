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

// Define APP_PATH
if (!defined('APP_PATH')) define('APP_PATH', realpath(__DIR__ . '/../../../../'));

// Define DEBUG
if (!defined('DEBUG')) define('DEBUG', true);

####################################################################################
/*--------------------------------- RELAY LOADER ---------------------------------*/
####################################################################################

// Get Relay Registry Object
$registry = new RelayRegistry();
$providers = new ProviderRegistry($registry);

// Register Core Services
$providers->register(CoreServiceProvider::class);

if (class_exists(Provider::class)) {
    foreach (Provider::instance()->services() as $service) {
        $providers->register($service);
    }
}

// Auto Discover App Providers
$appProviderDir = APP_PATH . '/lf-app/Service';
if ($appProviderDir && is_dir($appProviderDir)) {
    $appProviderFiles = glob("{$appProviderDir}/*.php");
    foreach($appProviderFiles as $file) {
        $className = 'App\\Relay\\' . basename($file, '.php');
        if (class_exists($className)) {
            $providers->register($className);
        }
    }
}

// Wire Registry
Relay::setRegistry($registry);

// Boot Providers
$providers->boot();
