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

class RelayHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "-----------------------";
        echo $this->txt_cyan("\nLAIKA RELAY CL DETAILS\n");
        echo "-----------------------\n";

        echo <<<RELAYHELP
        {$this->txt_yellow("Description:")}
            List RELAY Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:relay <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Relay
            -> rename       :   Rename Relay
            -> pop          :   Delete Relay
            -> list         :   List of Relays

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
        
        {$this->txt_yellow("Options:")}
            -------------------------------------------------------------
                OPTIONS         |           TASK
            -------------------------------------------------------------
            -> -k, --key        : Defines Key Name. Default <name>

        {$this->txt_yellow("Example:")}
            ->  php laika list:relay
            ->  php laika make:relay <name> -k <key>
            ->  php laika make:relay <name> -key <key>
            ->  php laika pop:relay <name>
            ->  php laika rename:relay <old_name> <new_name>

        RELAYHELP;
    }
}
