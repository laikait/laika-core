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
 * @method static mixed get(string $name, ?string $key = null, mixed $default = null)
 * @method static void  set(string $name, string $key, null|int|string|bool|array $value)
 * @method static bool  has(string $name, ?string $key = null)
 * @method static bool pop(string $name, string $key)
 * @method static bool create(string $name, array $data)
 */
class Config extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'config';
    }
}
