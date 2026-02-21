<?php

namespace Laika\Core\System\Command;

class ProcessPool
{
    private array $jobs = [];

    public function add(string|array $command): self
    {
        $this->jobs[] = $command;
        return $this;
    }

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
