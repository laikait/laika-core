<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Route;

use Laika\Core\Exceptions\Handler as ErrorHandler;
use Laika\Core\Helper\Url as UrlHelper;
use Laika\Core\System\MemoryManager;
use Laika\Core\Helper\Directory;
use Laika\Core\Http\Request;
use Laika\Core\Http\Response;
use Laika\Core\Helper\Config;
use Laika\Core\Helper\Token;

class Dispatcher
{
    public static function dispatch(): void
    {
        // Pre Dispatch Tasks
        self::PreDispatcher();

        // Get Request Url
        $requestUrl = Url::normalize(call_user_func([new UrlHelper, 'path']));

        // Get If Request Uri Matched With Router List
        $res = Url::matchRequestRoute($requestUrl);

        // Get Parameters
        $params = $res['params'];

        // Check URL is for Web
        $asset = new Asset();
        $isWebUrl = !str_starts_with($res['route'] ?? '', $asset->app) && !str_starts_with($res['route'] ?? '', $asset->template);

        // FIX 1: Execute 404 check BEFORE RegisterInitiators().
        // When route is null, $isWebUrl is always true ('' does not start with asset prefixes),
        // so without this reorder, DB/session/hooks boot on every 404 request unnecessarily.
        if ($res['route'] === null) {

            // 404 Response
            call_user_func([new Response, 'code'], 404);

            $fallbacks = Handler::getFallbacks();

            uksort($fallbacks, fn($a, $b) => strlen($b) - strlen($a));
            foreach ($fallbacks as $key => $fallback) {
                if (\str_starts_with(Url::normalizeFallbackKey($requestUrl), $key)) {
                    $request  = new Request;
                    $response = new Response;
                    try {
                        echo Invoke::middleware(
                            $fallback['middlewares'],
                            empty($fallback['controller']) ? function () { return _404::show(); } : $fallback['controller'],
                            $params,
                            $request,
                            $response
                        );
                    } catch (\Throwable $e) {
                        \report_bug($e);
                    }
                    return;
                }
            }
            echo _404::show();
            return;
        }

        // Register DB, Session, Hooks — only for valid web routes
        if ($isWebUrl) {
            self::RegisterInitiators();
        }

        // Get Matched Route Info
        $routes = Handler::getRoutes(Url::method());
        $route = $routes[$res['route']];

        // FIX 2: echo the output for asset routes — return value was silently discarded before.
        if (!$isWebUrl) {
            echo Invoke::middleware([], $route['controller'], $params);
            return;
        }

        // Collect middlewares in order: global → group → route
        $middlewares = \array_merge(
            $route['middlewares']['global'],
            $route['middlewares']['group'],
            $route['middlewares']['route']
        );

        // Run Middlewares -> Controller
        $request  = new Request;
        $response = new Response;

        try {
            $output = Invoke::middleware($middlewares, $route['controller'], $params, $request, $response);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // Run Afterwares
        $afterwares = \array_merge(
            $route['afterwares']['global'],
            $route['afterwares']['group'],
            $route['afterwares']['route']
        );

        try {
            echo empty($afterwares) ? $output : Invoke::afterware($afterwares, $output, $params, $request, $response);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
        return;
    }

    /*================================= PRIVATE API =================================*/

    /**
     * Set framework request headers
     * @return void
     */
    private static function RegisterHeaders(): void
    {
        $token   = new Token();
        $headers = [
            "Request-Time"  =>  do_hook('config.env', 'start.time', time()),
            "App-Name"      =>  do_hook('config.app', 'name', 'Laika Framework'),
            "Authorization" =>  $token->generate([
            'uid'           =>  mt_rand(100001, 999999),
            'requestor'     =>  call_user_func([new UrlHelper, 'base'])
            ])
        ];
        call_user_func([new Response, 'setHeader'], $headers);
        return;
    }

    /**
     * Create required application directories
     * @return void
     */
    private static function CreateDirectories(): void
    {
        $dirs = [
            APP_PATH . '/lf-app/Controller',
            APP_PATH . '/lf-app/Model',
            APP_PATH . '/lf-app/Middleware',
            APP_PATH . '/lf-app/Afterware',
            APP_PATH . '/lf-app/Migration'
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new \RuntimeException("Failed to Create Directory: {$dir}");
                }
            }
        }
        return;
    }

    /**
     * Create secret key config file if it does not exist
     * @return void
     */
    private static function CreateSecretKey(): void
    {
        if (!Config::has('secret')) {
            Config::create('secret', ['key' => bin2hex(random_bytes(64))]);
        }

        if (!Config::has('secret', 'key')) {
            Config::set('secret', 'key', bin2hex(random_bytes(64)));
        }
        return;
    }

    /**
     * Load hook files from lf-hooks directory
     * @return void
     */
    private static function LoadHookFiles(): void
    {
        $hooks_path = APP_PATH . '/lf-hooks';

        Directory::make($hooks_path);

        $hook_files = Directory::files($hooks_path, '.hook.php');
        foreach ($hook_files as $hook_file) {
            require $hook_file;
        }
    }

    /**
     * Run required tasks before dispatching
     * @return void
     */
    private static function PreDispatcher(): void
    {
        // Register Error Handler
        ErrorHandler::register();

        // Apply memory limits. monitor() is intentionally called with no arguments
        // (silent / production-safe). To opt in to logging, change to:
        //   $manager->monitor(enabled: true);               — uses error_log fallback
        //   $manager->monitor(logger: fn($mb, $b) => ...);  — custom logger
        $manager = new MemoryManager();
        $manager->apply();
        $manager->monitor();

        // Create Secret Key
        self::CreateSecretKey();

        // Create Required Directories
        self::CreateDirectories();

        // FIX 5: Remove trailing comma from call_user_func (was a typo).
        call_user_func([new Response, 'register']);

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
    private static function RegisterInitiators(): void
    {
        // Register Headers
        self::RegisterHeaders();
        // Load Hook Files
        self::LoadHookFiles();
    }
}
