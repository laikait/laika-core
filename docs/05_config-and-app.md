# Configuration & App

<!-- {% raw %} -->

## Config

**Relay:** `Laika\Service\Config` (`config`). **Class:** `Laika\Core\Helper\Config` (static). **Helper:** `config()`.

Every `lf-config/*.php` file returns an array. `providers.php` is the exception and is skipped. All files are loaded together on first use and cached for the rest of the process.

```php
use Laika\Service\Config;

Config::get('app', 'name', 'My App');     // one key
Config::get('database', 'default');       // one connection's array
Config::get('mail');                      // a whole file
config('app', 'trusted_proxies', []);     // same as Config::get()
```

| Method | Does |
|---|---|
| `get(string $name, ?string $key = null, mixed $default = null): mixed` | A whole file, or one top-level key |
| `all(): mixed` | Every loaded file |
| `has(string $name, ?string $key = null): bool` | Whether the file (and key) exists |
| `set(string $name, string $key, null\|int\|float\|string\|bool\|array $value): void` | Changes a key **and rewrites the file** |
| `pop(string $name, string $key): bool` | Removes a key **and rewrites the file** |
| `create(string $name, array $data): bool` | Writes a new config file; throws if it already exists |

File names and keys are lowercased, so `APP_NAME` and `app_name` are the same key. Only top-level keys are addressable; there's no dot notation.

> **Note:** `set()`, `pop()` and `create()` regenerate the whole file from the loaded array. Comments, `use` statements, constants and `::class` expressions are replaced by their values or lost. Don't use them on hand-edited files such as `lf-config/auth.php`; keep them for files the application manages itself.

## Init

**Relay:** `Laika\Service\Init` (`init`). **Class:** `Laika\Core\Helper\Init`.

Connects framework services to their `lf-config` files.

| Method | Does |
|---|---|
| `db(?string $name = null): void` | Registers a connection from `lf-config/database.php`, once. Throws `RuntimeException` if it isn't defined. |
| `file(array $params = []): void` | File session driver |
| `model(?string $name = null, bool $install = false): void` | Database session driver through laika-model |
| `mysql(?string $name = null, array $params = []): void` | Database session driver over raw PDO |
| `redis(array $params = []): void` | Redis session driver, client from `lf-config/redis.php` |
| `memcached(array $params = []): void` | Memcached session driver, client from `lf-config/memcached.php` |

