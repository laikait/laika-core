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

use Laika\Core\Exceptions\MigrationException;
use Laika\Core\Console\Command;
use Laika\Model\Schema\Schema;
use Laika\Core\Relay\Relays\Config;
use Laika\Model\Connection;

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

        // Check DB Config Available
        $config = Config::get('database', 'default');
        if (empty($config) || !is_array($config)) {
            $this->error("Database [default] Config Not Found or Missing Parameters!");
            return;
        }
        // Connect DB
        Connection::add($config);

        // Create Table
        try {
            // Migrate Migration Tables
            $schemaClasses = $schema ?
                    ["\\Laika\\App\\Migration\\{$schema}"] :
                    \call_user_func([new \Laika\Core\App\Infra(), 'getSchemaClasses']);

            // Show Error if No Migration Exists
            if (empty($schemaClasses)) {
                $this->error("No Migrations Found to Run!");
                return;
            }

            // Migrate Tables
            try {
                // Disable Foreign Key Check
                Schema::on()->statement('SET foreign_key_checks = 0');

                // Migrate Schema
                array_map(function ($table) {
                    $tblModel = new $table();
                    if (!method_exists($tblModel, 'migrate')) {
                        throw new MigrationException("{$table}::migrate() Method Doesn't Exists!");
                    }
                    $tblModel->migrate();
                }, $schemaClasses);

                // Migrate Default Column Values
                array_map(function ($table) {
                    $tblModel = new $table();
                    if (method_exists($tblModel, 'default')) {
                        $tblModel->default();
                    }
                }, $schemaClasses);

            } catch (\Throwable $th) {
                $this->error($th->getMessage());
                return;
            }

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
            $this->error($th->getMessage() . ' ' . $th->getFile() . ':' . $th->getLine());
            return;
        }
    }
}
