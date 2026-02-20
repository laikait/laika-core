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

use Laika\Core\Console\Command;

class Pop extends Command
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
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (\count($params) < 1) {
            $this->error("USAGE: php laika pop:model <name>");
            return;
        }

        if (!\preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Model Name: {$params[0]}!");
            return;
        }

        // Model Name
        $model = $params[0];
        // Table Name
        $table = strtolower($params[1] ?? $model);

        $file = "{$this->path}/{$model}.php";
        $migrationFile = "{$this->migrationPath}/{$model}.php";

        if (!\is_file($file)) {
            $this->error("Model [{$params[0]}] Doesn't Exist!");
            return;
        }

        if (!\unlink($file)) {
            $this->error("Failed to Remove Model: [{$file}]!");
            return;
        }

        if (is_file($migrationFile) && !\unlink($migrationFile)) {
            $this->error("Failed to Remove Migration File: [{$migrationFile}]!");
            return;
        }

        $this->info("Model [{$params[0]}] Removed Successfully!");
        return;
    }
}
