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

namespace Laika\Core\Console\Commands\Model;

use Laika\Core\Console\Command;

// Make Model Class
class Lists extends Command
{
    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        $models = \call_user_func([new \Laika\Core\App\Infra(), 'getModels']);

        // Header
        $headers = ['#', 'Models'];

        // Find max width for "File Path" column
        $maxLength = \max(\array_map('strlen', $models) ?: [30]);
        $col2Width = \max(\strlen($headers[1]), $maxLength);

        // Table width
        $line = '+' . \str_repeat('-', 5) . '+' . \str_repeat('-', $col2Width + 2) . "+\n";

        // Print Header
        echo $line;
        \printf("| %-3s | %-{$col2Width}s |\n", $headers[0], $headers[1]);
        echo $line;

        $count = 0;
        // Print Rows
        foreach ($models as $item) {
            $count++;
            \printf("| %-3d | %-{$col2Width}s |\n", $count, $item);
        }

        echo $line;
        echo "Total: {$count}\n\n";
        return;
    }
}
