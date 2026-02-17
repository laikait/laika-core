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

namespace Laika\Core\Console\Commands\Model;

use Laika\Core\Helper\Directory;
use Laika\Core\Console\Command;

class Make extends Command
{
    // App Model Path
    protected string $path = APP_PATH . '/lf-app/Model';

    // App Migration Path
    protected string $migrationPath = APP_PATH . '/lf-app/Migration';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika make:model <name> <table::optional>");
            return;
        }

        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Model Name: [{$params[0]}]!");
            return;
        }

        // Model Name
        $name = $params[0];
        // Table Name
        $table = strtolower($params[1] ?? $name);

        // Make Directory if Not Exist
        if (!Directory::exists($this->path)) {
            Directory::make($this->path);
        }

        $file = "{$this->path}/{$name}.php";

        if (\is_file($file)) {
            $this->error("Model [{$params[0]}] Already Exists!");
            return;
        }

        // Get Sample Content
        $content = \file_get_contents(__DIR__ . '/../../Samples/Model.sample');

        // Replace Placeholders
        $content = \str_replace(['{{NAME}}','{{TABLE}}'], [$name, $table], $content);

        // Create Model File
        if (\file_put_contents($file, $content) === false) {
            $this->error("Failed to Create Model: {$file}!");
            return;
        }

        // Migration File
        $migrationFile = "{$this->migrationPath}/{$name}.php";

        // Get Sample Migration Content
        $migrationContent = \file_get_contents(__DIR__ . '/../../Samples/Migration.sample');

        // Replace Placeholders in Migration File
        $migrationContent = \str_replace(['{{NAME}}','{{TABLE}}'], [$name, $table], $migrationContent);

        // Create Migration File
        if (\file_put_contents($migrationFile, $migrationContent) === false) {
            $this->error("Failed to Create Migration File: {$migrationFile}!");
            return;
        }

        $this->info("Model [{$params[0]}] Created Successfully!");
        return;
    }
}
