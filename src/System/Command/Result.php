<?php

declare(strict_types=1);

namespace Laika\Core\System\Command;

class Result
{
    public readonly string $command;
    public readonly string $output;
    public readonly string $error;
    public readonly int $exitCode;
    public readonly bool $timedOut;
    public readonly ?int $pid;

    /**
     * CommandResult Constructor
     * @param string $command The command that was executed.
     * @param string $output The standard output from the command.
     * @param string $error The standard error output from the command.
     * @param int $exitCode The exit code returned by the command.
     * @param bool $timedOut Whether the command execution timed out.
     * @param int|null $pid The process ID of the executed command, if available.
     */
    public function __construct(
        string $command,
        string $output,
        string $error,
        int $exitCode,
        bool $timedOut,
        ?int $pid = null
    ) {
        $this->command = $command;
        $this->output = trim($output);
        $this->error = trim($error);
        $this->exitCode = $exitCode;
        $this->timedOut = $timedOut;
        $this->pid = $pid;
    }

    /**
     * Determine if the command executed successfully.
     */
    public function success(): bool
    {
        return $this->exitCode === 0 && !$this->timedOut;
    }
}