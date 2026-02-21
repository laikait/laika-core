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

namespace Laika\Core\Console\Commands\View;

use Laika\Core\Helper\Directory;
use Laika\Core\Console\Command;

// Rename View Class
class Rename extends Command
{
    // App View Old Path
    protected string $old_path = APP_PATH . '/lf-templates';

    // App View New Path
    protected string $new_path = APP_PATH . '/lf-templates';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z0-9_\-\/]+$/';

    /**
     * Run The Command to Remove a View.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 2) {
            $this->error("Usage: php laika rename:view <old_name> <new_name>");
            return;
        }

        // View Name
        $old = $params[0];
        $new = $params[1];

        // Check Old View Name is Valid
        if (!\preg_match($this->exp, $old)) {
            // Invalid View Name
            $this->error("Invalid Old View Name: '{$old}'");
            return;
        }
        // Check New View Name is Valid
        if (!\preg_match($this->exp, $new)) {
            // Invalid View Name
            $this->error("Invalid New View Name: '{$old}'");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old, false);
        $new_parts = $this->parts($new, false);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        $old_file = "{$this->old_path}/{$old_parts['name']}.tpl.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.tpl.php";

        // Check Old View Path is Valid
        if (!\is_file($old_file)) {
            $this->error("Invalid View Name or Path: '$old'");
            return;
        }

        // Check New Path Exist
        if (!Directory::exists($this->new_path)) {
            Directory::make($this->new_path);
        }

        // Check New View Path is Valid
        if (\is_file($new_file)) {
            $this->error("New View Already Exist: '$old'");
            return;
        }

        // Get Contents
        $content = \file_get_contents($old_file);
        if ($content === false) {
            $this->error("Failed to Read View: '{$old}'");
            return;
        }

        // Create New View File
        if (\file_put_contents($new_file, $content) === false) {
            $this->error("Failed to Create View: {$new}");
            return;
        }

        // Remove Old View File
        if (!\unlink($old_file)) {
            $this->error("Failed to Remove View: '{$old_file}'");
            return;
        }

        $this->info("View Renamed Successfully: '{$old}'->'{$new}'");
        return;
    }
}
