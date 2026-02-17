<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class AlphaRule extends Rule
{
    /**
     * Get the regex pattern for alphabetic characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^[a-zA-Z]+$/';
    }
}
