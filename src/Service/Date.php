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
 * @method static DateHelper now()
 * @method static DateHelper fromFormat(string $format, string $time, ?string $outputFormat = null, ?string $timezone = null)
 * @method static DateHelper    parse(string $time, ?string $timezone = null)
 * @method static string        format(?string $format = null)
 * @method static int           getTimestamp()
 * @method static DateHelper    setFormat(string $format)
 * @method static DateHelper    setTimestamp(int $timestamp)
 * @method static DateHelper    setTimezone(string $timezone)
 * @method static DateHelper    modify(string $modifier)
 * @method static DateHelper    toLocal()
 * @method static string        toIso8601(bool $extended = true)
 * @method static array         toArray()
 * @method static string        humanDiff(?DateHelper $other = null)
 * @method static string        humanDiffShort(?DateHelper $other = null)
 * @method static string        humanDiffShort(?DateHelper $other = null)
 * @method static void          setAppTimezone(string $timezone)
 * @method static string        getAppTimezone()
 * @method static string        getOffset()
 */
class Date extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'date';
    }
}
