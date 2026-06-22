<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

use InvalidArgumentException;

final class PhpMetadataParser
{
    /**
     * Parse metadata from the first PHPDoc block in a PHP file.
     * Supported format:
     *
     * /**
     *  * Name: Blog Module
     *  * Version: 1.0.0
     *  * Author: Showket Ahmed
     *  * Description: Example package description.
     *  *\/
     *
     * Result:
     *
     * [
     *     'name'        => 'Blog Module',
     *     'version'     => '1.0.0',
     *     'author'      => 'Showket Ahmed',
     *     'description' => 'Example package description',
     * ]
     *
     * @param string $file Path to the PHP file.
     * @return array<string,string> Associative array of extracted metadata.
     * @throws InvalidArgumentException If the file does not exist or is not readable.
     */
    public static function parse(string $file): array
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new InvalidArgumentException("Invalid file path: {$file}");
        }

        $meta = [];
        $tokens = token_get_all(file_get_contents($file));

        foreach ($tokens as $token) {
            if (
                is_array($token) &&
                isset($token[0], $token[1]) &&
                $token[0] === T_DOC_COMMENT
            ) {
                $lines = preg_split('/\R/', $token[1]) ?: [];

                foreach ($lines as $line) {
                    $line = trim($line, " \t\n\r\0\x0B/*");

                    if ($line === '' || !str_contains($line, ':')) {
                        continue;
                    }

                    [$key, $value] = explode(':', $line, 2);

                    $meta[
                        strtolower(
                            str_replace(' ', '-', trim($key))
                        )
                    ] = trim($value);
                }

                // Only parse the first PHPDoc block.
                break;
            }
        }

        return $meta;
    }
}
