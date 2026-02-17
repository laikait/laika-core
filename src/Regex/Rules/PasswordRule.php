<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class PasswordRule extends Rule
{
    protected int $min;
    protected bool $upper;
    protected bool $lower;
    protected bool $numeric;
    protected bool $special;
    protected string $specialChars;

    /**
     * Constructor with configurable password requirements
     *
     * @param int $min Minimum password length (default: 8)
     * @param bool $upper Require uppercase letter (default: true)
     * @param bool $lower Require lowercase letter (default: true)
     * @param bool $numeric Require digit (default: true)
     * @param bool $special Require special character (default: true)
     * @param string $specialChars Allowed special characters (default: @$!%*?&)
     */
    public function __construct(
        int $min = 6,
        bool $upper = true,
        bool $lower = true,
        bool $numeric = true,
        bool $special = true,
        string $specialChars = '@$^&()_=!%*?#&~\'"{}\\|:";<>,./?'
    ) {
        $this->min = $min;
        $this->upper = $upper;
        $this->lower = $lower;
        $this->numeric = $numeric;
        $this->special = $special;
        $this->specialChars = preg_quote($specialChars, '/');
    }

    /**
     * Build dynamic regex pattern based on requirements
     *
     * @return string
     */
    public function pattern(): string
    {
        $lookaheads = [];
        $charClass = '';

        if ($this->lower) {
            $lookaheads[] = '(?=.*[a-z])';
            $charClass .= 'a-z';
        }

        if ($this->upper) {
            $lookaheads[] = '(?=.*[A-Z])';
            $charClass .= 'A-Z';
        }

        if ($this->numeric) {
            $lookaheads[] = '(?=.*\d)';
            $charClass .= '\d';
        }

        if ($this->special) {
            $lookaheads[] = "(?=.*[{$this->specialChars}])";
            $charClass .= $this->specialChars;
        }

        $lookaheadStr = implode('', $lookaheads);
        
        return $charClass ? "/^{$lookaheadStr}[{$charClass}]{{$this->min},}$/" : "/^.{{$this->min},}$/";
    }
}
