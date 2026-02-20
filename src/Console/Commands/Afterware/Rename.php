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

namespace Laika\Core\Console\Commands\Afterware;

use Laika\Core\Helper\Directory;
use Laika\Core\Console\Command;

// Rename Afterware Class
class Rename extends Command
{
    // App Afterware Old Path
    protected string $old_path = APP_PATH . '/lf-app/Afterware';

    // App Afterware New Path
    protected string $new_path = APP_PATH . '/lf-app/Afterware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 2) {
            $this->error("Usage: php laika rename:afterware <old_name> <new_name>");
            return;
        }

        // Model Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Afterware Name is Valid
        if (!\preg_match($this->exp, $old)) {
            // Invalid Afterware Name
            $this->error("Invalid Old Afterware Name: [{$old}]!");
            return;
        }
        // Check New Afterware Name is Valid
        if (!\preg_match($this->exp, $new)) {
            // Invalid Afterware Name
            $this->error("Invalid New Afterware Name: [{$new}]!");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old);
        $new_parts = $this->parts($new);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        // Old and New Namespace
        $old_namespace = "namespace Laika\\App\\Afterware{$old_parts['namespace']}";
        $new_namespace = "namespace Laika\\App\\Afterware{$new_parts['namespace']}";

        $old_file = "{$this->old_path}/{$old_parts['name']}.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.php";

        // Check Old Afterware is Valid
        if (!\is_file($old_file)) {
            $this->error("Old Afterware [{$old}] Doesn't Exists!");
            return;
        }

        // Check New Afterware is Valid
        if (\is_file($new_file)) {
            $this->error("New Afterware [{$new}] Doesn't Exists!");
            return;
        }

        // Create Directory if Doesn't Exists
        if (!Directory::exists($this->new_path)) {
            Directory::make($this->new_path);
        }

        // Get Contents
        $content = \file_get_contents($old_file);
        if ($content === false) {
            $this->error("Failed to Read Afterware: [{$old}]");
            return;
        }

        // Replace Namespace if Not Same
        if ($old_namespace != $new_namespace) {
            $content = \preg_replace('/' . \preg_quote($old_namespace, '/') . '/', $new_namespace, $content);
        }

        // Replace Class Name
        $content = \preg_replace("/class {$old_parts['name']}/i", "class {$new_parts['name']}", $content);

        // Create New Afterware File
        if (\file_put_contents($new_file, $content) === false) {
            $this->error("Failed to Create Afterware: [{$new}]");
            return;
        }

        // Remove Old Afterware File

        if (!\unlink($old_file)) {
            $this->error("Failed to Remove Afterware: [$old_file]!");
            return;
        }

        $this->info("Afterware [{$old}] Renamed to [{$new}] Successfully!");
    }
}
