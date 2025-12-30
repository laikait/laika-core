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

namespace Laika\Core\Console\Commands\Middleware;

use Laika\Core\Helper\Directory;
use Laika\Core\Console\Command;

// Make Middleware Class
class Lists extends Command
{
    // App Middleware Path
    protected string $path = APP_PATH . '/lf-app/Middleware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        $middlewares = call_user_func([new \Laika\Core\App\Infra(), 'getMiddlewares']);

        // Header
        $headers = ['#', 'Models'];

        // Find max width for "File Path" column
        $maxLength = max(array_map('strlen', $middlewares) ?: [30]);
        $col2Width = max(strlen($headers[1]), $maxLength);

        // Table width
        $line = '+' . str_repeat('-', 5) . '+' . str_repeat('-', $col2Width + 2) . "+\n";

        // Print Header
        echo $line;
        printf("| %-3s | %-{$col2Width}s |\n", $headers[0], $headers[1]);
        echo $line;

        $count = 0;
        // Print Rows
        foreach ($middlewares as $item) {
            $count++;
            printf("| %-3d | %-{$col2Width}s |\n", $count, $item);
        }

        echo $line;
        echo "Total: {$count}\n\n";
        return;
    }
}
