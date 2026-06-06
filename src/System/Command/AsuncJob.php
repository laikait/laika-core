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
    private int $pid;
    private string $command;

    public function __construct(int $pid, string $command) {
        $this->pid = $pid;
        $this->command = trim($command);
    }

    public function pid(): int
    {
        return $this->pid;
    }

    public function isRunning(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$this->pid}\" 2>NUL", $out);
            return isset($out[1]);
        }

        return posix_kill($this->pid, 0);
    }

    public function stop(int $signal = SIGTERM): bool
    {
        if (!$this->isRunning()) {
            return false;
        }

        return posix_kill($this->pid, $signal);
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
