# Upgrading

## To 5.1.1

Bug fixes only. No code changes are needed.

| Area | Fix |
|---|---|
| `OptionModel` | `new OptionModel('name')` called the connection name as a function and failed. The connection is now honoured, with separate models, caches and install state per connection. |
| `OptionModel` | Nothing created or seeded the `options` table once schema auto-discovery was removed. The table is now installed and seeded with the [defaults](10_data.md#options) the first time `Option` is used. |
| `OptionModel` | A stored `''` or `'0'` no longer counts as missing, so `insert()` can't hit a duplicate-key error. The cached value is the stored one, never a caller's default. |
| `Activity` | The `activities` table was always created on the `default` connection, even when inserting elsewhere. It now follows the connection passed to `insert()`, with one install flag per connection. |
| `Redirect::back()` | The referer host was compared with `HTTP_HOST`, which includes the port, so on `localhost:8000` every referer was rejected. Hosts are now compared without the port, honouring trusted proxies. `//evil.com` and `/\evil.com` paths are collapsed to local paths. |
| `composer.json` | `aws/aws-sdk-php` (`^3.394`) and `filp/whoops` (`^2.18`) accept updates again, including security fixes. |

## To 5.1.0

### Behaviour Changes

- **Schemas and models are no longer auto-discovered** from laika-core's `composer.json`. `php laika app:migrate` doesn't create `options` or `activities`; the classes install their own tables. See [Options & Activity Log](10_data.md).
- **`Redirect::back()` and `Redirect::to()` return `void`** instead of `static`. Both always ended with `exit`, so no working code chained after them. 303 is now accepted alongside 301 and 302.
- **`Redirect::back()` only follows same-host referers,** and only as a path. Anything else goes to `/`.
- **The error handler respects `@` and `error_reporting()`.** A suppressed warning no longer becomes an exception, so deliberate fail-safe calls (`@mkdir`, `@fopen`) behave as intended, including under concurrent PHP-FPM workers.
- **`Request::header('Authorization')`** falls back to `REDIRECT_HTTP_AUTHORIZATION` and `PHP_AUTH_*`, so bearer tokens survive Apache with PHP-FPM.
- **Session cookies get `Secure` behind trusted proxies.** `Init`'s session helpers set it when `Url::isHttps()` is true.
- **`OptionModel` and `Activity` accept a connection name** in their constructors. `Activity` no longer calls `Init::db()`; laika-model registers connections from config on demand.

### Dependency Changes

| Package | Constraint |
|---|---|
| `laikait/laika-session` | `5.1.*`. `Session::scope()` replaces the trailing `$for` argument; see its upgrade notes. |
| `laikait/laika-auth` | `2.1.*`. Guards take their config array in the constructor. |

The framework's root `composer.json` must require `laikait/laika-core: ^5.1` to receive these versions.

### After Upgrading

1. Run `php laika app:cache` (or `app:sync`). A manifest compiled before 5.1.0 still lists the old core schemas.
2. Make sure the database user can run `CREATE TABLE` once, or create `options` and `activities` ahead of time.
3. Search for `Session::set($key, $value, 'SCOPE')`-style calls. PHP ignores the extra argument, so they silently write to the `APP` scope.
