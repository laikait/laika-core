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

class Runner
{
    private int $timeout = 60;
    private ?string $cwd = null;
    private array $env = [];
    private bool $async = false;
    private $onOutput = null;

    public static function make(): self
    {
        return new self();
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function cwd(string $path): self
    {
        $this->cwd = $path;
        return $this;
    }

    public function env(array $env): self
    {
        $this->env = $env;
        return $this;
    }

    public function async(bool $async = true): self
    {
        $this->async = $async;
        return $this;
    }

    /**
     * Real-Time Output Callback
     */
    public function onOutput(callable $callback): self
    {
        $this->onOutput = $callback;
        return $this;
    }

    // =========================
    // MAIN
    // =========================

    public function run(string|array $command): Result|AsyncJob
    {
        $commandString = $this->buildCommand($command);

        if ($this->async) {
            return $this->runAsync($commandString);
        }

        return $this->runSync($commandString);
    }

    // =========================
    // SAFE BUILDER
    // =========================

    private function buildCommand(string|array $command): string
    {
        if (is_string($command)) {
            return escapeshellcmd($command);
        }

        $parts = array_map('escapeshellarg', $command);
        return implode(' ', $parts);
    }

    // =========================
    // SYNC WITH STREAMING
    // =========================

    private function runSync(string $command): Result
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->cwd, $this->env ?: null);

        if (!is_resource($process)) {
            throw new \RuntimeException('Process start failed.');
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $start = time();
        $output = '';
        $error = '';
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);

            $chunkOut = stream_get_contents($pipes[1]);
            $chunkErr = stream_get_contents($pipes[2]);

            if ($chunkOut !== '') {
                $output .= $chunkOut;
                $this->onOutput && ($this->onOutput)($chunkOut, 'out');
            }

            if ($chunkErr !== '') {
                $error .= $chunkErr;
                $this->onOutput && ($this->onOutput)($chunkErr, 'err');
            }

            if (!$status['running']) {
                break;
            }

            if ((time() - $start) > $this->timeout) {
                proc_terminate($process);
                $timedOut = true;
                break;
            }

            usleep(100000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return new Result(
            $command,
            trim($output),
            trim($error),
            $exitCode,
            $timedOut,
            $status['pid'] ?? null
        );
    }

    // =========================
    // ASYNC WITH PID
    // =========================

    private function runAsync(string $command): AsyncJob
    {
        // proc_open() on both platforms, so cwd() and env() apply to async runs too
        if (PHP_OS_FAMILY === 'Windows') {
            // `start /B` through popen() gave no PID, and AsyncJob's int $pid
            // then threw a TypeError. Output goes to NUL so the child can never
            // block on a full pipe. The PID is the cmd.exe wrapper's, which
            // lives exactly as long as the command; the handle isn't waited on.
            $null = ['file', 'NUL', 'w'];
            $process = proc_open($command, [0 => ['file', 'NUL', 'r'], 1 => $null, 2 => $null], $pipes, $this->cwd, $this->env ?: null);

            if (!is_resource($process)) {
                throw new \RuntimeException('Process start failed.');
            }

            $pid = (int) (proc_get_status($process)['pid'] ?? 0);
        } else {
            $process = proc_open($command . ' > /dev/null 2>&1 & echo $!', [1 => ['pipe', 'w']], $pipes, $this->cwd, $this->env ?: null);

            if (!is_resource($process)) {
                throw new \RuntimeException('Process start failed.');
            }

            $pid = (int) trim((string) stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            proc_close($process);
        }

        return new AsyncJob($pid, $command);
    }
}
