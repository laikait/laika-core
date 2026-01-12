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
use Exception;

class Migrate extends Command
{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        // Get Values
        $args = ['connection' => null, 'model'=>null];
        foreach ($params as $param) {
            $parts = explode(':', $param);
            if (isset($parts[0])) {
                $args[$parts[0]] = $parts[1] ?? null;
            }
        }

        // Set Connection Name
        $connection = $args['connection'] ?? 'default';
        // Set Model
        $model = $args['model'] ?? null;
        // Check DB Config Available
        $config = Config::get('database', $connection);
        if (empty($config)) {
            $this->error("Database [{$connection}] Config Not Found or Missing Parameters!");
            return;
        }
        // Connect DB
        Connection::add($config, $connection);

        // Create Table
        try {
            // Migrate All Available Models
            $models = $model ?
                    ["\\Laika\\App\\Model\\{$model}"] :
                    call_user_func([new \Laika\Core\App\Infra(), 'getModels']);

            // Show Error if No Model Exists
            if (empty($models)) {
                $this->error("No Models Found to Migrate!");
            }

            // Migrate Models
            foreach ($models as $model) {
                call_user_func([new $model, 'migrate']);
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
            $this->info("App Migrated Successfully");
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }
    }
}
