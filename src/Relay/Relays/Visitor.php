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
 * @method static string userAgent()
 * @method static ?string ip()
 * @method static string language()
 * @method static string os()
 * @method static string browser()
 * @method static string deviceType()
 * @method static bool isBot()
 * @method static array info()
 */
class Visitor extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'visitor';
    }
}