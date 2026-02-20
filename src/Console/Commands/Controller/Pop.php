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

use Laika\Core\Console\Command;

// Remove Controller Class
class Pop extends Command
{
    // App Controller Path
    protected string $path = APP_PATH . '/lf-app/Controller';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/][a-zA-Z0-9_\/]+$/';

    /**
     * @param array $params
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika pop:controller <name>");
            return;
        }

        // Check Controller Name is Valid
        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Controller Name
            $this->error("Invalid Controller Name: [{$params[0]}]!");
            return;
        }

        // Get Controller Parts
        $parts = $this->parts($params[0]);

        // Set Path
        $this->path .= $parts['path'];

        $file = "{$this->path}/{$parts['name']}.php";

        // Check Controller Path is Valid
        if (!\is_file($file)) {
            $this->error("Controller [{$params[0]}] Doesn't Exists!");
            return;
        }

        if (!\unlink($file)) {
            $this->error("Failed to Remove Controller: [{$file}]!");
            return;
        }

        $this->info("Controller [{$params[0]}] Removed Successfully!");
        return;
    }
}
