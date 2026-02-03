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

use Laika\Core\Http\Response;
use Laika\Core\Http\Request;
use Laika\Core\Helper\CSRF;
use Laika\Core\Helper\Url;
use Laika\Session\Session;

/*=================================== URL HOOKS ===================================*/
/**
 * Get Host Path
 * @return string Example: http://example.com/ or http://example.com/path/
*/
add_hook('app.host', function(): string
{
    $host = call_user_func([new Url, 'base']);
    return \rtrim($host, '/') . '/';
}, 1000);
/*=================================== ASSET HOOKS ===================================*/
/**
 * Load App Asset
 * @param string $file
 */
add_hook('app.asset', function(string $file): string {
    if(\parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = \trim($file, '/');
    return \named('app.src', ['name' => $file], true);
}, 1000);

/**
 * Load Template Asset
 * @param string $file
 */
add_hook('tpl.asset', function(string $file): string {
    if(parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = trim($file, '/');
    return named('tpl.src', ['name' => $file], true);
}, 1000);

/**
 * Local Language
 * @param string $property Property of LANG Class
 * @param array ...$args Other Parameters for sprintf()
 * @return string
 */
add_hook('app.local', function(string $property, ...$args): string {
    // Return if Class Doesn't Exists
    if(!\class_exists('LANG')) {
        throw new RuntimeException("'LANG' Class Doesn't Exists!");
    }
    // Return if Class Exists
    if (!LANG::$$property) {
        throw new InvalidArgumentException("Invalid Language Property: [$property]");
    }
    return \sprintf(LANG::$$property, ...$args);
}, 1000);

/*================================== CONFIG HOOKS ==================================*/
/**
 * App Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.app', function(?string $key = null, mixed $default = null): mixed{
    return config('app', $key, $default);
}, 1000);

/**
 * Env Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.env', function(?string $key = null, mixed $default = null): mixed{
    return config('env', $key, $default);
}, 1000);

/**
 * Database Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.database', function(?string $key = null, mixed $default = null): mixed{
    return config('database', $key, $default);
}, 1000);

/**
 * Mail Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.mail', function(?string $key = null, mixed $default = null): mixed{
    return config('mail', $key, $default);
}, 1000);

/**
 * Database Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.memcached', function(?string $key = null, mixed $default = null): mixed{
    return config('memcached', $key, $default);
}, 1000);

/**
 * Database Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.redis', function(?string $key = null, mixed $default = null): mixed{
    return config('redis', $key, $default);
}, 1000);

/**
 * Database Config
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 */
add_hook('config.secret', function(?string $key = 'key', mixed $default = null): mixed{
    return config('secret', $key, $default);
}, 1000);

/*================================== CSRF HOOKS ==================================*/
/**
 * CSRF Token HTL Field
 * @param ?string $for Default is null. Example: 'app' or 'admin'
 * @return string
 */
add_hook('csrf.field', function (?string $for = null): string{
    return call_user_func([new CSRF(for:$for), 'field']);
}, 1000);

/*================================== MESSAGE HOOKS ==================================*/
/**
 * Set Notification Message
 * @param string $message Message to Set
 * @param bool $status Warning or Success. true for Success & false for Warning
 */
add_hook('message.set', function(string $message, bool $status): void {
    Session::set('message', ['info'=>$message,'status'=>$status]);
    return;
}, 1000);

/**
 * Get Notification Message
 */
add_hook('message.show', function(): array {
    $message = Session::get('message');
    Session::pop('message');
    return $message ?: [];
}, 1000);

/*================================== PAGE HOOKS ==================================*/
/**
 * Page Title
 * @param string $title
 */
add_hook('page.title', function(string $title): string {
    return "{$title} | " . do_hook('app.name');
}, 1000);

/**
 * Page Number
 */
add_hook('page.number', function(): int {
    $number = (int) do_hook('request.input', 'page', 1);
    return $number < 1 ? 1 : $number;
}, 1000);

/**
 * Next Page Number
 */
add_hook('page.next', function()
{
    return call_user_func([new Url, 'incrementQuery']);
}, 1000);

/**
 * Previous Page Number
 */
add_hook('page.previous', function()
{
    return call_user_func([new Url, 'decrementQuery']);
}, 1000);

/*================================== REQUEST HOOKS ==================================*/

/**
 * Get Request Header
 * @param string $key
 */
add_hook('request.header', function(string $key): ?string {
    return call_user_func([new Request, 'header'], $key);
}, 1000);

/**
 * Get Request Input Value
 * @param string $key
 * @param mixed $default Default is ''
 */
add_hook('request.input', function(string $key, mixed $default = ''): mixed {
    return call_user_func([new Request, 'input'], $key, $default);
}, 1000);

/**
 * Get Request Values
 */
add_hook('request.inputs', function(): array {
    return call_user_func([new Request, 'inputs']);
}, 1000);

/**
 * Check Method Request is Post/Get/Put/Patch/Delete/Ajax
 * @param string $method
 */
add_hook('request.is', function(string $method): bool {
    $method = strtolower($method);
    switch ($method) {
        case 'post':
            return call_user_func([new Request, 'isPost']);
            break;
        case 'get':
            return call_user_func([new Request, 'isGet']);
            break;
        case 'put':
            return call_user_func([new Request, 'isPut']);
            break;
        case 'patch':
            return call_user_func([new Request, 'isPatch']);
            break;
        case 'delete':
            return call_user_func([new Request, 'isDelete']);
            break;
        case 'ajax':
            return call_user_func([new Request, 'isAjax']);
            break;
        default:
            return false;
            break;
    }
}, 1000);

/*================================== TEMPLATE HOOKS ==================================*/
// Set Template Default JS Vars
add_hook('tpl.scripts', function(): string{
    $authorizarion = call_user_func([new Response, 'get'], 'authorization');
    $appuri = trim(do_hook('app.host'), '/');
    return "<script>let token = '{$authorizarion}'; let appuri = '{$appuri}';</script>\n";
}, 1000);

/*================================== COMMON HOOKS ==================================*/
/**
 * DB Date Format
 */
add_hook('default_date_format', function () {
    return date('Y-m-d H:i:s');
}, 1000);
