<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class AlphaNumericRule extends Rule
{
    /**
     * Get the regex pattern for alphanumeric characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^[a-zA-Z0-9]+$/';
    }
}
