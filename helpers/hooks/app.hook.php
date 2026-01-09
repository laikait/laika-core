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

// use Laika\Core\App\Route\Asset;
use Laika\Core\Helper\Config;
use Laika\Core\Helper\Option;
use Laika\Core\Helper\Url;

####################################################################
/*------------------------- APP FILTERS --------------------------*/
####################################################################
/**
 * Get App DB Option
 * @param string $key DB Option lkey Name
 * @param string $default Option Default Value
 * @return string
 */
add_hook('option', function(string $key, string $default = ''){
    return Option::get($key, Config::get('env', $key, $default));
}, 1000);

/**
 * Get App DB Option as Bool
 * @param string $key DB Option lkey Name
 * @return bool
 */
add_hook('option.bool', function(string $key){
    $value = do_hook('option', $key, false);
    return is_bool($value) ? $value : (bool) preg_match('/^(yes|enable|true|on|1)$/i', $value);
}, 1000);

/**
 * Get Host Path
 * @return string Example: http://example.com/ or http://example.com/path/
 */
add_hook('app.host', function(): string
{
    $host = do_hook('option', 'app.host', call_user_func([new Url, 'base']));
    return rtrim($host, '/') . '/';
}, 1000);

/**
 * Get App Name
 * @return string
 */
add_hook('app.name', function(){
    return do_hook('option', 'app.name', do_hook('config.app', 'name', 'Laika Framework'));
}, 1000);

/**
 * App Logo
 * @param ?string $key Option Table lkey column. Example: admin.logo app.logo
 * @return string
 */
add_hook('app.logo', function(?string $key = null): string {
    $name = do_hook('option', $key ?: 'app.logo', 'logo.png');
    return named('app.src', ['name'=>"/img/{$name}"], true);
}, 1000);

/**
 * App Icon
 * @param ?string $key opt_ken column value in Database options Table
 * @return string
 */
add_hook('app.icon', function(?string $key = null): string {
    $name = do_hook('option', $key ?: 'app.icon', 'favicon.ico');
    return named('app.src', ['name'=>"/img/{$name}"], true);
}, 1000);

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
    if (!LANG::$$property) {
        throw new InvalidArgumentException("Invalid Language Property: [$property]");
    }
    return sprintf(LANG::$$property, ...$args);
}, 1000);

// Load App Asset
add_hook('app.asset', function(string $file): string {
    if(parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = trim($file, '/');
    return named('app.src', ['name' => $file], true);
});