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

namespace Laika\Core\Console\Commands\Secret;

use Laika\Core\Console\Command;
use Laika\Core\Helper\Config;

class Pop extends Command
{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Create Secret Config File if Not Exist
        if (!Config::has('secret')) {
            Config::create('secret', []);
        }

        // Create Secret Key Value
        Config::set('secret', 'key', '');
        // Set Message
        $this->info("Secret Key Removed Successfully");
        return;
    }
}
