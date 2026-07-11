<?php

declare(strict_types=1);

namespace Laika\Core\Interfaces;

interface FilterInterface
{
    public function terminate(callable $next, ?string $response, array &$params): void;
}
