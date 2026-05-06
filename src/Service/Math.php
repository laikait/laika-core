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
 * @method static string    add(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    sub(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    mul(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    div(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    mod(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    pow(int|float|string $number, int|float|string $exponent, ?int $scale = null)
 * @method static string    sqrt(int|float|string $number, ?int $scale = null)
 * @method static string    powmod(int|float|string $number, int|float|string $exponent, int|float|string $modulus, ?int $scale = null)
 * @method static int       compare(int|float|string $a, int|float|string $b, ?int $scale = null)
 * @method static string    abs(int|float|string $a)
 * @method static string    negate(int|float|string $a)
 * @method static string    floor(int|float|string $a)
 * @method static string    ceil(int|float|string $a)
 * @method static string    round(int|float|string $a, int|string $precision = 0)
 * @method static string    percent(int|float|string $value, int|float|string $total, ?int $scale = null)
 * @method static string    percentOf(int|float|string $percent, int|float|string $value, ?int $scale = null)
 * @method static string    max(int|float|string $a, int|float|string $b)
 * @method static string    min(int|float|string $a, int|float|string $b)
 * @method static string    sum(array $values, ?int $scale = null)
 * @method static string    avg(array $values, ?int $scale = null)
 * @method static bool      isZero(int|float|string $a)
 * @method static bool      isPositive(int|float|string $a)
 * @method static bool      isNegative(int|float|string $a)
 * @method static bool      isEqual(int|float|string $a, int|float|string $b)
 * @method static bool      isGt(int|float|string $a, int|float|string $b)
 * @method static bool      isLt(int|float|string $a, int|float|string $b)
 * @method static bool      isGte(int|float|string $a, int|float|string $b)
 * @method static bool      isLte(int|float|string $a, int|float|string $b)
 * @method static string    trim(int|float|string $a)
 */
class Math extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'math';
    }
}
