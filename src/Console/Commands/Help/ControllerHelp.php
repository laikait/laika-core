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

class ControllerHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA CONTROLLER CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<CONTROLLERHELP
        {$this->txt_yellow("Description:")}
            List Controller Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:controller <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Controller
            -> rename       :   Rename Controller
            -> pop          :   Delete Controller
            -> list         :   List of Controllers

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
            Rename  :   <old_name> <new_name>
        
        {$this->txt_yellow("Options:")}
            No Oprions Required

        {$this->txt_yellow("Example:")}
            ->  php laika list:controller
            ->  php laika make:controller <name>
            ->  php laika pop:controller <name>
            ->  php laika rename:controller <old_name> <new_name>

        CONTROLLERHELP;
    }
}
