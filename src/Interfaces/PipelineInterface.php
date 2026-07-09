<?php

declare(strict_types=1);

namespace Laika\Core\Interfaces;

interface PipelineInterface
{
    public function handle(callable $next, array &$params);
}
