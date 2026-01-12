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

namespace laika\Core\Console;

// Abstract Command Class
abstract class Command
{
    /**
     * @param array $params
     * This method should be implemented by each command class to define its behavior.
     * It should accept an array of parameters that can be passed from the command line.
     * @return void
     */
    abstract public function run(array $params): void;

    /**
     * @param string $message
     * This method is used to print informational messages to the console.
     * @return void
     */
    protected function info(string $message): void
    {
        // Green Text
        echo "\033[32m[SUCCESS]>> \033[0m{$message}\n"; // green text
    }

    /**
     * @param string $message
     * This method is used to print informational messages to the console.
     * @return void
     */
    protected function error(string $message): void
    {
        // Red Text
        echo "\033[31m[ERROR]>> \033[0m{$message}\n";
    }

    /**
     * @param string $str. Directory Path Stringl. Example: 'Admin/User'
     * @param bool $ucfirst. First Character of All Folders Will Be Upper Case. Default is true
     * @return array results with keys 'name', 'path', 'namespace'
     * @return array{name:string,path:string,namespace:string}
     */
    protected function parts(string $str, bool $ucfirst = true): array
    {
        $str    =   \trim($str, '/');
        $parts  =   \explode('/', $str);

        // Get File Name
        $result['name']         =   \array_pop($parts);
        $result['path']         =   '';
        $result['namespace']    =   '';

        // $parts = array_map('ucfirst', $parts);
        foreach ($parts as $part) {
            // Ucfirst if true
            if ($ucfirst) {
                $part = \ucfirst($part);
            }

            $result['path']         .=   "/{$part}";
            $result['namespace']    .=   "\\{$part}";
        }
        return $result;
    }
}
