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

class Help extends Command
{
    /**
     * Run the command to create a new controller.
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        $match = strtolower($params[0] ?? 'n/a');

        switch ($match) {
            case 'model':
                (new \Laika\Core\Console\Commands\Help\ModelHelp())->run($params, $options);
                break;

            case 'controller':
                (new \Laika\Core\Console\Commands\Help\ControllerHelp())->run($params, $options);
                break;

            case 'middleware':
                (new \Laika\Core\Console\Commands\Help\MiddlewareHelp())->run($params, $options);
                break;

            case 'afterware':
                (new \Laika\Core\Console\Commands\Help\AfterwareHelp())->run($params, $options);
                break;

            case 'relay':
                (new \Laika\Core\Console\Commands\Help\RelayHelp())->run($params, $options);
                break;

            case 'template':
                (new \Laika\Core\Console\Commands\Help\TemplateHelp())->run($params, $options);
                break;
            
            default:
                $this->allHelpCommands();
                break;
        }
        return;
    }

    /**
     * All Help Commands
     * @return void
     */
    private function allHelpCommands(): void
    {
        echo "---------------------------";
        echo $this->txt_cyan("\nLAIKA COMMAND LISTS\n");
        echo "---------------------------\n";

        // CONTROLLERS
        // TEMPLATE CONTROLLERS
        echo <<<CONTROLLERS

        {$this->txt_green('## CONTROLLERS   [php laika help:controller]')}\n
            Make    :   php laika make:controller <name>
            Rename  :   php laika rename:controller <old_name> <new_name>
            Delete  :   php laika pop:controller <name>
            List    :   php laika list:controller <sub_path::optional>\n\n
        CONTROLLERS;
        // MIDDLEWARES
        echo <<<MIDDLEWARES
        {$this->txt_green('## MIDDLEWARES   [php laika help:middleware]')}\n
            Make    :   php laika make:middleware <name>
            Rename  :   php laika rename:middleware <old_name> <new_name>
            Delete  :   php laika pop:middleware <name>
            List    :   php laika list:middleware <sub_path::optional>\n\n
        MIDDLEWARES;
        // AFTERWARES
        echo <<<AFTERWARE
        {$this->txt_green('## AFTERWARES   [php laika help:afterware]')}\n
            Make    :   php laika make:afterware <name>
            Rename  :   php laika rename:afterware <old_name> <new_name>
            Delete  :   php laika pop:afterware <name>
            List    :   php laika list:afterware <sub_path::optional>\n\n
        AFTERWARE;
        // MODEL
        echo <<<MODEL
        {$this->txt_green('## MODEL   [php laika help:model]')}\n
            Make    :   php laika make:model <name>
            Rename  :   php laika rename:model <old_name> <new_name>
            Delete  :   php laika pop:model <name>
            List    :   php laika list:model <sub_path::optional>\n\n
        MODEL;

        // RELAY
        echo <<<RELAY
        {$this->txt_green('## RELAY   [php laika help:relay]')}\n
            Make    :   php laika make:relay <name> <optioanl:-k|--key>
            Rename  :   php laika rename:relay <old_name> <new_name>
            Delete  :   php laika pop:relay <name>
            List    :   php laika list:relay\n\n
        RELAY;

        // TEMPLATE
        echo <<<TEMPLATE
        {$this->txt_green('## TEMPLATE   [php laika help:template]')}\n
            Make    :   php laika make:template <name>
            Rename  :   php laika rename:template <old_name> <new_name>
            Delete  :   php laika pop:template <name>
            List    :   php laika list:template <sub_path::optional>\n\n
        TEMPLATE;

        // SECRET
        echo <<<SECRET
        {$this->txt_green('## SECRET   [php laika help:secret]')}\n
            Generate:   php laika generate:secret <byte_number::optional>
            Pop     :   php laika pop:secret\n\n
        SECRET;

        // MIGRATE
        echo <<<MIGRATE
        {$this->txt_green('## MIGRATE   [php laika help:migrate]')}\n
            Migrate :   php laika migrate <connection::optional> <model::optional>
        MIGRATE;

        return;
    }
}
