<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class EmailRule extends Rule
{
    /**
     * Get the regex pattern for email validation
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    }
}
