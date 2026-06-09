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
 * @method static ?string   single(string $key, ?string $default = null)
 * @method static bool      insert(string $key, mixed $value)
 * @method static bool      update(string $key, mixed $value)
 */
class Option extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'option';
    }
}