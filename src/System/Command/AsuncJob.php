<?php

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
