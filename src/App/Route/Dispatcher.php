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

namespace Laika\Core\App\Route;

use Laika\Core\Exceptions\Handler as ErrorHandler;
use Laika\Core\Helper\Url as UriHelper;
use Laika\Core\Helper\Directory;
use Laika\Core\Helper\Connect;
use Laika\Core\Http\Response;
use Laika\Core\Helper\Config;
use Laika\Core\Helper\Client;
use Laika\Core\Helper\Token;
use Laika\Core\App\Env;
use RuntimeException;

class Dispatcher
{
    public static function dispatch(): void
    {
        // Pre Dispatch Tasks
        self::PreDispatcher();

        // Get Request Url
        $requestUrl = Url::normalize(call_user_func([new UriHelper, 'path']));
        
        // Get If Request Uri Matched With Router List
        $res = Url::matchRequestRoute($requestUrl);

        // Get Parameters
        $params = $res['params'];

        // Check URL is for Web
        $isWebUrl = !\str_starts_with($res['route'] ?? '', Asset::$app) && !\str_starts_with($res['route'] ?? '', Asset::$template);
        
        // Add Additional Headers if Not Resource Route
        if ($isWebUrl) {
                // Register Additional Headers
                self::RegisterAdditionalHeaders();
                // Register DB, Session, Timezone 
                self::RegisterInitiators();
        }

        // Execute Fallback For Invalid Route
        if ($res['route'] === null) {

            // 404 Response
            \call_user_func([new Response, 'code'], 404);

            $fallbacks = Handler::getFallbacks();

            foreach (\array_reverse($fallbacks) as $key => $callable){
                if (\str_starts_with(Url::normalizeFallbackKey($requestUrl), $key)) {
                    try {
                        echo Invoke::controller($callable, $params);
                    } catch (\Throwable $e) {
                        \report_bug($e);
                    }
                    return;
                }
            }
            /*---- Execute Fallback ----*/
            try {
                echo _404::show();
            } catch (\Throwable $e) {
                \report_bug($e);
            }
            return;
        }

        // Get Matched Route Info
        $routes = Handler::getRoutes(Url::method());
        $route = $routes[$res['route']];

        // Collect before middlewares in order
        $middlewares = \array_merge(
            $route['middlewares']['global'],
            $route['middlewares']['group'],
            $route['middlewares']['route']
        );

        // Run Middlewares -> Controller
        try {
            $response = $isWebUrl ? Invoke::middleware($middlewares, $route['controller'], $params) :
                            Invoke::middleware([], $route['controller'], $params);
        } catch (\Throwable $e) {
            \report_bug($e);
        }

        if ($isWebUrl) {
            // Run Afterware
            $afterwares = array_merge(
                $route['afterwares']['global'],
                $route['afterwares']['group'],
                $route['afterwares']['route']
            );

            try {
                echo empty($afterwares) ? $response : Invoke::afterware($afterwares, $response, $params);
            } catch (\Throwable $e) {
                \report_bug($e);
            }
        }
        return;
    }

    /**
     * Connect App
     * @return void
     */
    private static function RegisterAdditionalHeaders(): void
    {
        // Set Headers
        $token = new Token();
        $headers = [
            "Request-Time"  =>  \do_hook('config.env', 'start.time', time()),
            "App-Name"      =>  \do_hook('config.app', 'name', 'Laika Framework'),
            "Authorization" =>  $token->generate([
                'uid'       =>  \mt_rand(100001, 999999),
                'requestor' =>  \call_user_func([new UriHelper, 'base'])
            ])
        ];
        \call_user_func([new Response, 'setHeader'], $headers);
        return;
    }

    /**
     * Create Required Directories
     * @return void
     */
    private static function CreateDirectories(): void
    {
        $dirs = [
            APP_PATH . '/lf-app/Controller',
            APP_PATH . '/lf-app/Model',
            APP_PATH . '/lf-app/Middleware',
            APP_PATH . '/lf-app/Afterware',
        ];

        foreach ($dirs as $dir) {
            if (!\is_dir($dir)) {
                if (!\mkdir($dir, 0755, true)) {
                    throw new RuntimeException("Failed to Create Directory: {$dir}");
                }
            }
        }
        return;
    }

    /**
     * Create Secret Key File if Not Exist
     * @return void
     */
    private static function CreateSecretKey(): void
    {
        /**
         * Create Secret Config File if Not Exist
         */
        if(!Config::has('secret')) {
            Config::create('secret', ['key'=>bin2hex(random_bytes(64))]);
        }
        
        /**
         * Create Secret Key Value Not Exist
         */
        if(!Config::has('secret', 'key')) {
            Config::set('secret', 'key', bin2hex(random_bytes(64)));
        }
        return;
    }

    /**
     * Load Hook Files
     * @return void
     */
    private static function LoadHookFiles(): void
    {
        $hooks_path = APP_PATH . '/lf-hooks';

        // Create Directory if Not Exists
        Directory::make($hooks_path);

        // Load Hook Files
        $hook_files = Directory::files($hooks_path, '.hook.php');
        foreach ($hook_files as $hook_file) {
            require $hook_file;
        }
    }

    /**
     * Do Required Tasks Before Dispatching Route
     * @return void
     */
    private static function PreDispatcher(): void
    {
        // Register Error Handler
        ErrorHandler::register();

        // Create Secret Key
        self::CreateSecretKey();

        // Create Required Directories
        self::CreateDirectories();

        // Register Header
        \call_user_func([new Response, 'register'],);

        // Load Routes
        Url::LoadRoutes();

        // Load App & Template Routes
        Asset::registerAppResource();
        Asset::registerTemplateResource();
        return;
    }

    /**
     * Start Database, Session, Local, Timezone & Load Hook Files
     * @return void
     */
    public static function RegisterInitiators(): void
    {
        /**
         * Start Database
         */
        Connect::db();
        /**
         * Start Session
         */
        Connect::session();

        /**
         * Load Hooks
         */
        self::LoadHookFiles();
        /**
         * Set App Info Environment
         */
        Env::set('app|info', \do_hook('config.app'));
        Env::set('app|client', call_user_func([new Client, 'all']));
    }
}
