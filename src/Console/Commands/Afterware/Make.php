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

// Make Afterware Class
class Make extends Command
{
    // App Afterware Path
    protected string $path = APP_PATH . '/lf-app/Afterware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika make:afterware <name>");
            return;
        }

        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Afterware Name: [{$params[0]}]!");
            return;
        }

        // Get Parts
        $parts = $this->parts($params[0]);

        //Get Path
        $this->path .=  $parts['path'];

        // Make Directory if Not Exists
        if (!Directory::exists($this->path)) {
            Directory::make($this->path);
        }

        $file = "{$this->path}/{$parts['name']}.php";

        if (\is_file($file)) {
            $this->error("Afterware [{$file}] Already Exist!");
            return;
        }

        // Get Sample Content
        $content = \file_get_contents(__DIR__ . '/../../Samples/Afterware.sample');

        // Replace Placeholders
        $content = \str_replace(['{{NAMESPACE}}','{{NAME}}'], [$parts['namespace'],$parts['name']], $content);

        if (\file_put_contents($file, $content) === false) {
            $this->error("Failed to Create Afterware: [{$file}]!");
            return;
        }

        $this->info("Afterware [{$params[0]}] Created Successfully!");
    }
}
