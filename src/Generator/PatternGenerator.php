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

class PatternGenerator
{
    /**
     * @param string $prefix Example: 'inv-'. Default is '';
     */
    public function __construct(private string $prefix = '') {}

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

        $result = str_replace('{y}', $this->getYear(), $result);
        $result = str_replace('{d}', $this->getDay(), $result);
        $result = str_replace('{m}', $this->getMinute(), $result);
        $result = str_replace('{s}', $this->getSecond(), $result);
        $result = str_replace('{c}', $this->getChar(), $result);
        $result = str_replace('{n}', $this->getNumber(), $result);

        return $this->prefix . $result . (string) $suffix;
    }

    /*================================ INTERNAL API ================================*/

    // 2-digit year  e.g. 25
    private function getYear(): string
    {
        return date('y');
    }

    // 2-digit day of the month  e.g. 07
    private function getDay(): string
    {
        return date('d');
    }

    // 2-digit minute  e.g. 04
    private function getMinute(): string
    {
        return date('i');
    }

    // 2-digit second  e.g. 59
    private function getSecond(): string
    {
        return date('s');
    }

    // 1 random alphanumeric character (a-z, A-Z, 0-9)
    private function getChar(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        return $chars[random_int(0, strlen($chars) - 1)];
    }

    // 1 random digit  0-99
    private function getNumber(): string
    {
        return (string) random_int(0, 99);
    }

    /**
     * Ensures the pattern contains at least MIN_TOKENS valid tokens.
     *
     * @throws \InvalidArgumentException when fewer than MIN_TOKENS tokens are found
     * @return string
     */
    private function validatePattern(string $pattern): string
    {
        // Sanitize Pattern
        $pattern = preg_replace('/\s+/', '', $pattern);
        $validTokens = ['{y}', '{d}', '{m}', '{s}', '{c}', '{n}'];
        $minTokens = 3;
        $count = 0;

        foreach ($validTokens as $token) {
            // substr_count handles repeated tokens, e.g. {c}{c} counts as 2
            $count += substr_count($pattern, $token);
        }

        if ($count < $minTokens) {
            throw new \InvalidArgumentException(
                "Pattern [{$pattern}] contains only {$count} identifier(s). A minimum of {$minTokens} identifiers is required. Available tokens: {y}, {d}, {m}, {s}, {c}, {n}.");
        }

        return $pattern;
    }
}
