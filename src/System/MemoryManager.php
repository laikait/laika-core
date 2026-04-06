<?php

declare(strict_types=1);

namespace Laika\Core\System;

use Laika\Core\Relay\Relays\Config;

final class MemoryManager
{
    /** @var bool $monitorRegistered */
    private static bool $monitorRegistered = false;

    /** @var string $memory_limit */
    private string $memory_limit;

    /** @var string $cli_memory_limit */
    private string $cli_memory_limit;

    public function __construct()
    {
        $this->memory_limit = Config::get('env', 'memory.limit', '256M');
        $this->cli_memory_limit = Config::get('env', 'cli.memory.limit', '256M');
    }

    /**
     * Apply Memory Limit
     * @return void
     */
    public function apply(): void
    {
        // Check Argumentrs are Valid
        if (!preg_match('/^\d+[kmg]$/i', $this->memory_limit)) {
            throw new \InvalidArgumentException("Invalid Memory Limit Parameter: [{$this->memory_limit}]. Valid Format: '256M', '512K', '1G'.");
        }
        if (!preg_match('/^\d+[kmg]$/i', $this->cli_memory_limit)) {
            throw new \InvalidArgumentException("Invalid CLI Memory Limit Parameter: [{$this->cli_memory_limit}]. Valid Format: '256M', '512K', '1G'.");
        }

        $current = ini_get('memory_limit');
        if (!function_exists('ini_set') || ($current === false)) {
            return;
        }

        $target = $this->isCli() ? $this->cli_memory_limit : $this->memory_limit;

        $this->setMemoryLimitSafely($target);
        return;
    }

    /**
     * Register Peak Memory Usage Logger.
     * @param ?callable $logger Optional callback to receive peak memory usage in MB and bytes. Signature: function(float $peakMb, int $peakBytes): void
     * @param bool $enabled Optional callback to receive peak memory usage in MB and bytes. Signature: function(float $peakMb, int $peakBytes): void
     * @return void
     */
    public function monitor(?callable $logger = null, bool $enabled = false): void
    {
        if ($logger === null && !$enabled) {
            return;
        }
        if (self::$monitorRegistered) {
            return;
        }

        self::$monitorRegistered = true;
        register_shutdown_function(function () use ($logger) {
            $peakBytes = memory_get_peak_usage(true);
            $peakMb = round($peakBytes / 1024 / 1024, 2);

            if ($logger) {
                $logger($peakMb, $peakBytes);
                return;
            }

            // Fallback Logger
            error_log("[Laika] Peak Memory Usage: {$peakMb} MB");
        });
    }

    /**
     * Get Current Limit
      * @return string
     */
    public function currentLimit(): string
    {
        return (string) ini_get('memory_limit');
    }

    /*=============================== INTERNAL API ===============================*/
    /**
     * Detect CLI Context.
     * @return bool
     */
    private function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Set Memory Limit But Never Exceed PHP Hard Ceiling.
     * @throws RuntimeException if target limit is less than or equal to current usage.
     * @return void
     */
    private function setMemoryLimitSafely(string $target): void
    {
        $currentLimit = ini_get('memory_limit');

        if ($currentLimit === false) {
            return;
        }

        // If PHP is unlimited, safe to set
        if ($currentLimit === '-1') {
            ini_set('memory_limit', $target);
            return;
        }

        $targetBytes  = $this->toBytes($target);
        $limitBytes   = $this->toBytes($currentLimit);
        $usageBytes   = memory_get_usage(true);

        if ($targetBytes <= $usageBytes) {
            throw new \RuntimeException("Target memory limit ($target) is less than or equal to current usage (" . round($usageBytes / 1024 / 1024, 2) . " MB). Please set a higher memory limit.");
        }

        // Existing Ceiling Protection
        if ($targetBytes <= $limitBytes) {
            ini_set('memory_limit', $target);
        }
        return;
    }

    /**
     * Convert Shorthand Memory Notation to Bytes.
     * @return int
     */
    private function toBytes(string $value): int
    {
        $value = trim($value);
        $unit  = strtolower($value[strlen($value) - 1]);
        $num   = (int) substr($value, 0, -1);

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $value,
        };
    }
}
