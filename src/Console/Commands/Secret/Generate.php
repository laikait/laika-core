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

class Generate extends Command
{
    /**
     * Run the command to create a new controller.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        $byte = $params[0] ?? 32;
        if (!is_numeric($byte) || ((int) $byte < 1)) {
            $this->error("USAGE: php laika generate:secret <byte_number::optional>");
            return;
        }

        $byte = (int) $byte;
        // Create Secret Config File if Not Exist
        if (!Config::has('secret')) {
            Config::create('secret', ['key' => bin2hex(random_bytes($byte))]);
        }

        // Create Secret Key Value
        Config::set('secret', 'key', bin2hex(random_bytes($byte)));
        // Set Message
        $this->info("Secret Key Generated Successfully");
        return;
    }
}
