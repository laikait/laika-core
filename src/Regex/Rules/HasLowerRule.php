<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class HasLowerRule extends Rule
{
    /**
     * Get The Regex Pattern for Has Lower Case
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^(?=.*[a-z]).+$/';
    }
}
