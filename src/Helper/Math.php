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

use Laika\Core\Exceptions\ExtensionException;
use DivisionByZeroError;

class Math
{
    /** @var int $scale */
    protected int $scale = 4;

    public function __construct()
    {
        // Check bcmath Extension Loaded
        if (!extension_loaded('bcmath')) {
            throw new ExtensionException('Extension Not Loaded [bcmath]!', 500);
        }
    }

    /**
     * Set Global Scale
     * @param int $scale
     * @return void
     */
    public function scale(int $scale): void
    {
        $this->scale = $scale;
    }

    /**
     * Addition
     * @param int|float|string $a
     * @param int|float|string $b
     * @param ?int $scale
     * @return string
     */
    public function add(int|float|string $a, int|float|string $b, ?int $scale = null): string
    {
        return bcadd((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Subtraction
     * @param int|float|string $a
     * @param int|float|string $b
     * @param ?int $scale
     * @return string
     */
    public function sub(int|float|string $a, int|float|string $b, ?int $scale = null): string
    {
        return bcsub((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Multiply
     * @param string $a
     * @param string $b
     * @param ?int $scale
     * @return string
     */
    public function mul(int|float|string $a, int|float|string $b, ?int $scale = null): string
    {
        return bcmul((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Divide
     * @param int|float|string $a
     * @param int|float|string $b
     * @param ?int $scale
     * @throws DivisionByZeroError
     * @return string
     */
    public function div(int|float|string $a, int|float|string $b, ?int $scale = null): string
    {
        if (bccomp((string) $b, '0', $this->scale) === 0) {
            throw new DivisionByZeroError('Division by zero.');
        }
        return bcdiv((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Modulus
     * @param int|float|string $a
     * @param int|float|string $b
     * @param ?int $scale
     * @throws DivisionByZeroError
     * @return string
     */
    public function mod(int|float|string $a, int|float|string $b, ?int $scale = null): string
    {
        if (bccomp((string) $b, '0', $this->scale) === 0) {
            throw new DivisionByZeroError('Division by zero.');
        }
        return bcmod((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Power
     * @param int|float|string $number
     * @param int|float|string $exponent
     * @param ?int $scale
     * @return string
     */
    public function pow(int|float|string $number, int|float|string $exponent, ?int $scale = null): string
    {
        return bcpow((string) $number, (string) $exponent, $scale ?? $this->scale);
    }

    /**
     * Square root
     * @param int|float|string $number
     * @param ?int $scale
     * @throws \InvalidArgumentException
     * @return string
     */
    public function sqrt(int|float|string $number, ?int $scale = null): string
    {
        if (bccomp((string) $number, '0', $this->scale) < 0) {
            throw new \InvalidArgumentException('Square root of negative number.');
        }
        return bcsqrt((string) $number, $scale ?? $this->scale);
    }

    /**
     * Power with modulus: (base ^ exp) % mod
     * @param int|float|string $number
     * @param int|float|string $exponent
     * @param string $modulus
     * @param ?int $scale
     * @return string
     */
    public function powmod(int|float|string $number, int|float|string $exponent, int|float|string $modulus, ?int $scale = null): string
    {
        return bcpowmod($this->floor($number), (string) $exponent, (string) $modulus, $scale ?? $this->scale);
    }

    /**
     * Compare: returns -1, 0, 1
     * @param int|float|string $a
     * @param int|float|string $b
     * @param ?int $scale
     * @return int
     */
    public function compare(int|float|string $a, int|float|string $b, ?int $scale = null): int
    {
        return bccomp((string) $a, (string) $b, $scale ?? $this->scale);
    }

    /**
     * Absolute value
     * @param int|float|string $a
     * @return string
     */
    public function abs(int|float|string $a): string
    {
        return bccomp((string) $a, '0', $this->scale) < 0
            ? bcmul((string) $a, '-1', $this->scale)
            : $a;
    }

    /**
     * Negate
     * @param int|float|string $a
     * @return string
     */
    public function negate(int|float|string $a): string
    {
        return bcmul((string) $a, '-1', $this->scale);
    }

    /**
     * Floor
     * @param int|float|string $a
     * @return string
     */
    public function floor(int|float|string $a): string
    {
        $result = bcdiv((string) $a, '1', 0);
        if (bccomp((string) $a, '0', $this->scale) < 0 && bccomp($result, (string) $a, $this->scale) !== 0) {
            $result = bcsub($result, '1', 0);
        }
        return $result;
    }

    /**
     * Ceil
     * @param int|float|string $a
     * @return string
     */
    public function ceil(int|float|string $a): string
    {
        $result = bcdiv((string) $a, '1', 0);
        if (bccomp((string) $a, '0', $this->scale) > 0 && bccomp($result, (string) $a, $this->scale) !== 0) {
            $result = bcadd($result, '1', 0);
        }
        return $result;
    }

    /**
     * Round
     * @param int|float|string $a
     * @param int|string $precision
     * @return string
     */
    public function round(int|float|string $a, int|string $precision = 0): string
    {
        $a = (string) $a;
        $precision = (int) $precision;
        $sign = bccomp($a, '0', $this->scale) < 0 ? '-' : '';
        $shift = bcpow('10', (string) $precision, 0);
        $shifted = bcmul(static::abs($a), $shift, $precision + 1);
        $floored = bcdiv($shifted, '1', 0);
        $decimal = bcsub($shifted, $floored, 1);
        if (bccomp($decimal, '0.5', 1) >= 0) {
            $floored = bcadd($floored, '1', 0);
        }
        return $sign . bcdiv($floored, $shift, $precision);
    }

    /**
     * Percentage: what % of total
     * @param string $value
     * @param string $total
     * @param mixed $scale
     * @throws DivisionByZeroError
     * @return string
     */
    public function percent(int|float|string $value, int|float|string $total, ?int $scale = null): string
    {
        if (bccomp((string) $total, '0', $this->scale) === 0) {
            throw new DivisionByZeroError('Total cannot be zero.');
        }
        return bcdiv(bcmul((string) $value, '100', $this->scale), (string) $total, $scale ?? $this->scale);
    }

    /**
     * Percentage of: x% of value
     * @param string $percent
     * @param string $value
     * @param mixed $scale
     * @return string
     */
    public function percentOf(int|float|string $percent, int|float|string $value, ?int $scale = null): string
    {
        return bcdiv(bcmul((string) $percent, (string) $value, $this->scale), '100', $scale ?? $this->scale);
    }

    /**
     * Max of two
     * @param int|float|string $a
     * @param int|float|string $b
     * @return string
     */
    public function max(int|float|string $a, int|float|string $b): string
    {
        return bccomp((string) $a, (string) $b, $this->scale) >= 0 ? (string) $a : (string) $b;
    }

    /**
     * Min of two
     * @param int|float|string $a
     * @param int|float|string $b
     * @return string
     */
    public function min(int|float|string $a, int|float|string $b): string
    {
        return bccomp((string) $a, (string) $b, $this->scale) <= 0 ? (string) $a : (string) $b;
    }

    /**
     * Sum of array
     * @param array $values
     * @param ?int $scale
     * @return string
     */
    public function sum(array $values, ?int $scale = null): string
    {
        $result = '0';
        foreach ($values as $v) {
            $result = bcadd($result, (string) $v, $scale ?? $this->scale);
        }
        return $result;
    }

    /**
     * Average of array
     * @param array $values
     * @param ?int $scale
     * @throws \InvalidArgumentException
     * @return string
     */
    public function avg(array $values, ?int $scale = null): string
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Array is empty.');
        }
        $sum = static::sum($values, $scale);
        return bcdiv($sum, (string) count($values), $scale ?? $this->scale);
    }

    /**
     * Is zero
     * @param int|float|string $a
     * @return bool
     */
    public function isZero(int|float|string $a): bool
    {
        return bccomp((string) $a, '0', $this->scale) === 0;
    }

    /**
     * Is positive
     * @param int|float|string $a
     * @return bool
     */
    public function isPositive(int|float|string $a): bool
    {
        return bccomp((string) $a, '0', $this->scale) > 0;
    }

    /**
     * Is negative
     * @param int|float|string $a
     * @return bool
     */
    public function isNegative(int|float|string $a): bool
    {
        return bccomp((string) $a, '0', $this->scale) < 0;
    }

    /**
     * Is equal
     * @param int|float|string $a
     * @param int|float|string $b
     * @return bool
     */
    public function isEqual(int|float|string $a, int|float|string $b): bool
    {
        return bccomp((string) $a, (string) $b, $this->scale) === 0;
    }

    /**
     * Is greater than
     * @param int|float|string $a
     * @param int|float|string $b
     * @return bool
     */
    public function isGt(int|float|string $a, int|float|string $b): bool
    {
        return bccomp((string) $a, (string) $b, $this->scale) > 0;
    }

    /**
     * Is less than
     * @param int|float|string $a
     * @param int|float|string $b
     * @return bool
     */
    public function isLt(int|float|string $a, int|float|string $b): bool
    {
        return bccomp((string) $a, (string) $b, $this->scale) < 0;
    }

    /**
     * Is greater than or equal
     * @param int|float|string $a
     * @param int|float|string $b
     * @return bool
     */
    public function isGte(int|float|string $a, int|float|string $b): bool
    {
        return bccomp((string) $a, (string) $b, $this->scale) >= 0;
    }

    /**
     * Is less than or equal
     * @param int|float|string $a
     * @param int|float|string $b
     * @return bool
     */
    public function isLte(int|float|string $a, int|float|string $b): bool
    {
        return bccomp((string) $a, (string) $b, $this->scale) <= 0;
    }

    /**
     * Format: trim trailing zeros
     * @param int|float|string $a
     * @return string
     */
    public function trim(int|float|string $a): string
    {
        $a = (string) $a;
        if (str_contains($a, '.')) {
            $a = rtrim($a, '0');
            $a = rtrim($a, '.');
        }
        return $a;
    }
}