The session methods mirror `Laika\Session\SessionConfig`. See the [laika-session documentation](https://github.com/laikait/laika-session/tree/main/docs). Each one also marks the session cookie `Secure` when `Url::isHttps()` is true, which works behind trusted proxies too.

```php
// lf-hooks/session.php
use Laika\Service\Init;

Init::file(['path' => APP_PATH . '/lf-storage/sessions']);
```

## App Key

**Relay:** `Laika\Service\AppKey` (`app.key`). **Class:** `Laika\Core\App\Key`.

The application secret, stored in `lf-storage/keys/app.key` with mode `0600`. [Vault](07_security.md#vault), [Token](07_security.md#token-jwt) and [CSRF](03_http.md#csrf) derive their keys from it.

| Method | Does |
|---|---|
| `get(): string` | The key, cached after the first read. Throws `AppKeyException` if it's missing or invalid. |
| `generate(int $byte = 32): void` | Writes a new key, **replacing any existing one** |
| `validate(int $byte = 32): bool` | Checks that the key has the expected length; throws otherwise |
| `fix(int $byte = 32): void` | Writes a new key only when the current one is missing or invalid |

From the command line, use `php laika secret:generate` and `php laika secret:fix`. `composer install` runs `secret:fix` automatically.

> **Note:** a new key invalidates every encrypted value, signature, token and CSRF token issued under the old one. Generate it once per environment and keep it out of version control.

## Local (Localisation)

**Relay:** `Laika\Service\Local` (`local`). **Class:** `Laika\Core\Helper\Local`. **Helper:** `local()`.

Translations are static properties of a `LANG` class, one file per language: `lf-lang/{lang}.local.php`.

```php
// lf-lang/en.local.php
class LANG
{
    public static string $greeting = 'Hello, %s!';
}
```

Nothing loads a language automatically. Choose and load one in a hook file:

```php
// lf-hooks/lang.php
use Laika\Service\Local;

Local::set('en');
Local::load();
```

Then call `local('greeting', 'Ann')` in PHP, or `{{ 'local'|hook('greeting', 'Ann') }}` in Twig, to get `Hello, Ann!`.

| Method | Does |
|---|---|
| `set(string $lang = 'en'): void` | Selects `xx` or `xx-yy`. Anything else throws `LocalException` (400), because the value becomes a file name. |
| `get(): string` | The selected language, `en` by default. Templates also get it as `local`. |
| `setPath(string $path): void` | An absolute directory, or a sub-directory of `lf-lang` |
| `load(): void` | Requires the language file, first creating it with a sample `LANG` class if it's missing |

The file is loaded with `require_once` and defines a global class, so one request can use only one language.

## Hook

**Relay:** `Laika\Service\Hook` (`hook`). **Class:** `Laika\Core\Helper\Hook` (static). **Helpers:** `add_hook()`, `do_hook()`, `apply_hook()`.

Named extension points for actions and filters.

```php
use Laika\Service\Hook;

Hook::add('order.placed', fn (array $order) => notify_team($order));
Hook::do('order.placed', $order);                    // action: run every callback

Hook::add('price.display', fn ($price, $currency) => "{$currency} {$price}", 20);
Hook::apply('price.display', '9.99', 'USD');         // filter: "USD 9.99"
```

| Method | Does |
|---|---|
| `add(string $hook, callable $callback, int $priority = 10): void` | Registers a callback |
| `do(string $hook, mixed ...$args): void` | Calls every callback with `$args` |
| `apply(string $hook, mixed $default = null, mixed ...$args): mixed` | Passes the value through every callback, each receiving `($value, ...$args)`, and returns the result |

Lower priorities run first; callbacks with equal priority run in registration order. The framework's own template hooks use priority 1000, so yours run before them. See [Registered Hooks](12_helper-functions.md#registered-hooks).

## Resources

**Relay:** `Laika\Service\Resource` (`resource`). **Class:** `Laika\Core\App\Resource` (static).

A **resource** is a named set of directories, such as `controllers`, `models`, `routes` or `hooks`. There are two kinds:
- **Class-map resources** have a namespace, and resolve each PHP file to a class name. They can require a contract (an interface or base class).
- **File resources** have no namespace, and resolve to file paths.

Definitions come from four places, seeded once in this order:

1. **Framework defaults** (below). The application can replace any of them.
2. **Packages:** `extra.laika.resources` in each installed package's `composer.json`.
3. **The application:** `extra.laika.resources` in the root `composer.json`. A name declared here replaces the default of that name.
4. **Runtime:** `Resource::register()` calls, which always add to what's there.

| Default name | Path | Namespace | Contract |
|---|---|---|---|
| `models` | `lf-app/Model` | `App\Model` | — |
| `schemas` | `lf-app/Schema` | `App\Schema` | `Laika\Model\Contract\SchemaAbstract` |
| `controllers` | `lf-app/Controller` | `App\Controller` | — |
| `jobs` | `lf-app/Job` | `App\Job` | `Laika\Queue\Abstracts\Job` |
| `pipelines` | `lf-app/Pipeline` | `App\Pipeline` | `Laika\Route\Contracts\PipelineInterface` |
| `filters` | `lf-app/Filter` | `App\Filter` | `Laika\Route\Contracts\FilterInterface` |
| `commands` | `lf-app/Command` | `App\Command` | `Laika\Cli\Contracts\CommandInterface` |
| `relays` | `lf-app/Relay` | `App\Relay` | `Laika\Relay\RelayProvider` |
| `routes` | `lf-routes` | — (files) | — |
| `hooks` | `lf-hooks` | — (files) | — |

A package declares its resources like this, with paths relative to the package root. A bare string is shorthand for `{"path": …}`:

```json
"extra": {
    "laika": {
        "resources": {
            "widgets": { "path": "src/Widget", "namespace": "Acme\\Widget", "contract": "Acme\\WidgetInterface" },
            "hooks": "hooks"
        }
    }
}
```

| Method | Returns / does |
|---|---|
| `register(string $name, string $path, ?string $base_namespace = null, ?string $contract = null): void` | Adds a location at runtime |
| `define(ResourceDefinition $definition): void` | Adds a prepared definition. The same name, path and namespace twice is a no-op. |
| `package(string $composer_file): void` | Declares a package's resources from its `composer.json` |
| `getClasses(string $name, ?string $contract = null): array` | Class names, checked to exist and to satisfy the contract |
| `getFiles(string $name): array` | File paths |
| `getResources(?string $name = null): array` | Raw results for one resource, or for all of them |
| `names(): array` / `has(string $name): bool` / `isClassMap(string $name): bool` | Introspection |
| `definitions(?string $name = null): array` / `entries(ResourceDefinition $definition): array` | The definitions, and what one location contributes |
| `compile(): array` / `cache(?string $file = null): string` / `loadManifest(?string $file = null): bool` / `manifestPath(): string` | The compiled manifest; see [Getting Started](01_getting-started.md#the-resource-manifest) |
| `isolate(): void` / `flush(?string $name = null): void` | Testing helpers |

Names match `[a-z][a-z0-9_]*`, ignoring case. `controller` is accepted as an alias of `controllers`. A declared directory that doesn't exist resolves to nothing rather than failing; run `php laika resource:list` to see definitions and whether their paths exist.

`Laika\Core\App\ResourceDefinition` is the read-only record behind each location. Its properties are `name`, `path`, `namespace`, `contract` and `source`, and it has `isClassMap()`, `exists()`, `toArray()` and `fromArray()`.

## Infra

**Relay:** `Laika\Service\Infra` (`infra`). **Class:** `Laika\Core\App\Infra`.

Shortcuts over [Resources](#resources), used by the CLI and the router.

| Method | Returns |
|---|---|
| `getModelClasses(): array` | `[table => Model class]` |
| `getSchemaClasses(): array` | `[table => Schema class]`. Resource errors are rethrown as `SchemaException`. |
| `getControllerClasses()` / `getPipelineClasses()` / `getFilterClasses()` / `getQueueJobsClasses(): array` | Sorted class lists |
| `get(string $name, ?string $contract = null): array` | Classes of any class-map resource |
| `getRouteFiles()` / `getFunctionFiles()` / `getHookFiles(): array` | File resources |
| `getTemplateNames(): array` | `.twig` and `.html` files under `template/`, grouped by directory |
| `getRelayClasses(): array` | Every bound relay |

> **Note:** since 5.1.0, laika-core, laika-session and laika-auth no longer declare their schemas as resources. `getSchemaClasses()`, and so `php laika app:migrate`, now see only your application's schemas and those of other packages. The core `options` and `activities` tables create themselves on first use; see [Options & Activity Log](10_data.md).

## MemoryManager

**Class:** `Laika\Core\System\MemoryManager`.

| Method | Does |
|---|---|
| `__construct()` | Defines `MEMORY_LIMIT` and `CLI_MEMORY_LIMIT` as `256M` if they're missing |
| `apply(): void` | Applies `CLI_MEMORY_LIMIT` (under the CLI) or `MEMORY_LIMIT` to `memory_limit` |
| `monitor(?callable $logger = null, bool $enabled = false): void` | Logs peak memory at shutdown, through `$logger($peakMb, $peakBytes)` or `error_log()`. Only active when a logger is given or `$enabled` is true. |
| `currentLimit(): string` | The current `memory_limit` |

`apply()` accepts values like `256M`, `512K` or `1G`; anything else throws `InvalidArgumentException`. What it does depends on the current limit:
- When the current limit is unlimited (`-1`), it sets the new limit.
- Otherwise it only **lowers** the limit, never raising it above `php.ini`.
- It throws `RuntimeException` if the target is below the memory already in use.

> **Note:** only laika-queue's `worker` calls `apply()`. Web requests never do, so `MEMORY_LIMIT` in `lf-inc/const.php` currently has no effect on them. To enforce it, call `(new \Laika\Core\System\MemoryManager())->apply();` from a hook file.

<!-- {% endraw %} -->
