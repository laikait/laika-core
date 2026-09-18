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

namespace Laika\Core\System\Command;

class AsyncJob
{
    /** @var int Process ID */
    private int $pid;

    /** @var string Command to Run */
    private string $command;

    public function __construct(int $pid, string $command)
    {
        $this->pid = $pid;
        $this->command = trim($command);
    }

    public function pid(): int
    {
        return $this->pid;
    }

    public function isRunning(): bool
    {
        if ($this->pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // CSV without a header: a matching row contains the quoted PID
            exec("tasklist /NH /FO CSV /FI \"PID eq {$this->pid}\" 2>NUL", $out);
            foreach ($out as $line) {
                if (str_contains($line, "\"{$this->pid}\"")) {
                    return true;
                }
            }
            return false;
        }

        return function_exists('posix_kill') ? posix_kill($this->pid, 0) : is_dir("/proc/{$this->pid}");
    }

    /**
     * Stop The Process
     * @param int $signal POSIX signal. Default is 15 (SIGTERM, a constant only ext-pcntl defines). Ignored on Windows.
     * @return bool
     */
    public function stop(int $signal = 15): bool
    {
        if (!$this->isRunning()) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // /T: the PID is the cmd.exe wrapper, so its child goes with it
            exec("taskkill /PID {$this->pid} /T /F 2>NUL", $out, $code);
            return $code === 0;
        }

        if (function_exists('posix_kill')) {
            return posix_kill($this->pid, $signal);
        }

        exec("kill -{$signal} {$this->pid} 2>/dev/null", $out, $code);
        return $code === 0;
    }

    public function status(): array
    {
        return [
            'pid' => $this->pid,
            'running' => $this->isRunning(),
            'command' => $this->command,
        ];
    }
}
