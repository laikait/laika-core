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

class AfterwareHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA AFTERWARE CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<AFTERWAREHELP
        {$this->txt_yellow("Description:")}
            List Afterware Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:afterware <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Afterware
            -> rename       :   Rename Afterware
            -> pop          :   Delete Afterware
            -> list         :   List of Afterwares

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
            Rename  :   <old_name> <new_name>
        
        {$this->txt_yellow("Options:")}
            No Oprions Required

        {$this->txt_yellow("Example:")}
            ->  php laika list:afterware
            ->  php laika make:afterware <name>
            ->  php laika pop:afterware <name>
            ->  php laika rename:afterware <old_name> <new_name>

        AFTERWAREHELP;
    }
}
