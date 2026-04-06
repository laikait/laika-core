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

use Laika\Core\Regex\Abstracts\Rule;
use Laika\Core\Relay\Relay;

/**
 * @method static void addRule(Rule $rule)
 * @method static ?Rule getRule(string $name)
 * @method static array getRules()
 * @method static bool validate(string $ruleName, string $input, mixed ...$params)
 * @method static ?array match(string $ruleName, string $input)
 * @method static array checkRules(string $input)
 */
class Regex extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'regex';
    }
}