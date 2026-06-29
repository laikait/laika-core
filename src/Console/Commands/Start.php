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

use Laika\Core\Console\Command;

final class Start extends Command

{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Params
        if (count($params) != 0) {
            $this->error("USAGE: php laika start <...options>");
            return;
        }

        $port = 8000;
        // Set Port if Available in Command
        if (isset($options['short']['p'])) {
            // Check Input Port is Numeric
            if (!is_numeric($options['short']['p'])) {
                $this->error("Port Should Be Numeric!");
                return;
            }
            $port = (int) $options['short']['p'];
            // Check Input Port is in Range
            if (($port > 10000) || ($port < 10)) {
                $this->error("Port Range Should Be Between 10 to 10000!");
                return;
            }

            // Validate Port is Not Using
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec("netstat -ano | findstr :{$port} 2>NUL");
            } else {
                $output = shell_exec("ss -tln 2>/dev/null | grep :{$port}");
            }
        }

        // Verify Port or Get Next Port
        while ($port <= 10000) {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec("netstat -ano | findstr :{$port} 2>NUL");
            } else {
                $output = shell_exec("ss -tln 2>/dev/null | grep :{$port}");
            }
            if (!$output) break;
            $p = $port;
            $port++;
            $this->warning("Port [{$p}] is Busy! Going for Next Port: [{$port}]");
        }

        $root = getcwd(); // Always returns project root where command is run from
        $index = "{$root}/index.php";

        $this->success("Laika Server Started at: 127.0.0.1:{$port}");
        passthru("php -S 127.0.0.1:{$port} -t {$root}");
        return;
    }
}