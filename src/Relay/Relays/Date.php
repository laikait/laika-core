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
 * @method static static setTime(string $time)
 * @method static static setFormat(string $format)
 * @method static static setTimezone(string $timezone)
 * @method static static init()
 * @method static static instance()
 * @method static static now(?string $format = null, ?string $timezone = null)
 * @method static static modify(string $modifier)
 * @method static static setTimestamp(int $timestamp)
 * @method static int getTimestamp()
 * @method static string getTimezone()
 * @method static DateInterval diff(Date|DateTime $other)
 * @method static DateTime getDateTime()
 * @method static string toIso8601(bool $convertToUtc = false)
 * @method static static toUtc()
 * @method static static toLocal(string $timezone)
 * @method static array toArray()
 * @method static string humanDiff(Date|DateTime|null $other = null)
 * @method static string humanDiffShort(?Date $other = null)
 * @method static static fromFormat(string $format, string $time, ?string $outputFormat = null, ?string $timezone = null)
 * @method static string format(?string $format = null)
 */
class Date extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'date';
    }
}
