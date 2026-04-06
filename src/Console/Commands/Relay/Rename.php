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

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
use Laika\Core\Console\Command;

// Rename Relay Class
class Rename extends Command
{
    // App Relay Old Path
    protected string $old_path = APP_PATH . '/lf-app/Relay';

    // App Relay New Path
    protected string $new_path = APP_PATH . '/lf-app/Relay';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) != 2) {
            $this->error("Usage: php laika rename:relay <old_name> <new_name>");
            return;
        }

        // Model Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Relay Name is Valid
        if (!preg_match($this->exp, $old)) {
            // Invalid Relay Name
            $this->error("Invalid Old Relay Name: [{$old}]!");
            return;
        }
        // Check New Relay Name is Valid
        if (!preg_match($this->exp, $new)) {
            // Invalid Relay Name
            $this->error("Invalid New Relay Name: [{$new}]!");
            return;
        }

        // Get Old and New Parts
        $old_parts = $this->parts($old);
        $new_parts = $this->parts($new);

        // Get Directory Paths
        $this->old_path .= $old_parts['path'];
        $this->new_path .= $new_parts['path'];

        // Old and New Namespace
        $old_namespace = "namespace App\\Relay{$old_parts['namespace']}";
        $new_namespace = "namespace App\\Relay{$new_parts['namespace']}";

        $old_file = "{$this->old_path}/{$old_parts['name']}.php";
        $new_file = "{$this->new_path}/{$new_parts['name']}.php";

        // Check Old Relay is Valid
        if (!File::exists($old_file)) {
            $this->error("Old Relay [{$old}] Doesn't Exists!");
            return;
        }

        // Check New Relay is Valid
        if (File::exists($new_file)) {
            $this->error("New Relay [{$new}] Doesn't Exists!");
            return;
        }

        // Create Directory if Doesn't Exists
        try {
            Directory::make($this->new_path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        // Get Contents
        $content = File::read($old_file);
        if ($content === false) {
            $this->error("Failed to Read Old Relay: [{$old}]");
            return;
        }

        // Replace Namespace if Not Same
        if ($old_namespace != $new_namespace) {
            $content = preg_replace('/' . preg_quote($old_namespace, '/') . '/', $new_namespace, $content);
        }

        // Replace Class Name
        $content = preg_replace("/class {$old_parts['name']}/i", "class {$new_parts['name']}", $content);

        // Create New Relay File
        if (File::write($content, $new_file) === false) {
            $this->error("Failed to Create Relay: [{$new}]");
            return;
        }

        // Remove Old Relay File

        if (!File::pop($old_file)) {
            $this->error("Failed to Remove Old Relay: [$old_file]!");
            return;
        }

        $this->success("Relay [{$old}] Renamed to [{$new}] Successfully!");
    }
}
