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

use Laika\Core\App\Route\Asset;

####################################################################
/*------------------------- APP FILTERS --------------------------*/
####################################################################
// App Host
add_hook('app.host', function(): string
{
    return rtrim(host(), '/') . '/';
});

// App Name
add_hook('app.name', function(){
    return option('app.name', do_hook('config.app', 'name', 'Laika Framework!'));
});

/**
 * App Logo
 * @param ?string $option_key opt_ken column value in Database options Table
 * @return string
 */
add_hook('app.logo', function(?string $option_key = null): string {
    $name = option($option_key ?? '') ?: null;
    $logo = $name ?: 'logo.png';
    return do_hook('app.host') . "resource/img/{$logo}";
});

/**
 * App Icon
 * @param ?string $option_key opt_ken column value in Database options Table
 * @return string
 */
add_hook('app.icon', function(?string $option_key = null): string {
    $name = option($option_key) ?: null;
    $icon = $name ?: 'favicon.ico';
    return do_hook('app.host') . "resource/img/{$icon}";
});

/**
 * Local Language
 * @param string $property Property of LANG Class
 * @param array ...$args Other Parameters for sprintf()
 * @return string
 */
add_hook('app.local', function(string $property, ...$args): string {
    // Return if Class Doesn't Exists
    if(!class_exists('LANG')) {
        throw new RuntimeException("'LANG' Class Doesn't Exists!");
    }
    // Return if Class Exists
    return sprintf(LANG::$$property ?? 'Local Property Does Not Exists!', ...$args);
});

// Load App Asset
add_hook('app.asset', function(string $file): string {
    if(parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = trim($file, '/');
    $slug = trim(Asset::$app, '/');
    return do_hook('app.host') . "{$slug}/{$file}";
});