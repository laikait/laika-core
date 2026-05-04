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

use Laika\Core\App\Router;
use Laika\Core\Service\Hook;
use Laika\Session\Relay\Session;
use Laika\Core\Relay\Relays\Url;
use Laika\Core\Relay\Relays\Csrf;
use Laika\Core\Exceptions\Handler;
use Laika\Core\Relay\Relays\Header;
use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\Request;
use Laika\Core\Service\Template\Meta;
use Laika\Core\Service\Template\Asset;
use Laika\Core\Exceptions\HttpException;

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
 * @return mixed
*/
function do_hook(string $filter, mixed ...$args): mixed
{
    return Hook::do($filter, ...$args);
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

// /**
//  * Register An Action.
//  * @param string   $action   Action name.
//  * @param callable $callback The function to execute.
//  * @param int      $priority Priority for execution (lower runs first).
//  * @return void
//  */
// function add_action(string $action, callable $callback, int $priority = 10): void
// {
//     Filter::add_action($action, $callback, $priority);
// }

// /**
//  * Apply All Actions
//  * @param string $action Action name.
//  * @param mixed  ...$args Additional arguments to pass to callbacks.
//  * @return mixed
//  */
// function do_action(string $action, mixed ...$args): mixed
// {
//     return Filter::apply_action($action, ...$args);
// }

// /**
//  * Get Filter Info
//  * @param ?string $hook Hook Name. Default is null.
//  * @return array
// */
// function hooks(?string $hook = null): array
// {
//     return Filter::filters($hook);
// }

// /**
//  * Get Actions
//  * @param ?string $action Action Name. Default is null.
//  * @return array
// */
// function actions(?string $action = null): array
// {
//     return Filter::actions($action);
// }

/**
 * Get Named Route
 * @param string $name Named Route Name. Example: 'client' or 'client?status=active'
 * @param array $params Named Route Parameters. Example: ['id'=>1234]
 * @param bool $url Return as Url or Slug. Default is false
 * @return string
 */
function named(string $name, array $params = [], bool $url = false): string
{
    // Get Slug
    $named = parse_url($name, PHP_URL_PATH);
    // Get Query String
    $qstring = parse_url($name, PHP_URL_QUERY);
    // Make Named Path
    $path = trim(Router::url($named, $params), '/');
    $path = $qstring ? "{$path}?{$qstring}" : $path;
    // Return Named Path/URL
    return $url ? rtrim(Url::base(), '/') . "/{$path}" : $path;
}

/**
 * Throw Exception and Abort
 * @param int $code Error Code. Default is 500
 * @param ?string $message Error Message
 * @return void
 */
function http_exception(int $code = 500, ?string $message = null): void
{
    $message = $message ?: (Header::statusCodes()[$code] ?? 'Unknown Error!');
    throw new HttpException($code, $message);
}

/**
 * Report Error
 * @return void
 */
function report_bug(Throwable $th): void
{
    $handler = new Handler();
    $handler->handle($th);
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
 * Get Mime Type Name
 * @return string
 */
function guess_mime_from_name(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $map = [
        // Documents
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'rtf'  => 'application/rtf',
        // Images
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        // Archives
        'zip'  => 'application/zip',
        'gz'   => 'application/gzip',
        'tar'  => 'application/x-tar',
        'rar'  => 'application/vnd.rar',
        '7z'   => 'application/x-7z-compressed',
        // Data
        'json' => 'application/json',
        'xml'  => 'application/xml',
        // Audio / Video
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
    ];

    return $map[$ext] ?? 'application/octet-stream';
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
function asset_src(string $path): void
{
    if(parse_url($path, PHP_URL_HOST)){
        echo $path;
    }
    $path = trim($path, '/.');
    echo named('asset.src', ['path' => $path], true);
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
    echo Csrf::field();
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