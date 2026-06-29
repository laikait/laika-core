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

use Laika\Core\Console\Command;
use Laika\Service\{Directory, File};

class Make extends Command
{
    // App Model Path
    protected string $path = APP_PATH . '/lf-app/Model';

    // App Migration Path
    protected string $schemaPath = APP_PATH . '/lf-app/Schema';

    // Accepted Regular Expresion
    private string $exp = '/^[\w][\w\d\/]+$/';

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

        // Schema File
        $schemaName = preg_replace('/model/i', '', $model) . 'Schema';
        $schemaFile = "{$this->schemaPath}/{$schemaName}.php";

        // Get Sample Migration Content
        $schemaContent = File::read(__DIR__ . '/../../Samples/Schema.sample');

        // Replace Placeholders in Migration File
        $schemaContent = str_replace(['{{NAME}}','{{TABLE}}', '{{ID}}', '{{MODEL}}', '{{DELETED_AT}}'], [$schemaName, $table, $id, $model, $deletedAt], $schemaContent);

        // Create Schema File
        if (File::write($schemaContent, $schemaFile) === false) {
            $this->error("Failed to Create Schema File: {$schemaFile}!");
            return;
        }

        $this->success("Model [{$params[0]}] Created Successfully!");
        return;
    }
}
