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
     * It should accept an array of options that can be passed from the command line.
     * @return void
     */
    abstract public function run(array $params, array $options = []): void;

    /*========================== FORGROUND COLOR START ==========================*/
    /**
     * Black Text
     * @return string
     */
    protected function txt_black(string $text): string
    {
        return "\e[30m{$text}\e[0m";
    }

    /**
     * Red Text
     * @return string
     */
    protected function txt_red(string $text): string
    {
        return "\e[31m{$text}\e[0m";
    }

    /**
     * Green Text
     * @return string
     */
    protected function txt_green(string $text): string
    {
        return "\e[32m{$text}\e[0m";
    }

    /**
     * Yellow Text
     * @return string
     */
    protected function txt_yellow(string $text): string
    {
        return "\e[33m{$text}\e[0m";
    }

    /**
     * Blue Text
     * @return string
     */
    protected function txt_blue(string $text): string
    {
        return "\e[34m{$text}\e[0m";
    }

    /**
     * Magenta Text
     * @return string
     */
    protected function txt_magenta(string $text): string
    {
        return "\e[35m{$text}\e[0m";
    }

    /**
     * Cyan Text
     * @return string
     */
    protected function txt_cyan(string $text): string
    {
        return "\e[36m{$text}\e[0m";
    }

    /**
     * White Text
     * @return string
     */
    protected function txt_white(string $text): string
    {
        return "\e[37m{$text}\e[0m";
    }
    /*=========================== FORGROUND COLOR END ===========================*/

    /*========================== BACKGROUND COLOR START ==========================*/
    /**
     * Black Background
     * @return string
     */
    protected function bg_black(string $text): string
    {
        return "\e[40m{$text}\e[0m";
    }

    /**
     * Red Background
     * @return string
     */
    protected function bg_red(string $text): string
    {
        return "\e[41m{$text}\e[0m";
    }

    /**
     * Green Background
     * @return string
     */
    protected function bg_green(string $text): string
    {
        return "\e[42m{$text} \e[0m";
    }

    /**
     * Yellow Background
     * @return string
     */
    protected function bg_yellow(string $text): string
    {
        return "\e[43m{$text}\e[0m";
    }

    /**
     * Blue Text
     * @return string
     */
    protected function bg_blue(string $text): string
    {
        return "\e[44m{$text}\e[0m";
    }

    /**
     * Magenta Text
     * @return string
     */
    protected function bg_magenta(string $text): string
    {
        return "\e[45m{$text}\e[0m";
    }

    /**
     * Cyan Text
     * @return string
     */
    protected function bg_cyan(string $text): string
    {
        return "\e[46m{$text}\e[0m";
    }

    /**
     * White Text
     * @return string
     */
    protected function bg_white(string $text): string
    {
        return "\e[47m{$text}\e[0m";
    }
    /*=========================== BACKGROUND COLOR END ===========================*/

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

    /**
     * @param string $message
     * This method is used to print informational messages to the console.
     * @return never
     */
    protected function success(string $message): never
    {
        // Green Text
        echo "\e[32m[{$this->txt_green('SUCCESS')}]>> \e[0m{$message}\n";
        exit(0);
    }

    protected function warning(string $message): never
    {
        // Green Text
        echo "\e[32m[{$this->txt_yellow('WARNING')}]>> \e[0m{$message}\n";
        exit(0);
    }

    /**
     * @param string $message
     * This method is used to print informational messages to the console.
     * @return never
     */
    protected function error(string $message): never
    {
        echo "\e[31m[{$this->txt_red('ERROR')}]>> \e[0m{$message}\n";
        exit(0);
    }
}
