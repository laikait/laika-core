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

namespace Laika\Core\Console\Commands\Template;

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
use Laika\Core\Console\Command;

// Make View Class
class Lists extends Command
{
    // App View Path
    protected string $path = APP_PATH . '/lf-templates';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z0-9_\-\/]+$/';

    /**
     * Run The Command to Create a New View.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Path
        $path = trim($params[0] ?? '', '/');

        // Check View Name is Valid
        if ($path && !preg_match($this->exp, $path)) {
            // Invalid View Name
            $this->error("Invalid View Path: '{$path}'");
            return;
        }

        // Get Path if Given
        if ($path) {
            $this->path .= "/{$path}";
        }

        // Check Path Exist
        if (!Directory::exists($this->path)) {
            $this->error("Template Directory Not Found: '{$this->path}'");
            return;
        }

        $paths = array_merge(Directory::files($this->path, 'html'), Directory::files($this->path, 'twig'));
        $items = [];
        $count = 0;
        foreach ($paths as $file) {
            if (File::exists($file)) {
                $file = str_replace("{$this->path}/", '', $file);
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ($name != 'functions') {
                    $items[] = $name;
                    $count++;
                }
            }
        }

        // Check Has Items
        if (empty($items)) {
            echo "\n{$this->bg_red(' 0 Template Found! ')}\n\n";
            return;
        }
        // Header
        $headers = ['#', 'Templates'];

        // Find max width for "File Path" column
        $maxLength = max(array_map('strlen', $items));
        $col2Width = max(strlen($headers[1]), $maxLength);

        // Table width
        $line = '+' . str_repeat('-', 5) . '+' . str_repeat('-', $col2Width + 2) . "+\n";

        // Print Header
        echo $line;
        printf("| %-3s | %-{$col2Width}s |\n", $headers[0], $headers[1]);
        echo $line;

        // $count = 0;
        // Print Rows
        foreach ($items as $k => $item) {
            printf("| %-3d | %-{$col2Width}s |\n", $k + 1, $item);
            echo $line;
        }

        echo $this->bg_green("Total: {$count}");
        return;
    }
}
