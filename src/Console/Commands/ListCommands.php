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

namespace Laika\Core\Console\Commands;

use Laika\Core\Console\Command;

class ListCommands extends Command
{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params): void
    {
        echo <<<LIST
        --------------------------------
        -----  LAIKA COMMAND LISTS -----
        --------------------------------\n
        LIST;

        // CONTROLLERS
        // TEMPLATE CONTROLLERS
        echo <<<CONTROLLERS
        ## CONTROLLERS
            Make    :   php laika make:controller <name>
            Rename  :   php laika rename:controller <old_name> <new_name>
            Delete  :   php laika pop:controller <name>
            List    :   php laika list:controller <sub_path::optional>\n\n
        CONTROLLERS;
        // MIDDLEWARES
        echo <<<MIDDLEWARES
        ## MIDDLEWARES
            Make    :   php laika make:middleware <name>
            Rename  :   php laika rename:middleware <old_name> <new_name>
            Delete  :   php laika pop:middleware <name>
            List    :   php laika list:middleware <sub_path::optional>\n\n
        MIDDLEWARES;
        // MODEL
        echo <<<MODEL
        ## MODEL
            Make    :   php laika make:model <name> <table::optional>
            Rename  :   php laika rename:model <old_name> <new_name>
            Delete  :   php laika pop:model <name>
            List    :   php laika list:model <sub_path::optional>\n\n
        MODEL;

        // VIEW
        echo <<<VIEW
        ## VIEW
            Make    :   php laika make:view <name>
            Rename  :   php laika rename:view <old_name> <new_name>
            Delete  :   php laika pop:view <name>
            List    :   php laika list:view <sub_path::optional>\n\n
        VIEW;

        // SECRET
        echo <<<SECRET
        ## SECRET
            Generate:   php laika generate:secret <byte_number::optional>
            Pop     :   php laika pop:secret\n\n
        SECRET;

        // MIGRATE
        echo <<<MIGRATE
        ## MIGRATE
            Migrate :   php laika migrate <connection::optional> <model::optional>
        MIGRATE;
    }
}
