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

namespace Laika\Core\Route;

use Laika\Core\System\MemoryManager;
use Laika\Core\Service\{Directory, Config, Token, Csrf, Date, Activity, Response, Url as UrlHelper};

class Dispatcher
{
    public static function dispatch(): void
    {
        // Pre Dispatch Tasks
        self::preDispatcher();

        // Get Request Url
        $requestUrl = Url::normalize(UrlHelper::path());

        // Get If Request Uri Matched With Router List
        $res = Url::matchRequestRoute($requestUrl);

        // Get Parameters
        $params = $res['params'];

        // Check URL is for Web
        $asset = new Asset();
        $isWebUrl = !str_starts_with((string) $res['route'], $asset->path);

        // When route is null, $isWebUrl is always true ('' does not start with asset prefixes),
        if ($res['route'] === null) {
            self::handleFallback($requestUrl, $params);
            return;
        }

        // Register Headers & Hooks
        if ($isWebUrl) self::registerHeaders();

        // Get Matched Route Info
        $routes = Router::getRoutes(Url::method());
        $route = $routes[$res['route']];

        // echo the output for asset routes — return value was silently discarded before.
        if (!$isWebUrl) {
            [$output, $params] = Invoke::middleware([], $route['controller'], $params);
            return;
        }

        // Collect middlewares in order: global → group → route
        $middlewares = array_merge(
            $route['middlewares']['global'],
            $route['middlewares']['group'],
            $route['middlewares']['route']
        );

        try {
            [$output, $params] = Invoke::middleware($middlewares, $route['controller'], $params);
        } catch (\Throwable $e) {
            report_error($e);
        }

        // Run Afterwares
        $afterwares = array_merge(
            $route['afterwares']['global'],
            $route['afterwares']['group'],
            $route['afterwares']['route']
        );

        try {
            $str = Invoke::afterware($afterwares, $output, $params);
            // Send Response
            Response::body($str)->send();
        } catch (\Throwable $e) {
            report_error($e);
        }
        return;
    }

    /*================================= PRIVATE API =================================*/

    /**
     * Handle Fallback
     * @return void
     */
    private static function handleFallback(string $requestUrl, array $params): void
    {
        // 404 Response
        // Header::code(404);
        Response::status(404);

        $fallbacks = Router::getFallbacks();

        if (empty($fallbacks)) {
            Response::body(_404::show())->send();
            return;
        }

        uksort($fallbacks, fn($a, $b) => strlen($b) - strlen($a));

        $normalizedUrl = Url::normalizeFallbackKey($requestUrl);

        foreach ($fallbacks as $key => $fallback) {
            if (!str_starts_with($normalizedUrl, $key)) {
                continue;
            }

            if ($fallback['controller'] === null) {
                Response::body(_404::show())->send();
                return;
            }

            try {
                [$output, $params] = Invoke::middleware($fallback['middlewares'], $fallback['controller'], $params);
            } catch (\Throwable $e) {
                report_error($e);
            }

            try {
                $str = Invoke::afterware($fallback['afterwares'], $output, $params);
                Response::body($str)->send();
            } catch (\Throwable $e) {
                report_error($e);
            }

            return;
        }

        Response::body(_404::show())->send();
    }

    /**
     * Set framework request headers
     * @return void
     */
    private static function registerHeaders(): void
    {
        $headers = [
            "Request-Time"  =>  (int) Config::get('env', 'start_time', time()),
            "App-Name"      =>  Config::get('app', 'name', 'Laika Framework'),
            "Authorization" =>  Token::generate([
                    'uid' =>  mt_rand(100001, 999999),
                    'requestor' =>  UrlHelper::base()
                ])
        ];
        Response::setHeaders($headers);
        Csrf::generate();
        return;
    }

    /**
     * Load hook files from lf-hooks directory
     * @return void
     */
    private static function loadHookFiles(): void
    {
        $hooks_path = APP_PATH . '/lf-hooks';

        // $dir_instance = new Directory();
        Directory::make($hooks_path);
        $hook_files = Directory::files($hooks_path, 'hook.php');
        foreach ($hook_files as $hook_file) {
            require $hook_file;
        }
    }

    /**
     * Run required tasks before dispatching
     * @return void
     */
    private static function preDispatcher(): void
    {
        // Register Timezone
        Date::setAppTimezone('UTC');

        // Apply memory limits. monitor() is intentionally called with no arguments
        // (silent / production-safe). To opt in to logging, change to:
        // $manager->monitor(enabled: true);               — uses error_log fallback
        // $manager->monitor(logger: fn($mb, $b) => ...);  — custom logger
        $manager = new MemoryManager();
        $manager->apply();
        $manager->monitor();

        // Load Routes
        Url::LoadRoutes();

        // Load Template Asset Routes
        (new Asset())->registerAssetRoute();
        return;
    }
}
