<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class HasSpecialRule extends Rule
{
    public function __construct(private string $chars = '@$^&()_=!%*?#&~\'"{}\|:";<>,./?') {}
    /**
     * Get The Regex Pattern for Has Special Character
     *
     * @return string
     */
    public function pattern(): string
    {
        $this->chars = preg_quote($this->chars, '/');
        return "/^(?=.*[{$this->chars}]).+$/";
    }
}
