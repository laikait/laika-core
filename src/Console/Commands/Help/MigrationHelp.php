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

class MigrationHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA MIGRATION CL DETAILS\n");
        echo "---------------------------\n";

        echo <<<MIGRATIONHELP
        {$this->txt_yellow("Description:")}
            List Migration Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:migration <...arguments> <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> list         :   List of Migrations

        {$this->txt_yellow("Arguments:")}
            No Arguments Required
        
        {$this->txt_yellow("Options:")}
            No Oprions Required

        {$this->txt_yellow("Example:")}
            ->  php laika list:migration

        MIGRATIONHELP;
    }
}
