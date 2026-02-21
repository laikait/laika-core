<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class MaximumRule extends Rule
{
    public function __construct(private int $max = 100) {}

    /**
     * Get The Regex Pattern for Maximum Characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return "/^.{1,{$this->max}}$/";
    }
}
