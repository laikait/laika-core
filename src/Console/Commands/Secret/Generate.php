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

use Laika\Service\Config;
use Laika\Core\Console\Command;

class Generate extends Command
{
    /**
     * Run the command to create a new controller.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        $byte = $params[0] ?? 32;
        if (count($params) > 0) {
            $this->error("USAGE: php laika generate:secret <...options>");
            return;
        }

        $byte = $options['short']['b'] ?? 32;
        // Check Byte is Numeric
        if (!is_numeric($byte)) {
            $this->error("Option [b] Should Be Numeric");
            return;
        }
        $byte = (int) $byte;

        // Check Byte is Greater Than 1
        if ($byte < 1) {
            $this->error("Option [b] Minumum Vakue is 1");
            return;
        }

        // Create Secret Config File if Not Exist
        if (!Config::has('secret')) {
            Config::create('secret', ['key' => bin2hex(random_bytes($byte))]);
        }

        // Create Secret Key Value
        Config::set('secret', 'key', bin2hex(random_bytes($byte)));
        // Set Message
        $this->success("{$byte} Byte Secret Key Generated Successfully");
        return;
    }
}
