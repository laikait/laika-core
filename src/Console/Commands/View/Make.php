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

class Make extends Command
{
    // App View Path
    protected string $path = APP_PATH . '/lf-templates';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z0-9_\-\/]+$/';

    /**
     * Run the command to create a new controller.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika make:view <name>");
            return;
        }

        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid View Name: '{$params[0]}'");
            return;
        }
        $parts = $this->parts($params[0], false);

        $this->path .= $parts['path'];

        // Make Directory if Not Exist
        if (!Directory::exists($this->path)) {
            Directory::make($this->path);
        }

        $file = "{$this->path}/{$parts['name']}.tpl.php";

        if (\is_file($file)) {
            $this->error("View Already Exist: {$file}");
            return;
        }

        // Get Sample Content
        $content = \file_get_contents(__DIR__ . '/../../Samples/View.sample');

        // Replace Placeholders
        if (\file_put_contents($file, $content) === false) {
            $this->error("Failed to Create View: {$file}");
            return;
        }

        $this->info("View Created Successfully: {$params[0]}");
        return;
    }
}
