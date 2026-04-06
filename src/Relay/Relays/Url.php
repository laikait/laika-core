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
 * @method static string current()
 * @method static string base()
 * @method static string directory()
 * @method static string path()
 * @method static array queries()
 * @method static ?string query(string $key, ?string $default = null)
 * @method static string build(string $path, array $params = [])
 * @method static ?string segment(int $index)
 * @method static ?array segments()
 * @method static string withQuery(array $params)
 * @method static string withoutQuery(array $keys)
 * @method static string incrementQuery(?string $key = null)
 * @method static string decrementQuery(?string $key = null)
 * @method static bool isHttps()
 */
class Url extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'url';
    }
}