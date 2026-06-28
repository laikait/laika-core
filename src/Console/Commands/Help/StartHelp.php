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

namespace Laika\Core\Console\Commands\Help;

use Laika\Core\Console\Command;

class StartHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA SERVER START CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<STARTHELP
        {$this->txt_yellow("Description:")}
            List APP START Commands

        {$this->txt_yellow("Usage:")}
            php laika start <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> start        :   Start The Server

        {$this->txt_yellow("Arguments:")}
            No Arguments Required!
        
        {$this->txt_yellow("Options:")}
            -p  :   Define Port. Default is 8000

        {$this->txt_yellow("Example:")}
            ->  php laika start
            ->  php laika start -p 8080

        STARTHELP;
    }
}
