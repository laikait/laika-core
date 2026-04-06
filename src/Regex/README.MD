# Laika Regex

A lightweight, extensible regex rule engine for the [Laika PHP MVC Framework](https://github.com/ShowketAhmed/laika-framework). It provides a clean, rule-based API for validating and matching strings using auto-discovered or manually registered rules.

---

## Requirements

- PHP `8.1+`
- Part of `laikait/laika-core`

---

## Namespace

```
Laika\Core\Regex
```

---

## How It Works

The `Regex` class acts as a central registry for `Rule` instances. On construction, it automatically scans the `Rules/` directory and registers any concrete class that extends `Laika\Core\Regex\Abstracts\Rule`. Rules can also be registered manually at runtime.

Each `Rule` defines a regex `pattern()` and inherits `validate()` and `match()` from the abstract base.

---

## Usage

### Basic Validation

```php
use Laika\Core\Regex\Regex;

$regex = new Regex();

$regex->validate('alphanumeric', 'Hello123');  // true
$regex->validate('alphanumeric', 'Hello 123'); // false
```

### Matching

```php
$matches = $regex->match('alphanumeric', 'Hello123');
// ['Hello123']
```

Returns `null` if the pattern does not match.

### Check All Rules Against an Input

```php
$results = $regex->checkRules('Hello123');
// [
//     'alphanumeric' => true,
//     'email'        => false,
//     ...
// ]
```

### Register a Rule Manually

```php
use App\Regex\Rules\SlugRule;

$regex->addRule(new SlugRule());
```

### Retrieve a Rule

```php
$rule = $regex->getRule('alphanumeric');

$rule->validate('test123'); // true
$rule->match('test123');    // ['test123']
```

### Retrieve All Registered Rules

```php
$rules = $regex->getRules();
// ['alphanumeric' => AlphaNumericRule, ...]
```

---

## API Reference

### `Regex`

| Method | Signature | Description |
|---|---|---|
| `addRule` | `addRule(Rule $rule): void` | Manually register a rule instance |
| `getRule` | `getRule(string $name): ?Rule` | Retrieve a rule by its name |
| `getRules` | `getRules(): array` | Get all registered rules |
| `validate` | `validate(string $ruleName, string $input, mixed ...$params): bool` | Validate input against a named rule |
| `match` | `match(string $ruleName, string $input): ?array` | Run a match and return capture groups, or `null` |
| `checkRules` | `checkRules(string $input): array` | Validate input against every registered rule |

Throws `InvalidArgumentException` if a rule name is not found in `validate()` or `match()`.

---

## Creating a Custom Rule

Extend `Laika\Core\Regex\Abstracts\Rule` and implement the `pattern()` method. Place the file inside `Laika\Core\Regex\Rules\` for auto-discovery, or register it manually via `addRule()`.

```php
<?php

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class SlugRule extends Rule
{
    public function pattern(): string
    {
        return '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    }
}
```

The rule name is derived automatically from the class name by stripping the `Rule` suffix and lowercasing the result. `SlugRule` → `slug`, `AlphaNumericRule` → `alphanumeric`.

To override the name, redefine the `name()` method:

```php
public function name(): string
{
    return 'my-slug';
}
```

---

## `Rule` Abstract Class

All rules extend `Laika\Core\Regex\Abstracts\Rule`.

| Method | Description |
|---|---|
| `pattern(): string` | **Abstract.** Returns the regex pattern string |
| `name(): string` | Returns the rule's registered name (auto-derived from class name) |
| `validate(string $input): bool` | Returns `true` if the input matches the pattern |
| `match(string $input): ?array` | Returns capture groups array on match, or `null` |

---

## Auto-Discovery

Rules placed in `Laika\Core\Regex\Rules\` are discovered automatically when `Regex` is instantiated. Discovery requirements:

- The file must have a `.php` extension
- The class must extend `Rule`
- The class must not be abstract

```
src/
└── Regex/
    ├── Abstracts/
    │   └── Rule.php
    ├── Rules/
    │   ├── AlphaNumericRule.php   ← auto-discovered
    │   ├── EmailRule.php          ← auto-discovered
    │   └── SlugRule.php           ← auto-discovered
    └── Regex.php
```

---

## License

MIT — © [Showket Ahmed](mailto:riyadhtayf@gmail.com)