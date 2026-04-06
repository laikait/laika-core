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
 * @method static static setKey(string $key)
 * @method static static setTtl(int $ttl)
 * @method static string generate()
 * @method static string get()
 * @method static string refresh()
 * @method static string reset()
 * @method static string field()
 * @method static bool is_valid()
 */
class Csrf extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'csrf';
    }
}