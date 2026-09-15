# Options & Activity Log

laika-core owns two tables:

| Table | Holds | Created |
|---|---|---|
| `options` | Site-wide key/value settings | The first time `Option` is used on a connection, then seeded with defaults |
| `activities` | An audit trail of who did what | The first time activities are written to a connection |

Since 5.1.0 these schemas are **not** discovered by `php laika app:migrate`. Each class installs its own table, once per process per connection, using `CREATE TABLE IF NOT EXISTS`. The database user therefore needs `CREATE` rights the first time. If it doesn't have them, create the tables by hand or run the schema once (see [Schemas](#schemas)).

## Options

**Relay:** `Laika\Service\Option` (`option`). **Class:** `Laika\Core\Model\OptionModel`. **Helpers:** `option()`, `option_bool()`, `option_int()`, `option_array()`, `option_insert()`, `option_update()`.

```php
use Laika\Service\Option;

Option::single('app_name');                 // "Laika Framework" (seeded default)
Option::insert('maintenance', false);       // stored as "false"
Option::update('data_limit', 50);           // stored as "50"

option_bool('maintenance');                 // false
option_int('data_limit', 20);               // 50
```

| Method | Returns / does |
|---|---|
| `__construct(?string $connection = null)` | Uses the `default` connection unless named. Installs and seeds the table on first use. |
| `install(?string $connection = null): void` | Creates and seeds the table on that connection. Runs once per process, so repeat calls are cheap. |
| `single(string $key, ?string $default = null): ?string` | The stored value, or `$default` when missing. Database errors also return `$default`. |
| `insert(string $key, mixed $value): bool` | Adds a key. `false` if it exists, including when its stored value is `''` or `'0'`. |
| `update(string $key, mixed $value): bool` | Changes an existing key; `false` if it doesn't exist |

- **Values are stored as text** through `convert_to_string()`: booleans become `true`/`false`, `null` becomes `''`, and arrays become JSON. Read them back with `option_bool()`, `option_int()` or `option_array()`.
- **Lookups are cached** for the rest of the process, per connection.
- **Connections are independent.** Each has its own model, cache and install state:

  ```php
  $tenant = new \Laika\Core\Model\OptionModel('tenant_db');
  $tenant->single('app_name');
  ```

Default options, seeded when the table is first installed:

| Key | Value |
|---|---|
| `app_icon` / `app_logo` | `icon.png` / `logo.png` |
| `app_name` | `Laika Framework` |
| `app_path` | `APP_PATH` at install time |
| `data_limit` | `20` |
| `datetime_format` / `date_format` / `time_format` | `Y-M-d H:i:s` / `Y-M-d` / `H:i:s` |
| `time_zone` | PHP's default timezone at install time |

Seeding only inserts keys that are missing, so existing values are never overwritten.

In `DEBUG` mode, install, insert and update failures throw `OptionException`. In production they fail quietly: `single()` returns the default, and writes return `false`.

## Activity Log

**Relay:** `Laika\Service\Activity` (`activity`). **Class:** `Laika\Core\Log\Activity`.

Record events during a request. laika-route writes them all at the end of the request by calling `Activity::insert()`.

```php
use Laika\Service\Activity;

$changes = Activity::changelog($invoiceBefore);            // compares against the request inputs

Activity::author('staff', $staffId)
    ->log("Updated invoice #{$invoice['id']}")
    ->event('invoice.updated', $changes);
```

| Method | Does |
|---|---|
| `author(?string $type = null, ?int $id = null): static` | Who did it: `system` (default), `staff`, `client` … |
| `log(string $log): static` | A human-readable description |
| `event(string $event, array $changelog = []): void` | Queues the activity, then resets the author and log |
| `events(?string $event = null): array` | Queued activities, all or for one event; an unknown event throws `LogException` |
| `changelog(array $existing, ?array $inputs = null): array` | `['field' => ['old' => …, 'new' => …]]` for fields whose input differs from `$existing`. `$inputs` defaults to `Request::inputs()`. |
| `insert(?string $connection = null): int` | Writes the queue and returns the row count. With an empty queue it touches no database. |

Each row stores `author_type`, `author_id`, `event` (lowercased), `log`, `changes` (the serialized changelog), `from_ip` (`Visitor::ip()`) and `created_at`. Write failures throw `LogException("Log Failed: …")`.

`changelog()` compares loosely (`!=`), so `"5"` and `5` count as unchanged. It also compares **sanitized** request input, which is HTML-encoded by default, against your stored values.

> **Note:** `event()` lowercases the event name it stores, but `events()` keys the queue by the name exactly as you passed it, while lowercasing the name you look up. Use lowercase event names if you read the queue back with `events()`.

## ChangeLog

**Class:** `Laika\Core\Http\ChangeLog`. Deprecated.

```php
(new ChangeLog())->addExisting($before)->addNew($after)->getLogs();
// ['new' => …, 'old' => …, 'changes' => ['field' => ['old' => …, 'new' => …]]]
```

It compares strictly (`!==`), and fields missing from `$before` count as `''`. Its deprecation note points to `Laika\Core\Log\Changelog`, which doesn't exist; use `Activity::changelog()` instead.

## Schemas

| Class | Table | Columns |
|---|---|---|
| `Laika\Core\Schema\OptionSchema` | `options` | `op_key` (string, primary key), `op_value` (text), `is_default` (enum `yes`/`no`, default `no`) |
| `Laika\Core\Schema\ActivitySchema` | `activities` | `log_id` (big id), `author_type`, `author_id` (nullable), `event`, `log`, `changes`, `from_ip`, `created_at`, with indexes on author, event and `created_at` |

To create a table ahead of time, for example when the runtime user can't run DDL, run the schema once with a privileged connection:

```php
(new \Laika\Core\Schema\OptionSchema('default'))->up();
(new \Laika\Core\Schema\ActivitySchema('default'))->up();
```

`OptionSchema::defaults()` returns the seed list; `seed()` inserts it through the `Option` relay.
