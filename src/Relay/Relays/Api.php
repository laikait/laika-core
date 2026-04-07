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
 * @method static static setAllowedOrigin(string $origin = '*')
 * @method static static setMessage(string $message)
 * @method static string getContentType()
 * @method static string getMethod()
 * @method static array body()
 * @method static string bearerToken()
 * @method static never send(array $payload, int $status = 200, array $additional = [])
 */
class Api extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'api';
    }
}