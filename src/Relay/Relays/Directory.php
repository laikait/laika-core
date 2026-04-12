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

namespace Laika\Core\Relay\Relays;

use Laika\Core\Relay\Relay;

/**
 * @method static array folders(string $path)
 * @method static array files(string $path, string|array $ext = '*')
 * @method static bool exists(string $path)
 * @method static bool make(string $path, int $permissions = 0755, bool $recursive = true)
 * @method static bool pop(string $path)
 * @method static bool empty(string $path)
 * @method static array scan(string $path, bool $includeDirs = true, string|array $ext = '*')
 */
class Directory extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'directory';
    }
}