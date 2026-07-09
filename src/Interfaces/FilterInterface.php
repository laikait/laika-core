<?php

declare(strict_types=1);

namespace Laika\Core\Interfaces;

interface FilterInterface
{
    public function terminate(array &$params, $response): void;
}
