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

use Laika\Service\Url;
use Laika\Service\Hook;
use Laika\Service\CSRF;
use Laika\Service\Meta;
use Laika\Route\Handler;
use Laika\Service\Asset;
use Laika\Service\Option;
use Laika\Service\Config;
use Laika\Service\Request;
use Laika\Service\Context;
use Laika\Session\Session;
use Laika\Model\Connection;

/**
 * Dump Data & Die
 * @param mixed $data Data to Dump
 * @param bool $die Default is false
 * @return void
*/
function dd(mixed $data, bool $die = false): void
{
    echo '<pre style="background-color:#000;color:#fff;">';
    var_dump($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Show Data & Die
 * @param mixed $data Data to Show
 * @param bool $die Default is false
 * @return void
*/
function show(mixed $data, bool $die = false): void
{
    echo '<pre style="background-color:#000;color:#fff;">';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Purify Array Values
 * @param array $data Array Data to Purify
 * @return array
 */
function purify(array $data): array
{
    if (empty($data)) {
        return $data;
    }
    return array_map(function($val){
        return match (true) {
            is_array($val) => purify($val),
            is_string($val) => trim((string) $val),
            default => $val
        };
    }, $data);
}

/**
 * Convert Any Value To String.
 * @param mixed $value
 * @return string
 */
function convert_to_string(mixed $value): string
{
    return match (true) {
        is_string($value) => $value,
        is_bool($value) => $value ? 'true' : 'false',
        is_null($value) => '',
        is_scalar($value) => (string) $value,
        default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
    };
}

/**
 * App Name
 * @return string
 */
function app_name(): string
{
    return config('app', 'name', 'Laika Framework');
}


/**
 * Add Hook
 * @param string $filter Filter Name.
 * @param callable $callback Required Argument.
 * @param int $priority Optional Argument. Default is 10
 * @return void
*/
function add_hook(string $filter, callable $callback, int $priority = 10): void
{
    Hook::add($filter, $callback, $priority);
}

/**
 * Do Hook
 * @param string $filter Filter Name.
 * @param mixed ...$args Optional Arguments.
 * @return void
*/
function do_hook(string $filter, mixed ...$args): void
{
    Hook::do($filter, ...$args);
}

/**
 * Apply Hook
 * @param string $filter Filter Name.
 * @param mixed $value Optional Argument. Default is Null.
 * @param mixed ...$args Optional Arguments.
 * @return mixed
*/
function apply_hook(string $filter, mixed $value = null, mixed ...$args): mixed
{
    return Hook::apply($filter, $value, ...$args);
}

/**
 * Get Encrypt Key
 * @return string
 */
function enckey(): string
{
    static $key = null;
    if ($key === null) {
        $parts = explode('-', base64_decode((string) file_get_contents(APP_PATH . '/lf-storage/.key')));
        if (count($parts) != 2) {
            throw new \Exception("Invalid encrypt key detected!");
        }
        $key = $parts[1];
    }
    return $key;
}

/**
 * Get Named Route
 * @param string $name Named Route Name. Example: 'client' or 'client?status=active'
 * @param array $params Named Route Parameters. Example: ['id'=>1234]
 * @return string
 */
function named(string $name, array $params = []): string
{
    // Get Slug
    $named = parse_url($name, PHP_URL_PATH);
    // Get Query String
    $qstring = parse_url($name, PHP_URL_QUERY);
    // Make Named Path
    $path = trim(Handler::namedUrl($named, $params), '/');
    $path = $qstring ? "{$path}?{$qstring}" : $path;
    // Return Named Path/URL
    return Url::base() . $path;
}

/**
 * Config Obejct
 * @param string $name Config Name. Rrequired Argument. Example: app, database etc.
 * @param ?string $key Config Key. Optional Argument. Example: name, version etc.
 * @param mixed $default Default Value if no value found. Optional Argument.
 * @return mixed
 */
function config(string $name, ?string $key = null, mixed $default = null): mixed
{
    return Config::get($name, $key, $default);
}

/**
 * Make Slug From Name
 * @return string
 */
function slugify(string $name): string
{
    $parts = explode('.', $name);
    $name = $parts[0];
    $name = preg_replace('~[^\pL\d]+~u', '-', $name);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $name = preg_replace('~[^-\w]+~', '', $name);
    $name = trim($name, '-');
    $name = preg_replace('~-+~', '-', $name);
    return strtolower($name) ?: 'file-' . uniqid() . '-' . time();
}

/**
 * Get All Timezones
 * @return array
 */
function time_zones(): array
{
    return \DateTimeZone::listIdentifiers(DateTimeZone::ALL);
}

/**
 * Get Repo Directory
 * @param string $name Repository name
 * @return string
 */
function repo_dir(string $name): string
{
    return realpath(APP_PATH . '/vendor/' . trim($name, '/'));
}

#######################################################################################
/*================================== OPTION HANDLE ==================================*/
#######################################################################################
/**
 * Get Option Value
 * @param string $key
 * @param ?string $default
 * @return ?string
 */
function option(string $key, ?string $default = null): string
{
    static $options = [];
    if (!isset($options[$key])) $options[$key] = Option::single($key, $default);
    return $options[$key];
}

/**
 * Get Option Value as Bool
 * @param string $key
 * @return bool
 */
function option_bool(string $key): bool
{
    return (bool) preg_match('/^true$/i', option($key, 'false'));
}

/**
 * Get Option Value as Int
 * @param string $key
 * @param int $default
 * @return int
 */
function option_int(string $key, int $default = 0): int
{
    $v = option($key, (string) $default);
    if (is_numeric($v)) return (int) $v;
    return $default;
}

/**
 * Get Option Value as Array
 * @param string $key
 * @param array $default
 * @return array
 */
function option_array(string $key, array $default = []): array
{
    $str = option($key, "");
    try {
        $arr = json_decode($str, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($arr)) return $arr;
    } catch (\Throwable $th) {}
    return $default;
}

/**
 * Insert Option
 * @param string $ksy
 * @param mixed $value
 * @return bool
 */
function option_insert(string $key, mixed $value): bool
{
    return Option::insert($key, $value);
}

/**
 * Update Option
 * @param string $ksy
 * @param mixed $value
 * @return bool
 */
function option_update(string $key, mixed $value): bool
{
    return Option::update($key, $value);
}

#######################################################################################
/*================================ REQUEST FUNCTIONS ================================*/
#######################################################################################
/**
 * Check Method Request is Post/Get/Put/Patch/Delete/Ajax
 * @param string $method
 */
function request_is(string $method): bool
{
    return match (strtolower($method)) {
        'post'  =>  Request::isPost(),
        'get'   =>  Request::isGet(),
        'put'   =>  Request::isPut(),
        'patch' =>  Request::isPatch(),
        'delete'=>  Request::isDelete(),
        'ajax'  =>  Request::isAjax(),
        default =>  false
    };
}

/**
 * Request Inputs
 * @return array
 */
function request_inputs()
{
    return Request::inputs();
}

/**
 * Request Input
 * @return array
 */
function request_input(string $key, mixed $default = ''): mixed
{
    return Request::input($key, $default);
}

/**
 * Get Request Header
 * @param string $key
 * @return string
 */
function request_header(string $key): ?string
{
    return Request::header($key);
}

######################################################################################
/*================================= ALERT FUNCTIONS ================================*/
######################################################################################
/**
 * Set Alert Message
 * @param string $message
 * @param bool $status
 * @return void
 */
function alert_set(string $message, bool $status): void
{
    Session::set('alert', ['message' => $message, 'status' => $status]);
}

/**
 * Get Alert Message
 * @return array
 */
function alert_get(): array
{
    $alert = Session::get('alert');
    Session::pop('alert');
    return $alert ?: [];
}

######################################################################################
/*================================= PAGE FUNCTIONS =================================*/
######################################################################################
/**
 * Page Title
 * @param string $title
 * @return string
 */
function page_title(string $title): string
{
    return "{$title} | " . config('app', 'name', 'Laika Framework');
}

/**
 * Page Number
 * @return int
 */
function page_number(): int
{
    return max(1, (int) Request::input('page', 1));
}

######################################################################################
/*=============================== TEMPLATE FUNCTIONS ===============================*/
######################################################################################
/**
 * Load Template Asset
 * @param string $path
 * @return void
 */
function asset(string $path): void
{
    if(parse_url($path, PHP_URL_HOST)){
        echo $path;
        return;
    }
    $path = trim($path, '/.');
    echo Url::base() . $path;
}

/**
 * Add Context Data
 * @param string $key
 * @param mixed $value
 * @return void
 */
function context_add(string $key, mixed $value): void
{
    Context::set($key, $value);
}

/**
 * Get Context Data
 * @param ?string $key
 * @param mixed $default
 * @return mixed
 */
function context_get(?string $key = null, mixed $default = null): mixed
{
    return Context::get($key, $default);
}

/**
 * Enqueue Meta
 * @param string $name
 * @param string $content
 * @param string $type Default is 'name'
 * @return void
 */
function enqueue_meta(string $name, string $content, string $type = 'name'): void
{
    Meta::add($name, $content, $type);
}

/**
 * Enqueue Style
 * @param string $handle
 * @param string $src
 * @param string $version
 * @param string $media
 * @return void
 */
function enqueue_style(string $handle, string $src, string $version = '1.0.0', string $media = 'all'): void
{
    Asset::addStyle($handle, $src, $version, $media);
}

/**
 * Print Styles
 * @return void
 */
function print_styles(): void
{
    Asset::printStyles();
}

/**
 * Enqueue Script
 * @param string $handle
 * @param string $src
 * @param string $version
 * @param bool $defer
 * @return void
 */
function enqueue_script(string $handle, string $src, string $version = '1.0.0', bool $defer = false): void
{
    Asset::addScript($handle, $src, $version, $defer);
}

/**
 * Print Metas
 * @return void
 */
function print_metas(): void
{
    Meta::print();
}

/**
 * Print Scripts
 * @return void
 */
function print_scripts(): void
{
    Asset::printScripts();
}

/**
 * Print Framework Head
 * @return void
 */
function lf_header(): void
{
    // Print Metas
    print_metas();

    // Print Styles
    print_styles();

    // Default Scripts
    Asset::headerScripts();
}

/**
 * Print Framework Footer
 * @return void
 */
function lf_footer(): void
{
    // Print Styles
    print_scripts();
}

/**
 * CSRF Token HTL Field
 * @return void
 */
function csrf_field(): void
{
    echo CSRF::field();
};

/**
 * Local Language Value
 * @return string
 */
function local(string $property, ...$args): string
{
    // Return if Class Doesn't Exists
    if(!class_exists('LANG')) {
        throw new RuntimeException("'LANG' Class Doesn't Exists!");
    }
    // Return if Class Exists
    if (!isset(LANG::$$property)) {
        throw new InvalidArgumentException("Invalid Language Property: [$property]");
    }
    return sprintf(LANG::$$property, ...$args);
}

/**
 * App Host
 * @return string
 */
function app_host(): string
{
    return Url::base();
}

/**
 * Match Current Url With Named
 * @param string $named
 * @return bool
 */
function match_url(string $named): bool
{
    return str_starts_with(Url::current(), named($named));
}

/**
 * Make API Data
 * @param bool $success
 * @param int|string $message
 * @param array $data
 * @return array
 */
function response(bool $success, int|string $message, array $data = []): array
{
    return [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
}