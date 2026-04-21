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

namespace Laika\Core\Helper;

use DateTimeZone;
use DateTime;

class Date
{
    protected DateTime $dt;
    protected string $format;
    protected string $timezone;

    public function __construct(
        string $timezone = 'UTC',
        string $format = 'Y-m-d H:i:s'
    ) {
        $this->timezone = $timezone;
        $this->format   = $format;
        $this->dt       = new DateTime('now', new DateTimeZone($timezone));
    }

    ##############################################################################
    /*=============================== PUBLIC API ===============================*/
    ##############################################################################

    /**
     * Return a new instance set to the current date and time.
     * @return static
     * @example Date::now()->format()  // "2025-04-21 10:30:00"
     */
    public function now(): static
    {
        $clone     = clone $this;
        $clone->dt = new DateTime('now', new DateTimeZone($this->timezone));
        return $clone;
    }

    /**
     * Parse a date string from a specific format.
     * @param  string      $format       Input format,  e.g. "d/m/Y"
     * @param  string      $time         Date string,   e.g. "21/04/2025"
     * @param  string|null $outputFormat Output format, e.g. "Y-m-d"
     * @param  string|null $timezone     Timezone,      e.g. "Asia/Dhaka"
     * @return static
     * @example Date::fromFormat('d/m/Y', '21/04/2025', 'Y-m-d')->format()  // "2025-04-21"
     */
    public function fromFormat(
        string  $format,
        string  $time,
        ?string $outputFormat = null,
        ?string $timezone     = null
    ): static {
        $clone         = clone $this;
        $tz            = new DateTimeZone($timezone ?? $this->timezone);
        $clone->dt     = DateTime::createFromFormat($format, $time, $tz) ?: new DateTime($time, $tz);
        $clone->format = $outputFormat ?? $this->format;
        return $clone;
    }

    /**
     * Parse any date/time string understood by PHP.
     * @param  string      $time     Date string, e.g. "2025-04-21", "next Monday", "-1 week"
     * @param  string|null $timezone Timezone,    e.g. "America/New_York"
     * @return static
     * @example Date::parse('next Monday')->format('l, d M Y')  // "Monday, 28 Apr 2025"
     * @example Date::parse('-1 week')->format()                // "2025-04-14 10:30:00"
     */
    public function parse(string $time, ?string $timezone = null): static
    {
        $clone     = clone $this;
        $clone->dt = new DateTime($time, new DateTimeZone($timezone ?? $this->timezone));
        return $clone;
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    /**
     * Format the date using the given or default format string.
     * @param  string|null $format PHP date format, e.g. "d M Y", "H:i"
     * @return string
     * @example Date::now()->format()         // "2025-04-21 10:30:00"
     * @example Date::now()->format('d M Y')  // "21 Apr 2025"
     */
    public function format(?string $format = null): string
    {
        return $this->dt->format($format ?? $this->format);
    }

    /**
     * Get the Unix timestamp.
     * @return int
     * @example Date::now()->getTimestamp()  // 1745229000
     */
    public function getTimestamp(): int
    {
        return $this->dt->getTimestamp();
    }

    /**
     * Get the underlying PHP DateTime object.
     * @return DateTime
     * @example Date::now()->getDateTime()->format('U')  // "1745229000"
     */
    public function getDateTime(): DateTime
    {
        return $this->dt;
    }

    // ── Setters (fluent) ──────────────────────────────────────────────────────

    /**
     * Set the default output format for this instance.
     * @param  string $format PHP date format, e.g. "d/m/Y"
     * @return static
     * @example Date::now()->setFormat('d/m/Y')->format()  // "21/04/2025"
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Set the date/time from a Unix timestamp.
     * @param  int $timestamp Unix timestamp, e.g. 1745229000
     * @return static
     * @example Date::now()->setTimestamp(0)->format()  // "1970-01-01 00:00:00"
     */
    public function setTimestamp(int $timestamp): static
    {
        $clone     = clone $this;
        $clone->dt = (clone $this->dt)->setTimestamp($timestamp);
        return $clone;
    }

    /**
     * Change the timezone of the current instance.
     * @param  string $timezone IANA timezone, e.g. "Asia/Dhaka"
     * @return static
     * @example Date::now()->setTimezone('Asia/Dhaka')->format()  // "2025-04-21 16:30:00"
     */
    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        $this->dt->setTimezone(new DateTimeZone($timezone));
        return $this;
    }

    /**
     * Apply a relative date/time modifier string.
     * @param  string $modifier PHP modifier string, e.g. "+1 day", "-2 hours", "next Friday"
     * @return static
     * @example Date::now()->modify('+7 days')->format()   // "2025-04-28 10:30:00"
     * @example Date::now()->modify('-2 hours')->format()  // "2025-04-21 08:30:00"
     */
    public function modify(string $modifier): static
    {
        $clone = clone $this;
        $clone->dt = (clone $this->dt)->modify($modifier);
        return $clone;
    }

    /*================================ CONVERT ================================*/

