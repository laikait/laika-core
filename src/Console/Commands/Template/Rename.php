<?php
/**
 * Laika PHP MVC Framework
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

class Rename extends Command
{
    // App Temaple Old Path
    protected string $old_path = APP_PATH . '/lf-templates';

    // App Temaple New Path
    protected string $new_path = APP_PATH . '/lf-templates';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z0-9_\-\/]+$/';

    /**
     * Run The Command to Remove a Temaple.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) < 2) {
            $this->error("Usage: php laika rename:template <old_name> <new_name>");
            return;
        }

        // Temaple Name
        $old = $params[0];
        $new = $params[1];

        // Check Old Temaple Name is Valid
        if (!preg_match($this->exp, $old)) {
            // Invalid Temaple Name
            $this->error("Invalid Old Temaple Name: '{$old}'");
            return;
        }
        // Check New Temaple Name is Valid
        if (!preg_match($this->exp, $new)) {
            // Invalid Temaple Name
            $this->error("Invalid New Temaple Name: '{$old}'");
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

        // Check Old Temaple Path is Valid
        if (!File::exists($old_file)) {
            $this->error("Invalid Temaple Name or Path: '$old'");
            return;
        }

        // Check New Path Exist
        try {
            Directory::make($this->new_path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        // Check New Temaple Path is Valid
        if (File::exists($new_file)) {
            $this->error("New Template Already Exist: '$old'");
            return;
        }

        // Get Contents
        $content = File::read($old_file);
        if ($content === false) {
            $this->error("Failed to Read Old Template: '{$old}'");
            return;
        }

        // Create New Temaple File
        if (File::write($content, $new_file) === false) {
            $this->error("Failed to Create Temaple: {$new}");
            return;
        }

        // Remove Old Temaple File
        if (!File::pop($old_file)) {
            $this->error("Failed to Remove Temaple: '{$old_file}'");
            return;
        }

        $this->success("Temaple Renamed Successfully: '{$old}'->'{$new}'");
        return;
    }
}
