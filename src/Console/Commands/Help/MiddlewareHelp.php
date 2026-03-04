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

class MiddlewareHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA MIDDLEWARE CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<MIDDLEWAREHELP
        {$this->txt_yellow("Description:")}
            List Middleware Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:middleware <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Middleware
            -> rename       :   Rename Middleware
            -> pop          :   Delete Middleware
            -> list         :   List of Middlewares

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
            Rename  :   <old_name> <new_name>
        
        {$this->txt_yellow("Options:")}
            No Oprions Required

        {$this->txt_yellow("Example:")}
            ->  php laika list:middleware
            ->  php laika make:middleware <name>
            ->  php laika pop:middleware <name>
            ->  php laika rename:middleware <old_name> <new_name>

        MIDDLEWAREHELP;
    }
}
