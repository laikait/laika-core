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
 * @method static static init(array $fields)
 * @method static string|false single(string $directory, ?string $name = null, array $options = [])
 * @method static array multiple(string $destinationDir, array $options = [])
 */
class Upload extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'upload';
    }
}