<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MMC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Console\Commands\Route;

use Laika\Core\App\Route\Url;
use Laika\Core\Console\Command;
use Laika\Core\App\Route\Handler as Router;

// Make Controller Class
class Lists extends Command
{
    // App Controller Path
    protected string $path = APP_PATH . '/lf-routes';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        echo <<<PHP
        -------------------------------------------------------------------
        REGISTERED ROUTES:
        -------------------------------------------------------------------\n
        PHP;
        // Get Http List
        $requestMethod = $options['long']['method'] ?? $options['short']['m'] ?? null;
        if (!is_string($requestMethod)) {
            $requestMethod = null;
        }

        // Load Routes
        Url::loadRoutes();
        // Get Routes
        $routes = Router::getRoutes($requestMethod);

        // Normalize rows
        $rows = [];
        $count = 0;

        if ($requestMethod !== null) {
            // Single-method structure
            foreach ($routes as $uri => $data) {
                $count++;

                $handler = $data['controller'];
                if ($handler instanceof \Closure) {
                    $handler = 'Closure';
                }

                $rows[] = [
                    'id'      => $count,
                    'route'   => $uri,
                    'method'  => $requestMethod,
                    'handler' => (string) $handler,
                ];
            }
        } else {
            // Multi-method structure
            foreach ($routes as $method => $methodRoutes) {
                foreach ($methodRoutes as $uri => $data) {
                    $count++;

                    $handler = $data['controller'];
                    if ($handler instanceof \Closure) {
                        $handler = 'Closure';
                    }

                    $rows[] = [
                        'id'      => $count,
                        'route'   => $uri,
                        'method'  => $method,
                        'handler' => (string) $handler,
                    ];
                }
            }
        }

        // Headers
        $headers = ['#', 'Route', 'Method', 'Handler'];

        // Calculate widths
        $routeWidth   = max(strlen($headers[1]), ...array_map(fn($r) => strlen($r['route']), $rows ?: [['route'=>'']]));
        $methodWidth  = max(strlen($headers[2]), ...array_map(fn($r) => strlen($r['method']), $rows ?: [['method'=>'']]));
        $handlerWidth = max(strlen($headers[3]), ...array_map(fn($r) => strlen($r['handler']), $rows ?: [['handler'=>'']]));

        // Table line
        $line = '+-----+'
            . str_repeat('-', $routeWidth + 2) . '+'
            . str_repeat('-', $methodWidth + 2) . '+'
            . str_repeat('-', $handlerWidth + 2) . "+\n";

        // Header
        echo $line;
        printf(
            "| %-3s | %-{$routeWidth}s | %-{$methodWidth}s | %-{$handlerWidth}s |\n",
            $headers[0],
            $headers[1],
            $headers[2],
            $headers[3]
        );
        echo $line;

        // Rows
        foreach ($rows as $row) {
            printf(
                "| %-3d | %-{$routeWidth}s | %-{$methodWidth}s | %-{$handlerWidth}s |\n",
                $row['id'],
                $row['route'],
                $row['method'],
                $row['handler']
            );
        }

        echo $line;
        echo "Total: {$count}\n\n";
        return;
    }
}
