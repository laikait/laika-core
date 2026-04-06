<?php
/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

// Namespace
namespace Laika\Core\Generator;

use InvalidArgumentException;

class Unique
{
    /** @var array $tokens */
    protected array $tokens = ['{Y}', '{y}', '{d}', '{m}', '{G}', '{H}', '{s}', '{c}', '{n}'];

    /*##########################################################################*/
    /*############################### PUBLIC API ###############################*/
    /*##########################################################################*/
    /**
     * Generate Pattern As Per Input
     * @param string $pattern Example: {y}, {d}, {m}, {s}, {c}, {n}. Minimum 3 Identifiers Required
     * @param string $prefix Example: 'inv'
     * @param string $suffix Example: '-1'
     * @return string
     */
    public function generate(string $pattern, string $prefix = '', string $suffix = ''): string
    {
        // Validate Pattern
        $result = $this->validatePattern($pattern);

        // Replace every token individually via callback
        $result = preg_replace_callback('/\{Y\}|\{y\}|\{d\}|\{m\}|\{G\}|\{H\}|\{s\}|\{c\}|\{n\}/', function (array $match): string {
            return match ($match[0]) {
                '{Y}' => date('Y'),
                '{y}' => date('y'),
                '{d}' => date('d'),
                '{m}' => date('i'),
                '{G}' => date('G'),
                '{H}' => date('H'),
                '{s}' => date('s'),
                '{c}' => $this->singleChar(),
                '{n}' => $this->singleNumber(),
            };
        }, $result);

        return "{$prefix}{$result}{$suffix}";
    }

    /*###########################################################################*/
    /*############################### PRIVATE API ###############################*/
    /*###########################################################################*/

    // 1 random alphanumeric character (a-z)
    protected function singleChar(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        return $chars[random_int(0, strlen($chars) - 1)];
    }

    // 1 random digit  0-9
    protected function singleNumber(): string
    {
        return (string) random_int(0, 9);
    }

    /**
     * Ensures the pattern contains at least MIN_TOKENS valid tokens.
     * @param string $pattern String to Validate
     * @throws InvalidArgumentException when fewer than MIN_TOKENS tokens are found
     * @return string
     * @throws InvalidArgumentException
     */
    protected function validatePattern(string $pattern): string
    {
        // Sanitize Pattern
        $pattern = preg_replace('/\s+/', '', $pattern);
        $minTokens = 3;
        $count = 0;

        foreach ($this->tokens as $token) {
            // substr_count handles repeated tokens, e.g. {c}{c} counts as 2
            $count += substr_count($pattern, $token);
        }

        if ($count < $minTokens) {
            $tokens = join(', ', $this->tokens);
            throw new InvalidArgumentException("Pattern [{$pattern}] contains only {$count} identifier(s). A minimum of {$minTokens} identifiers is required. Available tokens: [{$tokens}].");}

        return $pattern;
    }
}
