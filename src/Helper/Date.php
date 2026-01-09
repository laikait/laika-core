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
use DateInterval;
use DateTime;

class Date
{
    // DateTime Object
    protected DateTime $dateTime;

    // Date Format
    protected string $format;

    // Timezone
    protected string $timezone;

    /**
     * Initiate Date Class
     * @param string $time Optional Argument. Default is 'now'.
     * @param ?string $format Optional Argument. Default is null.
     * @param ?string $timezone Optional Argument. Default is null.
     */
    public function __construct(string $time = 'now', ?string $format = null, ?string $timezone = null)
    {
        $this->timezone = 'Europe/London';
        $this->format = $format ?: do_hook('option', 'time.format', 'Y-M-d H:i:s');
        $this->dateTime = new DateTime($time, new DateTimeZone($this->timezone));
    }

    /**
     * This Time
     * @param ?string $format Optional Argument. Default is null.
     * @param ?string $timezone Optional Argument. Default is null.
     * @return self
     */
    public static function now(?string $format = null, ?string $timezone = null): self
    {
        return new self('now', $format, $timezone);
    }

    /**
     * Get Formated DateTime
     * @param string Optional Argument. Default is null
     * @return string
     */
    public function format(?string $format = null): string
    {
        return $this->dateTime->format($format ?: $this->format);
    }

    /**
     * Modify DateTime
     * @param $modifier Required Argument. Example: '+1 day'
     * @return object
     */
    public function modify(string $modifier): static
    {
        $this->dateTime->modify($modifier);
        return $this;
    }

    /**
     * Set DateTime Format
     * @param $format Required Argument. Example: 'Y-m-d H:i:s'
     * @return object
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Get Timestamp
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    /**
     * Set Timestamp
     * @param int $timestamp Required Argument.
     * @return self
     */
    public function setTimestamp(int $timestamp): self
    {
        $this->dateTime->setTimestamp($timestamp);
        return $this;
    }

    /**
     * Set Timezone
     * @param string $timezone Required Argument. Example: 'UTC'
     * @return self
     */
    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        $this->dateTime->setTimezone(new DateTimeZone($this->timezone));
        return $this;
    }

    /**
     * Get Timezone
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Get Difference Between Two Dates
     * @param Date $other Required Argument.
     * @return DateInterval
     */
    public function diff(Date $other): DateInterval
    {
        return $this->dateTime->diff($other->dateTime);
    }

    /**
     * Get DateTime Object
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Convert to UTC
     * @return self
     */
    public function toUtc(): static
    {
        return $this->setTimezone('UTC');
    }

    /**
     * Convert to Local Timezone
     * @param string $timezone Required Argument. Example: 'America/New_York'
     * @return self
     */
    public function toLocal(string $timezone): static
    {
        return $this->setTimezone($timezone);
    }

    /**
     * Convert to Array
     * @return array{year:int,month:int,day:int,hour:int,minute:int,second:int,timezone:string,timestamp:int}
     */
    public function toArray(): array
    {
        return [
            'year'      =>  (int)$this->dateTime->format('Y'),
            'month'     =>  (int)$this->dateTime->format('m'),
            'day'       =>  (int)$this->dateTime->format('d'),
            'hour'      =>  (int)$this->dateTime->format('H'),
            'minute'    =>  (int)$this->dateTime->format('i'),
            'second'    =>  (int)$this->dateTime->format('s'),
            'timezone'  =>  $this->timezone,
            'timestamp' =>  $this->getTimestamp()
        ];
    }

    /**
     * Get Human Readable Difference
     * @param Date|null $other Optional Argument. Default is null.
     * @return string
     */
    public function humanDiff(?Date $other = null): string
    {
        $other = $other ?: Date::now($this->format, $this->timezone);
        $diff = $this->diff($other);

        $units = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        foreach ($units as $key => $text) {
            $value = (int) $diff->$key;
            if ($value > 0) {
                $plural = $value > 1 ? 's' : '';
                return $diff->invert
                    ? "in {$value} {$text}{$plural}"
                    : "{$value} {$text}{$plural} ago";
            }
        }

        return 'just now';
    }

    /**
     * Get Short Human Readable Difference
     * @param Date|null $other Optional Argument. Default is null.
     * @return string
     */
    public function humanDiffShort(?Date $other = null): string
    {
        $other = $other ?: Date::now($this->format, $this->timezone);
        $diff = $this->diff($other);

        $units = [
            'y' => 'y',
            'm' => 'mo',
            'd' => 'd',
            'h' => 'h',
            'i' => 'm',
            's' => 's',
        ];

        foreach ($units as $key => $abbr) {
            $value = (int)$diff->$key;
            if ($value > 0) {
                return $diff->invert
                    ? "in {$value}{$abbr}"
                    : "{$value}{$abbr} ago";
            }
        }

        return 'now';
    }

    /**
     * Create Date from Format
     * @param string $format Required Argument. Example: 'Y-m-d H:i:s'
     * @param string $time Required Argument. Example: '2024-01-01 12:00:00'
     * @param ?string $outputFormat Optional Argument. Default is 'Y-m-d H:i:s'.
     * @param ?string $timezone Optional Argument. Default is 'UTC'.
     * @return self
     */
    public static function fromFormat(
        string $format,
        string $time,
        ?string $outputFormat = null,
        ?string $timezone = null
    ): self {
        $format = $format ?: do_hook('option', 'time.format', 'Y-M-d H:i:s');
        $tz = new DateTimeZone($timezone);
        $dt = DateTime::createFromFormat($format, $time, $tz);
        $instance = new self('now', $outputFormat, $timezone);
        $instance->dateTime = $dt instanceof DateTime ? $dt : new DateTime('now', $tz);
        return $instance;
    }

    /**
     * String Representation
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
