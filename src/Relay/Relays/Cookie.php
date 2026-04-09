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
 * @method static static policy(string $policy)
 * @method static static ttl(int $ttl)
 * @method static static httponly(bool $httponly = true)
 * @method static static path(string $path)
 * @method static bool set(string $name, mixed $value)
 * @method static mixed get(string $name, mixed $default = null)
 * @method static string pop(string $name)
 */
class Cookie extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'cookie';
    }
}