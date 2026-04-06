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
 * @method static int code(int $code = 200)
 * @method static void poweredBy(string $str)
 * @method static void register()
 * @method static void set(array $headers = [])
 * @method static array|string get(?string $key = null)
 * @method static array statusCodes()
 */
class Header extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'header';
    }
}