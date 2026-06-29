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

namespace Laika\Core\Console\Commands\Schema;

use Laika\Service\{Directory, Infra};
use Laika\Core\Console\Command;

/**
 * Schema List Class
 */
class Lists extends Command
{
    // App Schema Path
    protected string $path = APP_PATH . '/lf-app/Schema';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Make Directories if Does Not Exists
        if (!Directory::exists($this->path)) Directory::make($this->path);

        $schemas = Infra::getSchemaClasses();

        // Header
        $headers = ['#', 'Database Schemas'];

        // Find max width for "File Path" column
        $maxLength = max(array_map('strlen', $schemas) ?: [30]);
        $col2Width = max(strlen($headers[1]), $maxLength);

        // Table width
        $line = '+' . str_repeat('-', 5) . '+' . str_repeat('-', $col2Width + 2) . "+\n";

        // Print Header
        echo $line;
        printf("| %-3s | %-{$col2Width}s |\n", $headers[0], $headers[1]);
        echo $line;

        $count = 0;
        // Print Rows
        foreach ($schemas as $item) {
            $count++;
            printf("| %-3d | %-{$col2Width}s |\n", $count, $item);
            echo $line;
        }

        echo ($count) ? "Total: {$this->txt_green((string) $count)}" : "Total: {$this->txt_red((string) $count)}";
        return;
    }
}
