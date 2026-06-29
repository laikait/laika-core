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

use Laika\Service\{Directory, File};
use Laika\Core\Console\Command;

class Pop extends Command
{
    // App Model Path
    protected string $path = APP_PATH . '/lf-app/Model';

    // App Schema Path
    protected string $schemaPath = APP_PATH . '/lf-app/Schema';

    // Accepted Regular Expresion
    private string $exp = '/^[a-zA-Z_]+$/';

    /**
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Make Directories if Does Not Exists
        if (!Directory::exists($this->path)) Directory::make($this->path);
        if (!Directory::exists($this->migrationPath)) Directory::make($this->migrationPath);

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

        // Schema File
        $schemaName = preg_replace('/model/i', '', $model) . 'Schema';
        $schemaFile = "{$this->schemaPath}/{$schemaName}.php";

        if (!File::exists($file)) {
            $this->error("Model [{$params[0]}] Doesn't Exist!");
            return;
        }

        if (!File::pop($file)) {
            $this->error("Failed to Remove Model: [{$file}]!");
            return;
        }

        if (!File::exists($schemaFile)) {
            $this->error("Model Deleted, But Schema [{$schemaFile}] Doesn't Exist!");
            return;
        }

        if (!File::pop($schemaFile)) {
            $this->error("Failed to Remove Schema File: [{$schemaFile}]!");
            return;
        }

        $this->success("Model [{$params[0]}] Removed Successfully!");
        return;
    }
}
