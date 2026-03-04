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

class ModelHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "-----------------------";
        echo $this->txt_cyan("\nLAIKA MODEL CL DETAILS\n");
        echo "-----------------------\n";

        echo <<<MODELHELP
        {$this->txt_yellow("Description:")}
            List Model Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:model <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Model
            -> rename       :   Rename Model
            -> pop          :   Delete Model
            -> list         :   List of Models

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
            Rename  :   <old_name> <new_name>
        
        {$this->txt_yellow("Options:")}
            -------------------------------------------------------------
                OPTIONS         |           TASK
            -------------------------------------------------------------
            -> -t, --table      : Defines Table Name. Default <name>
            -> -p, --primary    : Defines Table Primary Key. Default: 'id'

        {$this->txt_yellow("Example:")}
            ->  php laika list:model
            ->  php laika make:model <name> -t <table> -p <primary_key>
            ->  php laika pop:model <name>
            ->  php laika rename:model <old_name> <new_name>

        MODELHELP;
    }
}
