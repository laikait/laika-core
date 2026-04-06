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

namespace Laika\Core\Console\Commands\Model;

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
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
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) < 1) {
            $this->error("USAGE: php laika make:model <name> <table::optional>");
            return;
        }

        if (!preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Model Name: [{$params[0]}]!");
            return;
        }

        // Model Name
        $model = $params[0];
        // Table Name
        $table = $options['long']['table'] ?? $options['short']['t'] ?? $model;
        $id = $options['long']['primary'] ?? $options['short']['p'] ?? 'id';
        $deletedAt = $options['long']['deleted'] ?? $options['short']['d'] ?? 'deletedAtColumn';

        // Check Table Name Is Valid
        if (!preg_match('/^[a-zA-Z_]+$/', $table)) {
            $this->error("Table Name Should Contains Only a-z, A-Z & _");
            return;
        }

        // Check Primary Key Name Is Valid
        if (!preg_match('/^[a-zA-Z_]+$/', $id)) {
            $this->error("Primary Column Name Should Contains Only a-z, A-Z & _");
            return;
        }

        // Make Directory if Not Exist
        try {
            Directory::make($this->path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        $file = "{$this->path}/{$model}.php";

        if (File::exists($file)) {
            $this->error("Model [{$params[0]}] Already Exists!");
            return;
        }

        // Get Sample Content
        $content = File::read(__DIR__ . '/../../Samples/Model.sample');
        if ($content === false) {
            $this->error("Failed to Read Sample: [{$file}]!");
            return;
        }

        // Replace Placeholders
        $content = str_replace(['{{NAME}}','{{TABLE}}', '{{ID}}', '{{DELETED_AT}}'], [$model, $table, $id, $deletedAt], $content);

        // Create Model File
        if (File::write($content, $file) === false) {
            $this->error("Failed to Create Model: {$file}!");
            return;
        }

        // Migration File
        $schemaName = preg_replace('/model/i', '', $model) . 'Schema';
        $migrationFile = "{$this->migrationPath}/{$schemaName}.php";

        // Get Sample Migration Content
        $migrationContent = File::read(__DIR__ . '/../../Samples/Migration.sample');

        // Replace Placeholders in Migration File
        $migrationContent = str_replace(['{{NAME}}','{{TABLE}}', '{{ID}}', '{{MODEL}}', '{{DELETED_AT}}'], [$schemaName, $table, $id, $model, $deletedAt], $migrationContent);

        // Create Migration File
        if (File::write($migrationContent, $migrationFile) === false) {
            $this->error("Failed to Create Migration File: {$migrationFile}!");
            return;
        }

        $this->success("Model [{$params[0]}] Created Successfully!");
        return;
    }
}
