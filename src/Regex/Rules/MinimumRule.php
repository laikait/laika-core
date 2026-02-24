<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class MinimumRule extends Rule
{
    public function __construct(private int $min = 6) {}

    /**
     * Get The Regex Pattern for Minimum Characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return "/^.{{$this->min},}$/";
    }
}
