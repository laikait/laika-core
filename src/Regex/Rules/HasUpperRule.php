<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class HasUpperRule extends Rule
{
    /**
     * Get The Regex Pattern for Has Upper Case
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^(?=.*[A-Z]).+$/';
    }
}
