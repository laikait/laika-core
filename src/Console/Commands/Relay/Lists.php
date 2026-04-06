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

namespace Laika\Core\Console\Commands\Relay;

use Laika\Core\Console\Command;
use Laika\Core\Relay\Relays\Infra;

// Make Relay Class
class Lists extends Command
{
    // App Relay Path
    protected string $path = APP_PATH . '/lf-app/Relay';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        $relays = Infra::getRelayClasses();
        $array = array_merge($relays['system'], $relays['user']);

        // Header
        $headers = ['#', 'Type', 'Relays'];

        // Find max width for "File Path" column
        $maxLength = max(array_map('strlen', $array) ?: [30]);
        $typeWidth = max(strlen($headers[1]), $maxLength);
        $relayWidth = max(strlen($headers[2]), $maxLength);

        // Table width
        $line = '+' . str_repeat('-', 5) . '+' . str_repeat('-', $typeWidth + 2) . '+' . str_repeat('-', $relayWidth + 2) . "+\n";

        // Print Header
        echo $line;
        printf("| %-3s | %-{$typeWidth}s | %-{$relayWidth}s |\n", $headers[0], $headers[1], $headers[2]);
        echo $line;

        $i = 0;
        // Print Rows
        foreach ($relays as $key => $item) {
            foreach ($item as $r) {
                printf("| %-3d | %-{$typeWidth}s | %-{$relayWidth}s |\n", $i, $key, $r);
                echo $line;
                $i++;
            }
        }
        echo "Total: " . count($array) . "\n\n";
        return;
    }
}
