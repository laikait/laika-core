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
use Laika\Service\{Directory, File};

// Rename Model Class
class Rename extends Command
{
    // App Model Old Path
    protected string $old_path = APP_PATH . '/lf-app/Model';

    // App Schema Path
    protected string $schemaPath = APP_PATH . '/lf-app/Schema';

    // App Model New Path
    protected string $new_path = APP_PATH . '/lf-app/Model';

    // Accepted Regular Expresion
    private string $exp = '/^[\w][\w\d\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Make Directories if Does Not Exists
        if (!Directory::exists($this->old_path)) Directory::make($this->path);
        if (!Directory::exists($this->schemaPath)) Directory::make($this->path);

        // Check Parameters
        if (count($params) < 2) {
            $this->error("Usage: php laika rename:model <old_name> <new_name>");
            return;
        }

        // Model Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Model Name is Valid
        if (!preg_match($this->exp, $old)) {
            // Invalid Model Name
            $this->error("Invalid Old Model Name: [{$old}]!");
            return;
        }
        // Check New Model Name is Valid
        if (!preg_match($this->exp, $new)) {
            // Invalid Model Name
            $this->error("Invalid New Model Name: '{$new}'");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old);
        $new_parts = $this->parts($new);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        // Old and New Namespace
        $old_namespace = "namespace App\\Model{$old_parts['namespace']}";
        $new_namespace = "namespace App\\Model{$new_parts['namespace']}";

        $old_file = "{$this->old_path}/{$old_parts['name']}.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.php";

        // Check Old Model is Valid
        if (!File::exists($old_file)) {
            $this->error("Model [$old] Doesn't Exists!");
            return;
        }

        // Check New Model is Valid
        if (File::exists($new_file)) {
            $this->error("New Model [{$old}] Already Exist!");
            return;
        }

        // Check New Path Exist
        try {
            Directory::make($this->new_path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        // Get Contents
        $content = File::read($old_file);
        if ($content === false) {
            $this->error("Failed to Read Old File: [{$old_file}]!");
            return;
        }

        // Replace Namespace if Not Same
        if ($old_namespace != $new_namespace) {
            $content = preg_replace('/' . preg_quote($old_namespace, '/') . '/', $new_namespace, $content);
        }

        // Replace Class Name
        $content = preg_replace("/class {$old_parts['name']}/i", "class {$new_parts['name']}", $content);

        // Create New Model File
        if (File::write($content, $new_file) === false) {
            $this->error("Failed to Create Model: [{$new}]!");
            return;
        }

        // Remove Old Model File
        if (!File::pop($old_file)) {
            $this->error("Failed to Remove Model: [{$old_file}]!");
            return;
        }

        $this->success("Model [$old] Renamed to [{$new}] Successfully!");
        return;
    }
}
