<?php

namespace Laika\Core\Regex\Abstracts;

abstract class Rule
{
    /**
     * Get the regex pattern for this rule
     *
     * @return string
     */
    abstract public function pattern(): string;

    /**
     * Get the rule name
     *
     * @return string
     */
    public function name(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(str_replace('Rule', '', $className));
    }

    /**
     * Validate input against the pattern
     *
     * @param string $input
     * @return bool
     */
    public function validate(string $input): bool
    {
        return (bool) preg_match($this->pattern(), $input);
    }

    /**
     * Get match results
     *
     * @param string $input
     * @return array|null
     */
    public function match(string $input): ?array
    {
        if (preg_match($this->pattern(), $input, $matches)) {
            return $matches;
        }
        return null;
    }
}
