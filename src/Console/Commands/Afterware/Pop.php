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

use Laika\Core\Console\Command;

// Remove Afterware Class
class Pop extends Command
{
    // App Afterware Path
    protected string $path = APP_PATH . '/lf-app/Afterware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika pop:afterware <name>");
            return;
        }

        // Check Afterware Name is Valid
        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Afterware Name
            $this->error("Invalid Afterware Name: [{$params[0]}]");
            return;
        }

        // Get Afterware Parts
        $parts = $this->parts($params[0]);

        // Set Path
        $this->path .= $parts['path'];

        $file = "{$this->path}/{$parts['name']}.php";

        // Check Afterware Path is Valid
        if (!\is_file($file)) {
            $this->error("Invalid Afterware or Path: [{$params[0]}]");
            return;
        }

        if (!\unlink($file)) {
            $this->error("Failed to Remove Afterware: [{$file}]");
            return;
        }

        $this->info("Afterware [{$params[0]}] Removed Successfully!");
    }
}
