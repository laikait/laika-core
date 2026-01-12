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

// Make Controller Class
class Make extends Command
{
    // App Controller Path
    protected string $path = APP_PATH . '/lf-app/Controller';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/][a-zA-Z0-9_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika make:controller <name>");
            return;
        }

        // Get Controller & View Parts
        $parts = $this->parts($params[0]);

        // Check Controller Name is Valid
        if (!\preg_match($this->exp, $params[0])) {
            $this->error("Invalid Controller Name: [{$params[0]}]");
            return;
        }

        // Set Controller & View Path
        $this->path .= $parts['path'];

        // Create Controller Directory if Not Exist
        if (!Directory::exists($this->path)) {
            try {
                Directory::make($this->path);
            } catch (\Throwable $th) {
                $this->error($th->getMessage());
                return;
            }
        }

        $file = "{$this->path}/{$parts['name']}.php";

        // Check Controller Already Exist
        if (\is_file($file)) {
            $this->error("Controller [{$params[0]}] Already Exist!");
            return;
        }

        // Get Sample Controller Content
        $content = \file_get_contents(__DIR__ . '/../../Samples/Controller.sample');

        // Replace Placeholders
        $content = \str_replace(['{{NAMESPACE}}', '{{NAME}}'], [$parts['namespace'], $parts['name']], $content);

        if (\file_put_contents($file, $content) === false) {
            $this->error("Failed to Create Controller: [{$file}]!");
            return;
        }

        $this->info("Controller [{$params[0]}] Created Successfully!");
        return;
    }
}
