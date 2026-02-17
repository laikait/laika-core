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

namespace Laika\Core\Console;

class Kernel
{
    // Arguments
    protected array $args;

    // Commands
    protected array $commands = [];

    /**
     * @param array $args Pass $argv from the command line
     */
    public function __construct(array $args)
    {
        // Set Arguments
        $this->args = $args;
        // Register Commands
        $this->registerCommands();
    }

    /**
     * Handle Kernel
     * @return void
     */
    public function handle(): void
    {
        // Remove "laika"
        \array_shift($this->args);

        $command = $this->args[0] ?? null;

        $params = \array_slice($this->args, 1);

        if ($command && isset($this->commands[strtolower($command)])) {
            $class = $this->commands[\strtolower($command)];
            \call_user_func([new $class(), 'run'], $params);
        } else {
            $this->printHelp();
        }
    }

    protected function registerCommands()
    {
        $this->commands = [
            /* Template Controller Commands */
            'make:controller'       =>  \Laika\Core\Console\Commands\Controller\Make::class,
            'rename:controller'     =>  \Laika\Core\Console\Commands\Controller\Rename::class,
            'pop:controller'        =>  \Laika\Core\Console\Commands\Controller\Pop::class,
            'list:controller'       =>  \Laika\Core\Console\Commands\Controller\Lists::class,
            /* Middleware Commands */
            'make:middleware'       =>  \Laika\Core\Console\Commands\Middleware\Make::class,
            'rename:middleware'     =>  \Laika\Core\Console\Commands\Middleware\Rename::class,
            'pop:middleware'        =>  \Laika\Core\Console\Commands\Middleware\Pop::class,
            'list:middleware'       =>  \Laika\Core\Console\Commands\Middleware\Lists::class,
            /* Afterware Commands */
            'make:afterware'       =>  \Laika\Core\Console\Commands\Afterware\Make::class,
            'rename:afterware'     =>  \Laika\Core\Console\Commands\Afterware\Rename::class,
            'pop:afterware'        =>  \Laika\Core\Console\Commands\Afterware\Pop::class,
            'list:afterware'       =>  \Laika\Core\Console\Commands\Afterware\Lists::class,
            /* Model Commands */
            'make:model'            =>  \Laika\Core\Console\Commands\Model\Make::class,
            'rename:model'          =>  \Laika\Core\Console\Commands\Model\Rename::class,
            'pop:model'             =>  \Laika\Core\Console\Commands\Model\Pop::class,
            'list:model'            =>  \Laika\Core\Console\Commands\Model\Lists::class,
            /* View Commands */
            'make:view'             =>  \Laika\Core\Console\Commands\View\Make::class,
            'rename:view'           =>  \Laika\Core\Console\Commands\View\Rename::class,
            'pop:view'              =>  \Laika\Core\Console\Commands\View\Pop::class,
            'list:view'             =>  \Laika\Core\Console\Commands\View\Lists::class,
            /* Other Commands */
            'help'                  =>  \Laika\Core\Console\Commands\ListCommands::class,
            /* Migrate */
            'migrate'               =>  \Laika\Core\Console\Commands\Migrate::class,
            /* Route */
            'list:route'            =>  \Laika\Core\Console\Commands\Route\Lists::class,
            /* Secret Key */
            'generate:secret'       =>  \Laika\Core\Console\Commands\Secret\Generate::class,
            'pop:secret'            =>  \Laika\Core\Console\Commands\Secret\Pop::class,
        ];
    }

    protected function printHelp()
    {
        echo <<<COMMON
        ##################################
        LAIKA CLI TOOL
        Usage: php laika <command> [options]
        ##################################

        AVAILABLE COMMANDS\n
        COMMON;
        $keys = \array_keys($this->commands);
        foreach ($keys as $key) {
            echo "\t-> {$key}\n";
        }
    }
}
