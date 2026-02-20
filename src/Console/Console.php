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

class Console extends Command
{
    // Commands
    protected static array $commands = [];

    /**
     * @param array $args Pass $argv from the command line
     */
    public function __construct()
    {
        // Register Commands
        $this->registerCommands();
    }

    /**
     * Add Command to the Kernel
     */
    public static function addCommand(string $name, string $class): void
    {
        self::$commands[\strtolower($name)] = $class;
    }

    /*============================= INTERNAL API =============================*/
    // Register Default Commands
    protected static function registerCommands(): void
    {
        if (empty(self::$commands)) {
            self::$commands = [
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
        return;
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
        $keys = \array_keys(self::$commands);
        foreach ($keys as $key) {
            echo "\t-> {$key}\n";
        }
    }

    protected function makeParams(array $args): array
    {
        $longs = [];
        $shorts = [];
        $actions = [];

        \array_shift($args);
        $skipNext = false;

        foreach ($args as $k => $arg) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            // ---------- LONG OPTION (--foo) ----------
            if (isset($arg[0], $arg[1]) && $arg[0] === '-' && $arg[1] === '-') {
                $key = \ltrim($arg, '-');
                $next = $args[$k + 1] ?? null;

                if ($next !== null && (!isset($next[0]) || $next[0] !== '-')) {
                    $longs[$key] = $next;
                    $skipNext = true;
                } else {
                    $longs[$key] = true; // boolean flag
                }

                continue;
            }

            // ---------- SHORT OPTION (-f) ----------
            if (isset($arg[0]) && $arg[0] === '-') {
                $key = ltrim($arg, '-');
                $next = $args[$k + 1] ?? null;

                $shorts[$key] = $next;
                if ($next !== null && (!isset($next[0]) || $next[0] !== '-')) {
                    $skipNext = true;
                } else {
                    $shorts[$key] = null;
                }

                continue;
            }

            // ---------- POSITIONAL ----------
            $actions[] = $arg;
        }
        return [
            'actions' => $actions,
            'options' => ['long' => $longs, 'short' => $shorts]
        ];
    }

    /**
     * Match Command Key
     * @param ?string $key Command Key To Match
     * @return array{success:bool,class:?string,message:?string}
     */
    private function matchCommand(?string $key): array
    {
        // 1. Exact match
        if (array_key_exists(strtolower((string) $key), self::$commands)) {
            return [
                'success' => true,
                'class' => self::$commands[strtolower($key)],
                'message' => "Command '{$key}' Found",
            ];
        }

        // 2. Find Closest Key
        $closestKey = null;
        $shortestDistance = PHP_INT_MAX;

        foreach (array_keys(self::$commands) as $existingKey) {
            $distance = levenshtein($key, $existingKey);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestKey = $existingKey;
            }
        }

        // 3. Decide If Suggestion Is Good Enough
        // You Can Tune The Threshold (here <= 2)
        if ($shortestDistance <= 3) {
            return [
                'success' => false,
                'class' => null,
                'message' => "Laika Suggested Command: '{$closestKey}'. For Help, Run 'php laika help'",
            ];
        }

        return [
            'success' => false,
            'class' => null,
            'message' => "Invalid Command '{$key}'. For Help, Run 'php laika help'",
        ];
    }

    public function run(array $argv, array $options = []): void
    {
        // Make Params
        $args = $this->makeParams($argv);

        // Get Key From Params
        $key = $args['actions'][0] ?? null;
        $result = $this->matchCommand($key);
        if ($result['success'] == false) {
            $this->error($result['message']);
        }

        $actions = $args['actions'];
        array_shift($actions); // Remove Command Key

        try {
            \call_user_func([new $result['class'](), 'run'], $actions, $args['options']);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
        }
        return;
    }
}
