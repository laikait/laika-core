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
 * @method static static addExisting(array $array) Add Existing Value Example: ['name' => 'John Doe', 'email' => '
 * @method static static addNew(array $array) Add New Value Example: ['name' => 'John Doe', 'email' => '
 * @method static array getLogs() Get Change Logs
 */
class ChangeLog extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'changelog';
    }
}
