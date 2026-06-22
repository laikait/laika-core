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

class ProcessPool
{
    /** @var array Jobs */
    private array $jobs = [];

    /**
     * Add Command to Process
     * @return static
     */
    public function add(string|array $command): static
    {
        $this->jobs[] = $command;
        return $this;
    }

    /**
     * Run Processes
     * @return array
     */
    public function run(int $concurrency = 4): array
    {
        $running = [];
        $results = [];
        $queue = $this->jobs;

        while ($queue || $running) {
            while (count($running) < $concurrency && $queue) {
                $cmd = array_shift($queue);
                $running[] = Runner::make()->async()->run($cmd);
            }

            foreach ($running as $k => $job) {
                if (!$job->isRunning()) {
                    $results[] = $job->status();
                    unset($running[$k]);
                }
            }

            usleep(200000);
        }

        return $results;
    }
}
