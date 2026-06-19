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

namespace Laika\Core\Service;

use Laika\Core\Relay\Relay;

/**
 * @method static static    author(?string $type = null, ?int $id = null)
 * @method static static    log(string $log)
 * @method static void      event(string $event)
 * @method static array     events(?string $event = null)
 * @method static int       insert(?string $connection = null)
 * @method static array     changelog(array $existing)
 */
class Activity extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'activity';
    }
}
