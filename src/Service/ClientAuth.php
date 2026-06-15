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
 * @method static static    setLifetime(int $ttl)
 * @method static string    login(int $userId, array $userData)
 * @method static bool      check()
 * @method static bool      guest()
 * @method static array     data()
 * @method static ?array    user()
 * @method static ?int      id()
 * @method static string    guard()
 * @method static void      logout()
 */
class ClientAuth extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'client.auth';
    }
}