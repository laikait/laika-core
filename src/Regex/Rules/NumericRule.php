<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class NumericRule extends Rule
{
    /**
     * Get the regex pattern for numeric characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^[0-9]+$/';
    }
}
