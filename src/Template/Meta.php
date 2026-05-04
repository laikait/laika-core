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

namespace Laika\Core\Template;

use InvalidArgumentException;

class Meta
{
    /** @var array $metas */
    private static array $metas = [];

    /**
     * Add Meta
     * @param string $name
     * @param string $content
     * @param string $type
     * @throws InvalidArgumentException
     * @return void
     */
    public static function add(string $name, string $content, string $type = 'name'): void
    {
        if (!in_array($type, ['name', 'property'])) {
            throw new InvalidArgumentException("Invalid Meta Type [{$type}]. Accepted Types are name,property");
        }
        static::$metas[$name] = compact('content', 'type');
    }

    /**
     * Print Meta
     * @return void
     */
    public static function print(): void
    {
        // Metas
        foreach (static::$metas as $name => $meta) {
            $type    = htmlspecialchars($meta['type']);
            $name    = htmlspecialchars($name);
            $content = htmlspecialchars($meta['content']);
            echo "<meta {$type}=\"{$name}\" content=\"{$content}\">\n";
        }
    }
}
