<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

class Cron
{
    /** @var array Jobs */
    protected array $jobs = [];

    /** @var string User */
    protected string $user;

    /**
     * @param string $user
     */
    public function __construct(string $user = '')
    {
        $this->user = $user;
    }

    ########################################################################################
    ##################################### EXTERNAL API #####################################
    ########################################################################################
    /**
     * Add Cron
     * @param string $expression
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function add(string $expression, string $command, ?string $label = null): static
    {
        // A newline in any field writes extra crontab lines, so the whole job
        // is rejected rather than silently scheduling something else.
        foreach (['expression' => $expression, 'command' => $command, 'label' => (string) $label] as $field => $value) {
            if (preg_match('/[\r\n]/', $value)) {
                throw new \InvalidArgumentException("Cron {$field} must not contain a line break.");
            }
        }

        $this->jobs[] = [
            'expression' => $expression,
            'command'    => $command,
            'label'      => $label,
        ];
        return $this;
    }

    /**
     * Get Jobs
     * @return array
     */
    public function jobs(): array
    {
        return $this->jobs;
    }

    /**
     * Remove a Job From List
     * @param string $label
     * @return static
     */
    public function pop(string $label): static
    {
        $this->jobs = array_values(
            array_filter($this->jobs, fn($job) => $job['label'] !== $label)
        );
        return $this;
    }

    /**
     * Flush Jobs List
     * @return static
     */
    public function flush(): static
    {
        $this->jobs = [];
        return $this;
    }

