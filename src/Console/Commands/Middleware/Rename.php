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

// Rename Middleware Class
class Rename extends Command
{
    // App Middleware Old Path
    protected string $old_path = APP_PATH . '/lf-app/Middleware';

    // App Middleware New Path
    protected string $new_path = APP_PATH . '/lf-app/Middleware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/][a-zA-Z0-9_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Check Parameters
        if (\count($params) < 2) {
            $this->error("Usage: php laika rename:middleware <old_name> <new_name>");
            return;
        }

        // Model Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Middleware Name is Valid
        if (!\preg_match($this->exp, $old)) {
            // Invalid Middleware Name
            $this->error("Invalid Old Middleware Name: [{$old}]!");
            return;
        }
        // Check New Middleware Name is Valid
        if (!\preg_match($this->exp, $new)) {
            // Invalid Middleware Name
            $this->error("Invalid New Middleware Name: [{$new}]!");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old);
        $new_parts = $this->parts($new);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        // Old and New Namespace
        $old_namespace = "namespace Laika\\App\\Middleware{$old_parts['namespace']}";
        $new_namespace = "namespace Laika\\App\\Middleware{$new_parts['namespace']}";

        $old_file = "{$this->old_path}/{$old_parts['name']}.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.php";

        // Check Old Middleware is Valid
        if (!\is_file($old_file)) {
            $this->error("Old Middleware [{$old}] Doesn't Exists!");
            return;
        }

        // Check New Middleware is Valid
        if (\is_file($new_file)) {
            $this->error("New Middleware [{$new}] Doesn't Exists!");
            return;
        }

        // Create Directory if Doesn't Exists
        if (!Directory::exists($this->new_path)) {
            Directory::make($this->new_path);
        }

        // Get Contents
        $content = \file_get_contents($old_file);
        if ($content === false) {
            $this->error("Failed to Read Middleware: [{$old}]");
            return;
        }

        // Replace Namespace if Not Same
        if ($old_namespace != $new_namespace) {
            $content = \preg_replace('/' . \preg_quote($old_namespace, '/') . '/', $new_namespace, $content);
        }

        // Replace Class Name
        $content = \preg_replace("/class {$old_parts['name']}/i", "class {$new_parts['name']}", $content);

        // Create New Middleware File
        if (\file_put_contents($new_file, $content) === false) {
            $this->error("Failed to Create Middleware: [{$new}]");
            return;
        }

        // Remove Old Middleware File

        if (!\unlink($old_file)) {
            $this->error("Failed to Remove Middleware: [$old_file]!");
            return;
        }

        $this->info("Middleware [{$old}] Renamed to [{$new}] Successfully!");
    }
}
