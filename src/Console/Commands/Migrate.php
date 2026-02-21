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

namespace Laika\Core\Console\Commands;

use Laika\Core\Console\Command;
use Laika\Core\Helper\Config;
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
        // Get Single Model if Exists
        $model = $params[0] ?? null;

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
            $models = $model ?
                    ["\\Laika\\App\\Model\\" . ucfirst($model)] :
                    \call_user_func([new \Laika\Core\App\Infra(), 'getModels']);

            // Show Error if No Migration Exists
            if (empty($models)) {
                $this->error("No Migrations Found to Run!");
                return;
            }

            // Migrate Tables
            \call_user_func([new \Laika\Core\App\Infra(), 'migrateModels']);

            // Create Secret Config File if Not Exist
            if (!Config::has('secret')) {
                Config::create('secret', ['key' => bin2hex(random_bytes(64))]);
            }
            // Create Secret Key Value Not Exist or Empty
            if (!Config::has('secret', 'key')) {
                Config::set('secret', 'key', bin2hex(random_bytes(64)));
            }
            // Success Message
            $this->info("App Migrated Successfully");
            return;
        } catch (\Throwable $th) {
            $this->error($th->getMessage() . ' ' . $th->getFile() . ':' . $th->getLine());
            return;
        }
    }
}
