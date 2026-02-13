<?php

namespace Laika\Core\Regex;

use Laika\Core\Regex\Abstracts\Rule;

class Regex
{
    /**
     * @var array<string,Rule>
     */
    protected static array $rules = [];

    /**
     * Constructor     *
     * @param bool $autoDiscover
     */
    public function __construct()
    {
        $this->discoverRules();
    }

    /**
     * Automatically discover and register all rules
     *
     * @return void
     */
    protected function discoverRules(): void
    {
        $rulesPath = __DIR__ . '/Rules';
        
        if (!is_dir($rulesPath)) {
            return;
        }

        $files = scandir($rulesPath);
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $fullClassName = "Laika\\Core\\Regex\\Rules\\{$className}";
                
                if (class_exists($fullClassName)) {
                    $reflection = new \ReflectionClass($fullClassName);

                    // if ($reflection->isSubclassOf(Rule::class) && !$reflection->isAbstract()) {
                    //     $instance = $reflection->newInstanceWithoutConstructor();
                    //     $this->addRule($instance);
                    //     // static::$rules[$instance->name()] = $fullClassName;
                    // }

                    if ($reflection->isSubclassOf(Rule::class) && !$reflection->isAbstract()) {
                        $ruleInstance = new $fullClassName();
                        $this->addRule($ruleInstance);
                    }
                }
            }
        }
        return;
    }

    protected function make(string $name, mixed ...$params): Rule
    {
        $class = $this->getRule(strtolower($name));

        if (empty($class)) {
            throw new \InvalidArgumentException("Rule [$name] Not Found");
        }

        return new $class(...$params);
    }

    /**
     * Add a rule manually
     *
     * @param Rule $rule
     * @return void
     */
    public function addRule(Rule $rule): void
    {
        static::$rules[$rule->name()] = $rule;
        return;
    }

    /**
     * Get a rule by name
     *
     * @param string $name
     * @return Rule|null
     */
    public function getRule(string $name): ?Rule
    {
        return static::$rules[strtolower($name)] ?? null;
    }

    /**
     * Get all registered rules
     *
     * @return array<string,Rule>
     */
    public function getRules(): array
    {
        return static::$rules;
    }

    /**
     * Validate input against a specific rule
     *
     * @param string $ruleName
     * @param string $input
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(string $ruleName, string $input, mixed ...$params): bool
    {
        $rule = $this->make($ruleName, ...$params);

        return $rule->validate($input);
    }

    /**
     * Match input against a specific rule
     *
     * @param string $ruleName
     * @param string $input
     * @return array|null
     * @throws \InvalidArgumentException
     */
    public function match(string $ruleName, string $input): ?array
    {
        $rule = $this->getRule($ruleName);
        
        if (!$rule) {
            throw new \InvalidArgumentException("Rule '{$ruleName}' not found");
        }
        
        return $rule->match($input);
    }

    // /**
    //  * Validate input against multiple rules
    //  *
    //  * @param array $ruleNames
    //  * @param string $input
    //  * @return bool
    //  */
    // public function validateMultiple(array $ruleNames, string $input): bool
    // {
    //     foreach ($ruleNames as $ruleName) {
    //         if (!$this->validate($ruleName, $input)) {
    //             return false;
    //         }
    //     }
    //     return true;
    // }

    /**
     * Check which rules the input passes
     *
     * @param string $input
     * @return array
     */
    public function checkRules(string $input): array
    {
        $results = [];
        
        foreach (static::$rules as $name => $rule) {
            $results[$name] = $rule->validate($input);
        }
        
        return $results;
    }
}