    /**
     * Add Cron For Every Minute
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function everyMinute(string $command, ?string $label = null): static
    {
        return $this->add('* * * * *', $command, $label);
    }

    /**
     * Add Cron For Every 5 Minutes
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function every5Minutes(string $command, ?string $label = null): static
    {
        return $this->add('*/5 * * * *', $command, $label);
    }

    /**
     * Add Cron For Every 10 Minutes
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function every10Minutes(string $command, ?string $label = null): static
    {
        return $this->add('*/10 * * * *', $command, $label);
    }

    /**
     * Add Cron For Every 15 Minutes
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function every15Minutes(string $command, ?string $label = null): static
    {
        return $this->add('*/15 * * * *', $command, $label);
    }

    /**
     * Add Cron For Every 30 Minutes
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function every30Minutes(string $command, ?string $label = null): static
    {
        return $this->add('*/30 * * * *', $command, $label);
    }

    /**
     * Add Hourly Cron
     * @param string $command
     * @param ?string $label Default is null
     * @return static
     */
    public function hourly(string $command, ?string $label = null): static
    {
        return $this->add('0 * * * *', $command, $label);
    }

    /**
     * Add Daily Cron
     * @param string $command
     * @param string $time Default is 00:00
     * @param ?string $label Default is null
     * @return static
     */
    public function daily(string $command, string $time = '00:00', ?string $label = null): static
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->add("{$minute} {$hour} * * *", $command, $label);
    }

    /**
     * Add Weekly Cron
     * @param string $command
     * @param int $dayOfWeek Default is 0
     * @param string $time Default is 00:00
     * @param ?string $label Default is null
     * @return static
     */
    public function weekly(string $command, int $dayOfWeek = 0, string $time = '00:00', ?string $label = null): static
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->add("{$minute} {$hour} * * {$dayOfWeek}", $command, $label);
    }

    /**
     * Add Monthly Cron
     * @param string $command
     * @param int $dayOfMonth Default is 1
     * @param string $time Default is 00:00
     * @param ?string $label Default is null
     * @return static
     */
    public function monthly(string $command, int $dayOfMonth = 1, string $time = '00:00', ?string $label = null): static
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->add("{$minute} {$hour} {$dayOfMonth} * *", $command, $label);
    }

    /**
     * Add Yearly Cron
     * @param string $command
     * @param int $month Default is 1
     * @param int $day Default is 1
     * @param string $time Default is 00:00
     * @param ?string $label Default is null
     * @return static
     */
    public function yearly(string $command, int $month = 1, int $day = 1, string $time = '00:00', ?string $label = null): static
    {
        [$hour, $minute] = $this->parseTime($time);
        return $this->add("{$minute} {$hour} {$day} {$month} *", $command, $label);
    }

    /**
     * Install Cron
     * @return bool
     */
    public function install(): bool
    {
        $this->assertLinux();
        $existing = $this->readCrontab();
        $block    = $this->buildBlock();

        if (str_contains($existing, '# [LAIKA-CRON-START]')) {
            // A callback, not a replacement string: in a replacement, $1 and \1
            // are backreferences, so a command containing $1 - ordinary in
            // shell - was silently rewritten.
            $existing = preg_replace_callback(
                '/# \[LAIKA-CRON-START\].*?# \[LAIKA-CRON-END\]/s',
                static fn (): string => $block,
                $existing
            );
        } else {
            $existing = rtrim($existing) . "\n" . $block . "\n";
        }

        return $this->writeCrontab($existing);
    }

    /**
     * Uninstall Cron
     * @return bool
     */
    public function uninstall(): bool
    {
        $this->assertLinux();
        $existing = $this->readCrontab();
        $cleaned  = preg_replace(
            '/\n?# \[LAIKA-CRON-START\].*?# \[LAIKA-CRON-END\]\n?/s',
            '',
            $existing
        );
        return $this->writeCrontab($cleaned ?? $existing);
    }

    /**
     * Render Cron
     * @return bool
     */
    public function render(): string
    {
        return $this->buildBlock();
    }

    /**
     * Get Installed Crons
     */
    public function installed(): array
    {
        $this->assertLinux();

        // Scoped to our own marker block. Parsing the whole crontab reported
        // jobs the framework does not own, and invited a caller to act on them.
        if (!preg_match('/# \[LAIKA-CRON-START\](.*?)# \[LAIKA-CRON-END\]/s', $this->readCrontab(), $match)) {
            return [];
        }

        $jobs = [];

        foreach (explode("\n", trim($match[1])) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $parts = preg_split('/\s+/', $line, 6);
            if ($parts === false || count($parts) < 6) continue;

            // buildBlock() appends " # label", so recover it here.
            [$command, $label] = array_pad(explode(' # ', $parts[5], 2), 2, null);

            $jobs[] = [
                'expression' => implode(' ', array_slice($parts, 0, 5)),
                'command'    => trim($command),
                'label'      => $label !== null ? trim($label) : null,
            ];
        }

        return $jobs;
    }

    /**
     * Check OS is Supported
     * @return bool
     */
    public function isSupported(): bool
    {
        return PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin';
    }

    ########################################################################################
    ##################################### INTERNAL API #####################################
    ########################################################################################
    /**
     * Parse an HH:MM Time
     *
     * The schedule helpers used to destructure explode(':', $time) directly, so
     * a value like '9' produced an undefined offset and a null minute - and a
     * malformed cron expression written to the user's crontab.
     * @param string $time
     * @return array{0:int,1:int} Hour, Minute
     * @throws \InvalidArgumentException
     */
    protected function parseTime(string $time): array
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($time), $match)) {
            throw new \InvalidArgumentException("Invalid time [{$time}]. Expected HH:MM.");
        }

        return [(int) $match[1], (int) $match[2]];
    }

    /**
     * Build Block
     * @return string
     */
    protected function buildBlock(): string
    {
        $lines = ['# [LAIKA-CRON-START]'];
        foreach ($this->jobs as $job) {
            $comment = $job['label'] ? ' # ' . $job['label'] : '';
            $lines[] = $job['expression'] . ' ' . $job['command'] . $comment;
        }
        $lines[] = '# [LAIKA-CRON-END]';
        return implode("\n", $lines);
    }

    /**
     * Render Crontab
     * @return string
     */
    protected function readCrontab(): string
    {
        $cmd = $this->user !== ''
            ? 'crontab -u ' . escapeshellarg($this->user) . ' -l 2>/dev/null'
            : 'crontab -l 2>/dev/null';

        return shell_exec($cmd) ?? '';
    }

    /**
     * Write Crontab
     * @return string
     */
    protected function writeCrontab(string $content): bool
    {
        $tmp = tempnam(sys_get_temp_dir(), 'laika_cron_');

        if ($tmp === false) {
            throw new \RuntimeException('Unable to create a temporary crontab file.');
        }

        if (file_put_contents($tmp, $content) === false) {
            unlink($tmp);
            throw new \RuntimeException('Unable to write the temporary crontab file.');
        }

        $cmd = $this->user !== ''
            ? 'crontab -u ' . escapeshellarg($this->user) . ' ' . escapeshellarg($tmp)
            : 'crontab ' . escapeshellarg($tmp);

        system($cmd, $result);
        unlink($tmp);

        return $result === 0;
    }

    /**
     * Assert Linux
     * @return string
     */
    protected function assertLinux(): void
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('Cron is only supported on Linux/macOS. Use Windows Task Scheduler instead.');
        }
    }
}
