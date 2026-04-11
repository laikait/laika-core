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
use Laika\Core\Helper\Filter;
use Laika\Core\Relay\Relays\Url;
use Laika\Core\Relay\Relays\File;
use Laika\Core\Relay\Relays\Header;
use Laika\Core\Exceptions\Handler;
use Laika\Core\Relay\Relays\Config;
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
            is_string($val) => htmlspecialchars(trim(urldecode((string) $val)), ENT_QUOTES, 'UTF-8'),
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
    Filter::add_filter($filter, $callback, $priority);
}

/**
 * Do Hook
 * @param string $filter Filter Name.
 * @param mixed $value Optional Argument. Default is Null.
 * @param mixed ...$args Optional Arguments.
 * @return mixed
*/
function do_hook(string $filter, mixed $value = null, mixed ...$args): mixed
{
    return Filter::apply_filter($filter, $value, ...$args);
}

/**
 * Get Filter Info
 * @param ?string $hook Hook Name. Default is null.
 * @return Array
*/
function hooks(?string $hook = null): mixed
{
    return Filter::filter_info($hook);
}

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