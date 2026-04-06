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

use Laika\Core\Relay\Relays\File;
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
        if (count($params) < 1) {
            $this->error("USAGE: php laika pop:model <name>");
            return;
        }

        if (!preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Model Name: {$params[0]}!");
            return;
        }

        // Model Name
        $model = $params[0];

        $file = "{$this->path}/{$model}.php";

        // Migration File
        $schemaName = preg_replace('/model/i', '', $model) . 'Schema';
        $migrationFile = "{$this->migrationPath}/{$schemaName}.php";

        if (!File::exists($file)) {
            $this->error("Model [{$params[0]}] Doesn't Exist!");
            return;
        }

        if (!File::pop($file)) {
            $this->error("Failed to Remove Model: [{$file}]!");
            return;
        }

        if (!File::exists($migrationFile)) {
            $this->error("Model Deleted, But Migration [{$migrationFile}] Doesn't Exist!");
            return;
        }

        if (!File::pop($migrationFile)) {
            $this->error("Failed to Remove Migration File: [{$migrationFile}]!");
            return;
        }

        $this->success("Model [{$params[0]}] Removed Successfully!");
        return;
    }
}
