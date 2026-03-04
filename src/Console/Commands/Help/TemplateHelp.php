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

class TemplateHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA TEMPLATE CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<TEMPLATEHELP
        {$this->txt_yellow("Description:")}
            List Template Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:template <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> make         :   Make Template
            -> rename       :   Rename Template
            -> pop          :   Delete Template
            -> list         :   List of Templates

        {$this->txt_yellow("Arguments:")}
            Make    :   <name>
            Rename  :   <old_name> <new_name>
        
        {$this->txt_yellow("Options:")}
            No Oprions Required

        {$this->txt_yellow("Example:")}
            ->  php laika list:template
            ->  php laika make:template <name>
            ->  php laika pop:template <name>
            ->  php laika rename:template <old_name> <new_name>

        TEMPLATEHELP;
    }
}
