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

// use Laika\Core\Exceptions\Handler as ErrorHandler;
use Laika\Core\Relay\Relays\Url as UrlHelper;
use Laika\Core\System\MemoryManager;
use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\Header;
use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\Token;
use Laika\Core\Relay\Relays\Csrf;

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
        $isWebUrl = !str_starts_with($res['route'] ?? '', $asset->app) && !str_starts_with($res['route'] ?? '', $asset->template);

        // When route is null, $isWebUrl is always true ('' does not start with asset prefixes),
        // so without this reorder, DB/session/hooks boot on every 404 request unnecessarily.
        if ($res['route'] === null) {
            self::handleFallback($requestUrl, $params);
            return;
        }

        // Register DB, Session, Hooks — only for valid web routes
        if ($isWebUrl) {
            self::registerInitiators();
        }

        // Get Matched Route Info
        $routes = Handler::getRoutes(Url::method());
        $route = $routes[$res['route']];

        // echo the output for asset routes — return value was silently discarded before.
        if (!$isWebUrl) {
            echo Invoke::middleware([], $route['controller'], $params);
            return;
        }

        // Collect middlewares in order: global → group → route
        $middlewares = array_merge(
            $route['middlewares']['global'],
            $route['middlewares']['group'],
            $route['middlewares']['route']
        );

        try {
            $output = Invoke::middleware($middlewares, $route['controller'], $params);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // Run Afterwares
        $afterwares = array_merge(
            $route['afterwares']['global'],
            $route['afterwares']['group'],
            $route['afterwares']['route']
        );

        try {
            echo Invoke::afterware($afterwares, $output, $params);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
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
        Header::code(404);

        $fallbacks = Handler::getFallbacks();

        uksort($fallbacks, fn($a, $b) => strlen($b) - strlen($a));
        foreach ($fallbacks as $key => $fallback) {
            if (str_starts_with(Url::normalizeFallbackKey($requestUrl), $key)) {
                try {
                    echo Invoke::middleware(
                        $fallback['middlewares'],
                        empty($fallback['controller']) ? function () { return _404::show(); } : $fallback['controller'],
                        $params
                    );
                } catch (\Throwable $e) {
                    throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
                }
                return;
            }
        }
        echo _404::show();
        return;
    }

    /**
     * Set framework request headers
     * @return void
     */
    private static function registerHeaders(): void
    {
        $headers = [
            "Request-Time"  =>  (int) Config::get('env', 'start.time', time()),
            "App-Name"      =>  Config::get('app', 'name', 'Laika Framework'),
            "Authorization" =>  Token::generate([
                    'uid' =>  mt_rand(100001, 999999),
                    'requestor' =>  UrlHelper::base()
                ])
        ];
        Header::set($headers);
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
        // Apply memory limits. monitor() is intentionally called with no arguments
        // (silent / production-safe). To opt in to logging, change to:
        //   $manager->monitor(enabled: true);               — uses error_log fallback
        //   $manager->monitor(logger: fn($mb, $b) => ...);  — custom logger
        $manager = new MemoryManager();
        $manager->apply();
        $manager->monitor();

        // Set Default Headers
        Header::register();

        // Load Routes
        Url::LoadRoutes();

        // Load App & Template Asset Routes
        call_user_func([new Asset(), 'registerAssetRoute']);
        return;
    }

    /**
     * Register Initiators
     * @return void
     */
    private static function registerInitiators(): void
    {
        // Register Headers
        self::registerHeaders();
        // Load Hook Files
        self::loadHookFiles();
    }
}
