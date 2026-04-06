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

use Laika\Core\Relay\Relays\File;
use Laika\Core\Console\Command;

// Remove Relay Class
class Pop extends Command
{
    // App Relay Path
    protected string $path = APP_PATH . '/lf-app/Relay';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) != 1) {
            $this->error("USAGE: php laika pop:relay <name>");
            return;
        }

        // Check Relay Name is Valid
        if (!preg_match($this->exp, $params[0])) {
            // Invalid Relay Name
            $this->error("Invalid Relay Name: [{$params[0]}]");
            return;
        }

        // Get Relay Parts
        $parts = $this->parts($params[0]);

        // Set Path
        $this->path .= $parts['path'];

        $file = "{$this->path}/{$parts['name']}.php";

        // Check Relay Path is Valid
        if (!File::exists($file)) {
            $this->error("Invalid Relay or Path: [{$params[0]}]");
            return;
        }

        if (!File::pop($file)) {
            $this->error("Failed to Remove Relay: [{$file}]");
            return;
        }

        $this->success("Relay [{$params[0]}] Removed Successfully!");
    }
}
