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

class SecretHelp extends Command
{
    /**
     * @param array $params
     * @param array $options
     * @return void
     */
    public function run(array $params = [], array $options = []): void
    {
        echo "-------------------------------";
        echo $this->txt_cyan("\nLAIKA SECRET KEY COMMAND HELPER\n");
        echo "-------------------------------\n";

        echo <<<SECRETKEYHELP
        {$this->txt_yellow("Description:")}
            List SECRET KEY Commands

        {$this->txt_yellow("Usage:")}
            laika <action>:secret <...options>

        {$this->txt_yellow("Actions:")}
            -----------------------------------
                ACTIONS     |       TASK
            -----------------------------------
            -> fix          :   Fix Secret Key
            -> generate     :   Generate Ne Secret Key
            -> pop          :   Delete Secret Key

        {$this->txt_yellow("Arguments:")}
            No Arguments Required
        
        {$this->txt_yellow("Options:")}
            -------------------------------------------------------------
                OPTIONS         |           TASK
            -------------------------------------------------------------
            -b  :   Optional Secret Key Byte Number. Default is 32.

        {$this->txt_yellow("Example:")}
            ->  php laika fix:secret <...options>
            ->  php laika generate:secret <...options>
            ->  php laika pop:secret

        SECRETKEYHELP;
    }
}