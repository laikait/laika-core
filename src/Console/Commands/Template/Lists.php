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

use Laika\Core\Console\Command;
use Laika\Service\{Directory, File};

// Make View Class
class Lists extends Command
{
    // App View Path
    protected string $path = APP_PATH . '/template';

    // Accepted Regular Expresion
    private string $exp = '/^[\w\-\/]+$/';

    /**
     * Run The Command to Create a New View.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) > 0) {
            $this->error("USAGE: php laika list:template <...options>");
            return;
        }

        // Get Extension
        $ext = strtolower($options['short']['e'] ?? '*');
        if (!in_array($ext, ['twig', 'html', '*'])) {
            $this->error("Invalid Template File Extension: '{$ext}'. Allowed Extensions Are: ['twig','html', '*']");
            return;
        }
        if ($ext == '*') {
            $ext = ['twig', 'html'];
        }

        // Set Path
        if (!empty($options['short']['d'])) {
            // Check Valid Chars
            if (!preg_match('/^[\w\d\/\.\-]+$/', $options['short']['d'])) {
                $this->error("Invalid Subdirectory: [{$options['short']['d']}]");
                return;
            }
            $this->path .= '/' . trim($options['short']['d'], '/');
        }

        // Check Path Exist
        if (!Directory::exists($this->path)) {
            $this->error("Template Directory Not Found: '{$this->path}'");
            return;
        }

        // $paths = Directory::files($this->path, $ext);
        $paths = Directory::scan($this->path, false, $ext);
        $items = [];
        $count = 0;
        foreach ($paths as $file) {
            if (File::exists($file)) {
                $file = str_replace(APP_PATH . DS . 'template', '', $file);
                $name = pathinfo($file, PATHINFO_FILENAME);
                $dir = trim(pathinfo($file, PATHINFO_DIRNAME), DS);
                if ($name != 'functions') {
                    $items[] = $dir ? "{$dir}/{$name}" : "{$name}";
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
