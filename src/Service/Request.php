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
 * @method static string method()
 * @method static array headers()
 * @method static ?string header(string $key)
 * @method static bool isPost()
 * @method static bool isGet()
 * @method static bool isPut()
 * @method static bool isDelete()
 * @method static bool isPatch()
 * @method static bool isAjax()
 * @method static mixed input(string $key, mixed $default = null)
 * @method static array inputs()
 * @method static array only(array $keys)
 * @method static bool has(string $key)
 * @method static array array()
 * @method static ?array file(?string $key = null)
 * @method static string raw()
 * @method static array validate(array $rules, array $customMessages = [])
 * @method static void addBulkError(array $errors)
 * @method static void addError(string $key, string $error)
 * @method static array errors()
 */
class Request extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'request';
    }
}