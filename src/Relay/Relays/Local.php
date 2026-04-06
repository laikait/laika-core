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
 * @method static void set(string $lang = 'en')
 * @method static string get()
 * @method static void setPath(string $path)
 * @method static void load()
 */
class Local extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'local';
    }
}