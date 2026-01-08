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

use Laika\Core\Helper\Url;
use Laika\Core\App\Router;
use Laika\Core\Helper\Filter;
use Laika\Core\Helper\Config;
use Laika\Core\Http\Response;
use Laika\Core\Exceptions\Handler;
use Laika\Core\Exceptions\HttpException;
use Laika\Core\Api\Response as ApiResponse;

// Dump Data & Die
/**
 * @param mixed $data - Required Argument
 * @param bool $die - Default is false
 * @return void
*/
function dd(mixed $data, bool $die = false): void
{
    echo '<pre style="background-color:#000;color:#fff;">';
    var_dump($data);
    echo '</pre>';
    $die ? die() : $die;
}

// Show Data & Die
/**
 * @param mixed $data - Required Argument
 * @param bool $die - Default is false
 * @return void
*/
function show(mixed $data, bool $die = false): void
{
    echo '<pre style="background-color:#000;color:#fff;">';
    print_r($data);
    echo '</pre>';
    $die ? die() : $die;
}

// Purify Arry Values
/**
 * @param array $data Array Data to Purify
 * @return array
 */
function purify(array $data): array
{
    return array_map(function($val){
        return is_array($val)
            ? purify($val)
            : htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }, $data);
}

// Redirect
/**
 * @param string|array $slug Required Argument
 * @param ?array $params Optional Argument.
 * @return void
*/
function redirect(string|array $slug, ?array $params = null): void
{
    // Redirect if $slug is an URL
    if (parse_url($slug, PHP_URL_HOST)) {
        header('Location:' . $slug, true);
        die();
    }
    // Convert to String if Slug is Array
    if (is_array($slug)) {
        $slug = implode('/', array_map('trim', $slug));
    }
    $slug = str_replace('\\', '/', $slug);
    $slug = trim($slug, '/');

    // Redirect
    header('Location:' . call_user_func([new Url, 'build'], $slug, $params ?: []), true);
    die();
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
 * @return string
 */
function named(string $name, array $params = [], bool $url = false): string
{
    $path = trim(Router::url($name, $params), '/');
    if ($url) {
        return trim(do_hook('app.host'), '/') . "/{$path}";
    }
    return $path;
}

/**
 * Show Date
 * @param int $unixtime
 * @return string
 */
function showdate(int $unixtime): string
{
    return do_hook('date.show', $unixtime);
}

/**
 * Throw Exception and Abort
 * @param int $code Error Code
 * @param ?string $message Error Message
 * @return void
 */
function abort(int $code, ?string $message = null): void
{
    $message = $message ?: (call_user_func([new Response, 'codes'])[$code] ?? 'Unknown Error!');
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
 * API Response
 */
function response()
{
    return new class {
        public function success(array $data = [], string $message = 'Success', int $status = 200, array $meta = []) {
            ApiResponse::success($data, $message, $status, $meta);
        }

        public function error(string $message = 'Error', int $status = 400, array $errors = [], array $meta = []) {
            ApiResponse::error($message, $status, $errors, $meta);
        }
    };
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