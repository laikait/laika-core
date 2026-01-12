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

namespace Laika\Core\Exceptions;

use Laika\Core\Helper\Directory;
use Laika\Core\Http\Response;
use Laika\Core\Helper\Config;
use Throwable;

class Handler
{
    protected bool $debug;

    public function __construct()
    {
        $this->debug = (bool) Config::get('env', 'debug', false);
    }

    /**
     * Register Handler For Application
     * @return void
     */
    public static function register()
    {
        // Handle exceptions
        set_exception_handler([new Handler(), 'handle']);

        // Convert PHP warnings & notices into exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        return;
    }

    /**
     * Handle all exceptions
     */
    public function handle(Throwable $e)
    {
        $this->log($e);
        $this->render($e);
    }

    /**
     * Store logs or send to a logger service
     */
    protected function log(Throwable $e): void
    {
        if (!$this->debug) {
            return;
        }
        $logDir = APP_PATH . '/lf-logs';
        // Create Directory If Not Exists
        Directory::make($logDir);

        $file = $logDir . '/' . date('Y') . '-' . date('M') . '-' . date('d') . '-error.log';

        $log = sprintf(
            "[%s] %s: %s in %s on line %d\nTrace:\n%s\n\n",
            date('Y-M-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        file_put_contents($file, $log, FILE_APPEND);
        return;
    }

    /**
     * Render output based on debug mode.
     */
    protected function render(Throwable $e): void
    {
        if ($this->wantsJson()) {
            call_user_func([new Response, 'setHeader'], ['content-type'=>'application/json']);
            $this->renderJson($e);
        } else {
            $this->renderHtml($e);
        }
        return;
    }

    private function wantsJson(): bool
    {
        return (
            ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json'
            || str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        );
    }

    private function renderDebug(Throwable $e)
    {
        $whoops = new \Whoops\Run;
        $handler = new \Whoops\Handler\PrettyPageHandler;
        $handler->setPageTitle("Laika Application Error!");
        $whoops->prependHandler($handler);
        $whoops->handleException($e);
    }

    private function renderJson(Throwable $e)
    {
        if ($e instanceof ValidationException) {
            http_response_code($e->getStatusCode());

            echo json_encode([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return;
        }

        if ($e instanceof HttpException) {
            http_response_code($e->getStatusCode());

            echo json_encode([
                'message' => $e->getMessage(),
            ]);
            return;
        }

        // Fallback for unknown errors
        http_response_code(500);

        echo json_encode([
            'message' => 'Laika Application Error!',
            'exception' => $this->debug ? $e->getMessage() : null,
        ]);
    }

    private function renderHtml(Throwable $e)
    {
        if ($this->debug) {
            $this->renderDebug($e);
            return;
        }

        // Production render
        $code = 500;
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
        }

        http_response_code($code);
        echo "<h1>Something Went Wrong!</h1>";
        return;
    }
}
