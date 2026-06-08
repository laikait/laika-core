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
 * @method static static    status(int $code)
 * @method static int       getStatus()
 * @method static static    contentType(string $type)
 * @method static string    getContentType()
 * @method static static    setHeader(string $name, string $value)
 * @method static static    setHeaders(array $headers)
 * @method static ?string   getHeader(string $name)
 * @method static array     getHeaders()
 * @method static static    removeHeader(string $name)
 * @method static ?string   requestHeader(string $name)
 * @method static array     requestHeaders()
 * @method static static    body(mixed $body)
 * @method static mixed     getBody()
 * @method static static    json(mixed $data, int $status = 200)
 * @method static static    html(string $html, int $status = 200)
 * @method static static    text(string $text, int $status = 200)
 * @method static static    noContent()
 * @method static void      send()
 * @method static array     statusCodes()
 */
class Response extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'response';
    }
}