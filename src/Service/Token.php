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
 * @method static void setTtl(int $ttl)
 * @method static string generate(?array $user = null)
 * @method static bool validateToken(?string $token)
 * @method static bool check()
 * @method static ?array user()
 * @method static void flush()
 * @method static ?string refresh(string $token)
 */
class Token extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'token';
    }
}