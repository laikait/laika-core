<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class HasNumericRule extends Rule
{
    /**
     * Get The Regex Pattern for Has Numeric Character
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^(?=.*\d).+$/';
    }
}