    /**
     * Convert the instance back to the default (local) timezone.
     * @return static
     * @example Date::now()->toUtc()->toLocal()->format()  // "2025-04-21 10:30:00"
     */
    public function toLocal(): static
    {
        $clone     = clone $this;
        $clone->dt = (clone $this->dt)->setTimezone(new DateTimeZone($this->timezone));
        return $clone;
    }

    /**
     * Return the date as an ISO 8601 string.
     * @param  bool $extended true = ATOM format "2025-04-21T10:30:00+06:00", false = ISO8601 "20250421T103000+0600"
     * @return string
     * @example Date::now()->toIso8601()        // "2025-04-21T10:30:00+06:00"
     * @example Date::now()->toIso8601(false)   // "20250421T103000+0600"
     */
    public function toIso8601(bool $extended = true): string
    {
        return $this->dt->format($extended ? \DateTimeInterface::ATOM : \DateTimeInterface::ISO8601);
    }

    /**
     * Return the date components as an associative array.
     *
     * @return array{year: int, month: int, day: int, hour: int, minute: int, second: int, timezone: string}
     * @example Date::now()->toArray()
     * // ['year'=>2025, 'month'=>4, 'day'=>21, 'hour'=>10, 'minute'=>30, 'second'=>0, 'timezone'=>'Europe/London']
     */
    public function toArray(): array
    {
        return [
            'year'     => (int) $this->dt->format('Y'),
            'month'    => (int) $this->dt->format('m'),
            'day'      => (int) $this->dt->format('d'),
            'hour'     => (int) $this->dt->format('H'),
            'minute'   => (int) $this->dt->format('i'),
            'second'   => (int) $this->dt->format('s'),
            'timezone' => $this->dt->getTimezone()->getName(),
        ];
    }

    // ── Diff / Human ──────────────────────────────────────────────────────────

    /**
     * Get the DateInterval between this instance and another.
     * @param  Date $other Another Date instance to compare against
     * @return \DateInterval
     *
     * @example Date::parse('2025-01-01')->diff(Date::now())->days  // 110
     */
    public function diff(Date $other): \DateInterval
    {
        return $this->dt->diff($other->getDateTime());
    }

    /**
     * Return a human-readable relative time string (long form).
     * @param  Date|null $other Compare against this instance; defaults to now
     * @return string
     * @example Date::parse('-3 days')->humanDiff()   // "3 days ago"
     * @example Date::parse('-1 hour')->humanDiff()   // "1 hour ago"
     * @example Date::parse('-30 seconds')->humanDiff() // "just now"
     */
    public function humanDiff(?Date $other = null): string
    {
        $secs = abs($this->dt->getTimestamp() - ($other?->getTimestamp() ?? time()));

        return match (true) {
            $secs < 60       => 'just now',
            $secs < 3600     => (int)($secs / 60) . ' minute' . ((int)($secs / 60) !== 1 ? 's' : '') . ' ago',
            $secs < 86400    => (int)($secs / 3600) . ' hour' . ((int)($secs / 3600) !== 1 ? 's' : '') . ' ago',
            $secs < 2592000  => (int)($secs / 86400) . ' day' . ((int)($secs / 86400) !== 1 ? 's' : '') . ' ago',
            $secs < 31536000 => (int)($secs / 2592000) . ' month' . ((int)($secs / 2592000) !== 1 ? 's' : '') . ' ago',
            default          => (int)($secs / 31536000) . ' year' . ((int)($secs / 31536000) !== 1 ? 's' : '') . ' ago',
        };
    }

    /**
     * Return a human-readable relative time string (short form).
     * @param  ?Date $other Compare against this instance; defaults to now
     * @return string
     * @example Date::parse('-3 days')->humanDiffShort()   // "3d"
     * @example Date::parse('-2 hours')->humanDiffShort()  // "2h"
     * @example Date::parse('-45 seconds')->humanDiffShort() // "now"
     */
    public function humanDiffShort(?Date $other = null): string
    {
        $secs = abs($this->dt->getTimestamp() - ($other?->getTimestamp() ?? time()));

        return match (true) {
            $secs < 60       => 'now',
            $secs < 3600     => (int)($secs / 60) . 'm',
            $secs < 86400    => (int)($secs / 3600) . 'h',
            $secs < 2592000  => (int)($secs / 86400) . 'd',
            $secs < 31536000 => (int)($secs / 2592000) . 'mo',
            default          => (int)($secs / 31536000) . 'y',
        };
    }

    /**
     * Set the PHP application-level default timezone.
     * @param  string $timezone IANA timezone, e.g. "Asia/Dhaka"
     * @return void
     * @example Date::setAppTimezone('Asia/Dhaka')  // date_default_timezone_set('Asia/Dhaka')
     */
    public function setAppTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        date_default_timezone_set($timezone);
        $this->dt->setTimezone(new DateTimeZone($timezone));
    }

    /**
     * Get the current PHP application-level default timezone.
     * @return string
     * @example Date::getAppTimezone()  // "Asia/Dhaka"
     */
    public function getAppTimezone(): string
    {
        return date_default_timezone_get();
    }

    /**
     * Cast the instance to its formatted string.
     * @return string
     * @example (string) Date::now()  // "2025-04-21 10:30:00"
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
