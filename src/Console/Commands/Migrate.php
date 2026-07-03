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

namespace Laika\Core\Console\Commands;

use Laika\Model\Connection;
use Laika\Model\Schema\Schema;
use Laika\Core\Console\Command;
use Laika\Service\{Infra, Config, DB};
use Laika\Core\Exceptions\SchemaException;

class Migrate extends Command
{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Get Single Schema if Exists
        $schema = $params[0] ?? null;
        if (is_string($schema)) {
            $schema = preg_replace('/model/i', '', $schema) . 'Schema';
        }

        // Connect DB
        try {
            DB::run();
        } catch (\Throwable $th) {
            $this->error("Database Connection Error!");
            return;
        }

        // Create Table
        try {
            // Migrate Migration Tables
            $schemaClasses = Infra::getSchemaClasses();

            $tables = $schema
            ? array_values(array_filter(
                $schemaClasses,
                fn (string $class) => str_ends_with($class, $schema)
            ))
            : $schemaClasses;

            // Show Error if No Migration Exists
            if (empty($schemaClasses)) {
                $this->warning("No Migrations Found to Run!");
                return;
            }

            // Migrate Tables
            // Disable Foreign Key Check
            Schema::on()->statement('SET foreign_key_checks = 0');

            // Migrate Schema
            array_map(function ($table) {
                $tblModel = new $table();
                if (!method_exists($tblModel, 'migrate')) {
                    $this->error("{$table}::migrate() Method Doesn't Exists!");
                }
                $tblModel->migrate();
            }, $tables);

            // Migrate Default Column Values
            array_map(function ($table) {
                $tblModel = new $table();
                if (method_exists($tblModel, 'default')) {
                    $tblModel->default();
                }
            }, $tables);

            // Create Secret Config File if Not Exist
            if (!Config::has('secret')) {
                Config::create('secret', ['key' => bin2hex(random_bytes(64))]);
            }
            // Create Secret Key Value Not Exist or Empty
            if (!Config::has('secret', 'key')) {
                Config::set('secret', 'key', bin2hex(random_bytes(64)));
            }
            // Success Message
            $this->success("Database Migrated Successfully");
            return;
        } catch (\Throwable $th) {
            $message = DEBUG ? "{$th->getMessage()} {$th->getFile()}:{$th->getLine()}" : "Migration Failed!";
            $this->error($message);
            return;
        }
    }
}
