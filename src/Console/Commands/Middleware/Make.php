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

namespace Laika\Core\Console\Commands\Middleware;

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
use Laika\Core\Console\Command;

// Make Middleware Class
class Make extends Command
{
    // App Middleware Path
    protected string $path = APP_PATH . '/lf-app/Middleware';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_\/][a-zA-Z0-9_\/]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) < 1) {
            $this->error("USAGE: php laika make:middleware <name>");
            return;
        }

        if (!preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Middleware Name: [{$params[0]}]!");
            return;
        }

        // Get Parts
        $parts = $this->parts($params[0]);

        //Get Path
        $this->path .=  $parts['path'];

        // Make Directory if Not Exists
        try {
            Directory::make($this->path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        $file = "{$this->path}/{$parts['name']}.php";

        if (File::exists($file)) {
            $this->error("Middleware [{$file}] Already Exist!");
            return;
        }

        // Get Sample Content
        $content = File::read(__DIR__ . '/../../Samples/Middleware.sample');
        if ($content === false) {
            $this->error("Failed to Read Sample: [{$file}]!");
            return;
        }

        // Replace Placeholders
        $content = str_replace(['{{NAMESPACE}}','{{NAME}}'], [$parts['namespace'],$parts['name']], $content);

        if (File::write($content, $file) === false) {
            $this->error("Failed to Create Middleware: [{$file}]!");
            return;
        }

        $this->success("Middleware [{$params[0]}] Created Successfully!");
    }
}
