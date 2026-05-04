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
 * @method static static setType(string $type = 'CLIENT')
 * @method static static setConnection(string $connection = 'default')
 * @method static static setTtl(int $ttl)
 * @method static void init()
 * @method static string create(array $user)
 * @method static ?array user()
 * @method static string regenerate()
 * @method static void destroy(bool $soft = false)
 */
class Auth extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'auth';
    }
}