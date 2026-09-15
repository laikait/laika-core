# Helper Functions

<!-- {% raw %} -->

laika-core ships global functions in `helpers/functions/system.php`, and registers many of them as hooks in `helpers/hooks/system.php`. Both files are declared as `functions` and `hooks` [resources](05_config-and-app.md#resources) in laika-core's `composer.json`, and `lf-boot/app.php` requires every function file and then every hook file before routing starts.

## Debugging

| Function | Does |
|---|---|
| `dd(mixed $data, bool $die = false): void` | Prints `var_dump()` in a `<pre>` |
| `show(mixed $data, bool $die = false): void` | Prints `print_r()` in a `<pre>` |

> **Note:** despite the name, `dd()` only stops execution when `$die` is `true`.

## Data

| Function | Returns |
|---|---|
| `purify(array $data): array` | The array with every string trimmed, recursively |
| `convert_to_string(mixed $value): string` | `true`/`false` for booleans, `''` for null, JSON for arrays and objects |
| `slugify(string $name): string` | A lowercase ASCII slug. **Stops at the first dot.** Returns `file-{uniqid}-{time}` when nothing is left. |
| `response(bool $status, int\|string $message, array $data = []): array` | `['status' => …, 'message' => …, 'data' => …]` for JSON APIs |

## Application

| Function | Returns / does |
|---|---|
| `config(string $name, ?string $key = null, mixed $default = null): mixed` | `Config::get()`; see [Config](05_config-and-app.md#config) |
| `app_name(): string` | `config('app', 'name')`, defaulting to `Laika Framework` |
| `app_host(): string` | `Url::base()` |
| `time_zones(): array` | Every timezone identifier |
| `repo_dir(string $name): string` | The absolute path of `vendor/{name}` |
| `setPermission(string $path, int $mode = 0755): bool` | `chmod()`. On Windows it only toggles the read-only attribute. |
| `setPermissionRecursive(string $path, int $dirMode = 0755, int $fileMode = 0644): bool` | The same, through a whole tree |

> **Note:** `repo_dir()` throws a `TypeError` when the package directory doesn't exist, because `realpath()` returns `false`.

## Hooks

| Function | Does |
|---|---|
| `add_hook(string $filter, callable $callback, int $priority = 10): void` | Registers a callback. Lower priorities run first. |
| `do_hook(string $filter, mixed ...$args): void` | Runs every callback |
| `apply_hook(string $filter, mixed $value = null, mixed ...$args): mixed` | Passes `$value` through each callback and returns the result |

See [Hook](05_config-and-app.md#hook) for how filters chain.

## Routing

| Function | Returns |
|---|---|
| `named(string $name, array $params = []): string` | The absolute URL of a named route. Accepts `'users.index?status=active'`. |
| `match_url(string $named): bool` | Whether the current URL starts with that named route's URL, for highlighting menus |

## Options

These read and write the `options` table through [`Option`](10_data.md#options).

| Function | Returns |
|---|---|
| `option(string $key, null\|string\|int $default = null): ?string` | The stored value, cached for the rest of the request |
| `option_bool(string $key): bool` | `true` only when the stored value is `true` (any case) |
| `option_int(string $key, int $default = 0): int` | The value as an integer, if numeric |
| `option_array(string $key, array $default = []): array` | The value decoded as JSON |
| `option_insert(string $key, mixed $value): bool` | Adds a new key; `false` if it exists |
| `option_update(string $key, mixed $value): bool` | Changes an existing key; `false` if it doesn't exist |

> **Note:** `option()` treats a falsy default (`0` or `''`) as no default, so `option('limit', 0)` returns `null` for a missing key. `option_bool()` doesn't accept `1`, `yes` or `on`: store booleans as `true` and `false`, which is what `convert_to_string()` produces.

## Request

| Function | Returns |
|---|---|
| `request_is(string $method): bool` | `post`, `get`, `put`, `patch`, `delete` or `ajax` |
| `request_input(string $key, mixed $default = ''): mixed` | `Request::input()` (note the `''` default) |
| `request_inputs(): array` | `Request::inputs()` |
| `request_header(string $key): ?string` | `Request::header()` |

## Alerts

One-shot flash messages, stored in the session's `APP` scope under `alert`.

| Function | Does |
|---|---|
| `alert_set(string $message, bool $status): void` | Stores the alert. `Redirect::with()` does the same. |
| `alert_get(): array` | Returns `['message' => …, 'status' => …]` and removes it, or `[]` |

## Pages

| Function | Returns |
|---|---|
| `page_title(string $title): string` | `"{$title} | {app name}"` |
| `page_number(): int` | The `?page=` value, at least 1 (same as `Page::number()`) |

## Template Output

These print tags into a template. They're usually called from Twig through the `hook` filter (see [below](#using-hooks-from-twig)).

| Function | Does |
|---|---|
| `asset(string $path): string` | An absolute URL under the base, or the URL unchanged when it already has a host |
| `enqueue_style(string $handle, string $src, string $version = '1.0.0', string $media = 'all'): void` | Queues a stylesheet once per handle |
| `enqueue_script(string $handle, string $src, string $version = '1.0.0', bool $defer = false): void` | Queues a script once per handle |
| `enqueue_meta(string $name, string $content, string $type = 'name'): void` | Queues a `<meta>`; `$type` is `name` or `property` |
| `print_metas()` / `print_styles()` / `print_scripts(): void` | Prints the queued tags |
| `lf_header(): void` | Metas, styles, and the default scripts (`TOKEN` and `APP_URI` JS constants). Put it before `</head>`. |
| `lf_footer(): void` | Queued scripts. Put it before `</body>`. |
| `csrf_field(): void` | **Echoes** a hidden `_csrf` input |
| `context_add(string $key, mixed $value): void` / `context_get(?string $key = null, mixed $default = null): mixed` | Shared template [context](06_templates.md#context) |
| `local(string $property, ...$args): string` | A translated string from the `LANG` class, run through `sprintf()`; throws if missing |

## Registered Hooks

`helpers/hooks/system.php` registers these functions as hooks at priority **1000**. Your own callbacks, at the default priority of 10, run first.

| Hook | Function |
|---|---|
| `app_host` | `app_host()` |
| `app_name` | `app_name()` |
| `asset` | `asset()` |
| `local` | `local()` |
| `csrf_field` | `csrf_field()` |
| `alert_set` / `alert_get` | `alert_set()` / `alert_get()` |
| `page_title` / `page_number` | `page_title()` / `page_number()` |
| `request_header` / `request_input` / `request_inputs` / `request_is` | The request helpers |
| `context_add` | `context_add()` |
| `context` | `context_get()` |
| `enqueue_meta` / `enqueue_style` / `enqueue_script` | The enqueue helpers |
| `print_metas` / `print_styles` / `print_scripts` | The print helpers |
| `lf_header` / `lf_footer` | `lf_header()` / `lf_footer()` |
| `time_zones` | `time_zones()` |

### Using Hooks From Twig

`Template` registers a `hook` filter that calls `apply_hook()`. The value on the left is the hook name, and filter arguments are passed on:

```twig
<head>
    <title>{{ 'page_title'|hook('Dashboard') }}</title>
    {{ 'lf_header'|hook }}
</head>
<body>
    <form method="post">
        {{ 'csrf_field'|hook }}
        <p>{{ 'local'|hook('greeting', user.name) }}</p>
    </form>
    {{ 'lf_footer'|hook }}
</body>
```

Hooks such as `csrf_field` and `lf_header` echo their markup while the template renders, and return nothing. See [Templates](06_templates.md#twig-filters) for the other filters.

<!-- {% endraw %} -->
