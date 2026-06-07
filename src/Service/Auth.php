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
 * @method static static    setLifeTime(int $ttl)
 * @method static string    login(string $userType, int $userId, array $userData = [])
 * @method static bool      check()
 * @method static bool      guest()
 * @method static array     user()
 * @method static ?int      id()
 * @method static ?string   type()
 * @method static void      logout()
 */
class Auth extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'auth';
    }
}