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

namespace Laika\Core\Console\Commands\Controller;

use Laika\Core\Helper\Directory;
use Laika\Core\Console\Command;

// Rename Controller Class
class Rename extends Command
{
    // App Controller Old Path
    protected string $old_path = APP_PATH . '/lf-app/Controller';

    // App Controller New Path
    protected string $new_path = APP_PATH . '/lf-app/Controller';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/][a-zA-Z0-9_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Check Parameters
        if (count($params) < 2) {
            $this->error("USAGE: php laika rename:controller <old_name> <new_name>");
            return;
        }

        // Model Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Controller Name is Valid
        if (!preg_match($this->exp, $old)) {
            // Invalid Controller Name
            $this->error("Invalid Old Controller Name: '{$old}'");
            return;
        }
        // Check New Controller Name is Valid
        if (!preg_match($this->exp, $new)) {
            // Invalid Controller Name
            $this->error("Invalid New Controller Name: '{$old}'");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old);
        $new_parts = $this->parts($new);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        // Old and New Namespace
        $old_namespace = "namespace Laika\\App\\Controller{$old_parts['namespace']}";
        $new_namespace = "namespace Laika\\App\\Controller{$new_parts['namespace']}";

        $old_file = "{$this->old_path}/{$old_parts['name']}.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.php";

        // Check Old Controller Path is Valid
        if (!is_file($old_file)) {
            $this->error("Invalid Controller Name or Path: '$old'");
            return;
        }

        // Check New Path Exist
        if (!Directory::exists($this->new_path)) {
            Directory::make($this->new_path);
        }

        // Check New Controller Path is Valid
        if (is_file($new_file)) {
            $this->error("New Controller Already Exist: '$old'");
            return;
        }

        // Get Contents
        $content = file_get_contents($old_file);
        if ($content === false) {
            $this->error("Failed to Read Controller: '{$old}'");
            return;
        }

        // Replace Namespace if Not Same
        if ($old_namespace != $new_namespace) {
            $content = preg_replace('/' . preg_quote($old_namespace, '/') . '/', $new_namespace, $content);
        }

        // Replace Class Name
        $content = preg_replace("/class {$old_parts['name']}/i", "class {$new_parts['name']}", $content);

        // Create New Controller File
        if (file_put_contents($new_file, $content) === false) {
            $this->error("Failed to Create Controller: {$new}");
            return;
        }

        // Remove Old Controller File

        if (!unlink($old_file)) {
            $this->error("Failed to Remove Controller: '{$old_file}'");
            return;
        }

        $this->info("Controller Renamed Successfully: '{$old}' -> '{$new}'");
        return;
    }
}
