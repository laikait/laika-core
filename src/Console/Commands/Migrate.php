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
use Laika\Core\App\Options;
use Laika\Model\Connection;
use Exception;

class Migrate extends Command
{
    /**
     * Default Options Keys
     * @return array<string,string>
     */
    private function defaulOptions(): array
    {
        return [
            'app.name'      =>  'Laika Framework',
            'time.zone'     =>  'Europe/London',
            'time.format'   =>  'Y-M-d H:i:s',
            'dbsession'     =>  'yes',
            'debug'         =>  'yes',
            'app.path'      =>  realpath(APP_PATH ?? __DIR__ . '/../../../../../../'),
            'app.icon'      =>  'favicon.ico',
            'app.logo'      =>  'logo.png',
            'csrf.lifetime' =>  '300',
        ];
    }

    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        $defaul_name = $params[0] ?? 'default';
        // Check DB Config Available
        $configs = Config::get('database');
        if (empty($configs) || !isset($configs[$defaul_name])) {
            $this->error("Database [{$defaul_name}] Config Not Found!");
            return;
        }
        // Connect DB
        foreach ($configs as $name => $config) {
            Connection::add($config, $name);
        }

        // Create Table
        try {
            $model = new Options($defaul_name);
            $model->migrate();

            // Check Option Table Doesn't Exists
            if (empty($model->get())) {
                $rows = [];
                foreach ($this->defaulOptions() as $key => $val) {
                    $rows[] = [$model->key => $key, $model->value => $val, $model->default => 'yes'];
                }
                // Insert Options
                $model->insert($rows);
            }

            // Migrate All Available Models
            $models = call_user_func([new \Laika\Core\App\Infra(), 'getModels']);
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
