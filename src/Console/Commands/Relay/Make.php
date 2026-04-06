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

// Make Relay Class
class Make extends Command
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
        $countParams = count($params);

        if ($countParams != 1) {
            $this->error("USAGE: php laika make:relay <name> <optioanl:-k|--key>");
            return;
        }

        if (!preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Relay Name: [{$params[0]}]!");
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
            $this->error("Relay [{$params[0]}] Already Exist!");
            return;
        }

        // Get Sample Content
        $content = File::read(__DIR__ . '/../../Samples/Relay.sample');

        if ($content === false) {
            $this->error("Failed to Read Sample: [{$file}]!");
            return;
        }

        // Get Key Name
        $key = strtolower($options['long']['key'] ?? $options['short']['k'] ?? $parts['name']);

        // Replace Placeholders
        $content = str_replace(
            ['{{NAMESPACE}}', '{{NAME}}', '{{KEY}}'],
            [$parts['namespace'], $parts['name'], $key],
            $content
        );

        if (File::write($content, $file) === false) {
            $this->error("Failed to Create Relay: [{$file}]!");
            return;
        }

        $this->success("Relay [{$params[0]}] Created Successfully!");
    }
}
