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

class Unique
{
    /**
     * @param string $prefix Example: 'inv-'. Default is '';
     */
    public function __construct(private string $prefix = '', private bool $upper = false) {}

    /**
     * Generate Pattern As Per Input
     * @param string $pattern Example: {y}, {d}, {m}, {s}, {c}, {n}. Minimum 3 Identifiers Required
     * @param null|int|string $suffix It Will Concat At The End of The String. Example: 1, '-end'
     * @return string
     */
    public function generate(string $pattern, null|int|string $suffix = null): string
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

        $str = $this->prefix . $result . (string) ($suffix ?? '');

        return $this->upper ? strtoupper($str) : $str;
    }

    /*================================ INTERNAL API ================================*/

    // 1 random alphanumeric character (a-z)
    private function singleChar(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        return $chars[random_int(0, strlen($chars) - 1)];
    }

    // 1 random digit  0-9
    private function singleNumber(): string
    {
        return (string) random_int(0, 9);
    }

    /**
     * Ensures the pattern contains at least MIN_TOKENS valid tokens.
     * @param string $pattern String to Validate
     * @throws \InvalidArgumentException when fewer than MIN_TOKENS tokens are found
     * @return string
     */
    private function validatePattern(string $pattern): string
    {
        // Sanitize Pattern
        $pattern = preg_replace('/\s+/', '', $pattern);
        $validTokens = ['{Y}', '{y}', '{d}', '{m}', '{G}', '{H}', '{s}', '{c}', '{n}'];
        $minTokens = 3;
        $count = 0;

        foreach ($validTokens as $token) {
            // substr_count handles repeated tokens, e.g. {c}{c} counts as 2
            $count += substr_count($pattern, $token);
        }

        if ($count < $minTokens) {
            throw new \InvalidArgumentException(
                "Pattern [{$pattern}] contains only {$count} identifier(s). A minimum of {$minTokens} identifiers is required. Available tokens: '{Y}', '{y}', '{d}', '{m}', '{G}', '{H}', '{s}', '{c}', '{n}'.");
        }

        return $pattern;
    }
}
