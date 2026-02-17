<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class UrlRule extends Rule
{
    /**
     * Get the regex pattern for URL validation
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)$/';
    }
}
