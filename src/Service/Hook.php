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
 * @method static void add(string $hook, callable $callback, int $priority = 10)
 * @method static void do(string $hook, mixed ...$args)
 * @method static mixed apply(string $hook, mixed $default = null, mixed ...$args)
 */
class Hook extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'hook';
    }
}