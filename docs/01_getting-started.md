# Getting Started

## Installation

laika-core is installed as part of the Laika Framework, whose root package requires it:

```bash
composer require laikait/laika-core
```

That pulls in the rest of the framework: laika-auth, laika-cli, laika-model, laika-queue, laika-relay, laika-route, laika-session, laika-shield and laika-mailman, plus Twig, `firebase/php-jwt`, the AWS SDK and Whoops.

**Requirements:** PHP 8.1 or later and `ext-openssl`. Some classes need more:

| Extension | Needed by |
|---|---|
| `bcmath` | [Math](09_utilities.md#math) |
| `gd` | [Image](08_files-and-storage.md#image) |
| `zip` | [Zip](08_files-and-storage.md#zip) |
| `fileinfo` | `File::mime()`, `MimeType::fromContent()`, upload MIME checks |
| `redis` / `memcached` | The Redis and Memcached storage, session and queue drivers |
| `pdo_*` | Database connections through laika-model |
| `posix` | `AsyncJob::isRunning()` / `stop()` on Linux and macOS |

## What Happens at Boot

A web request enters at `index.php`:

```php
require_once __DIR__ . '/lf-boot/app.php';
Url::dispatch();            // Laika\Route\Url
```

`lf-boot/app.php` then does the following, in order:

1. **Defines `APP_PATH` and `DS`,** and requires `lf-inc/const.php`, which defines `DEBUG`, `MEMORY_LIMIT` and `CLI_MEMORY_LIMIT`.
2. **Requires `vendor/autoload.php`.** Composer's `files` autoload runs laika-core's `helpers/loader.php`, which:
   1. Defines the remaining [path constants](#path-constants), unless something already defined them.
   2. Creates the service container (`RelayRegistry`) and registers `Laika\Relay\CoreProviders`, which binds every core service. See [Relays](02_relays.md).
   3. Registers every relay provider it finds in the `relays` [resource](05_config-and-app.md#resources): package providers first, then the application's `lf-app/Relay` providers, so the app can override a binding.
   4. Hands the container to the relays (`Relay::setRegistry()`) and to the router (`Invoke::setResolver()`). From here on, controllers, pipelines and filters are built through the container, with their constructor dependencies auto-wired.
   5. Boots the providers. `CoreProviders::boot()` sets the PHP timezone to **UTC** and registers the [error handler](11_errors.md).
3. **Requires every `functions` file, then every `hooks` file.** That includes laika-core's own [helpers](12_helper-functions.md) and your `lf-hooks/*.php`.

After that, `Url::dispatch()` hands the request to laika-route:
1. It sends the [CORS](03_http.md#cors) and security headers.
2. It serves static files, or loads the route files and matches a route.
3. It runs the pipelines, the controller and the filters.
4. It writes the [activity log](10_data.md#activity-log).
5. It renders the response.

> **Note:** the timezone is forced to UTC at boot. Call `Date::setAppTimezone('Asia/Dhaka')` in a hook file if the application should run in another zone. See [Date](09_utilities.md#date).

> **Note:** define `DEBUG` in `lf-inc/const.php` before the autoloader runs. If it isn't defined, `helpers/loader.php` defines it as `true`, which enables detailed error pages.

## Path Constants

| Constant | Value |
|---|---|
| `APP_PATH` | The project root |
| `DS` | `DIRECTORY_SEPARATOR` |
| `STORAGE_PATH` | `APP_PATH/lf-storage` |
| `TEMPLATE_PATH` | `APP_PATH/template` |
| `TEMPLATE_CACHE_PATH` | `STORAGE_PATH/cache/template` |
| `CONFIG_PATH` | `APP_PATH/lf-config` |
| `LANG_PATH` | `APP_PATH/lf-lang` |
| `DEBUG` | From `lf-inc/const.php`; `true` if missing |
| `MEMORY_LIMIT` / `CLI_MEMORY_LIMIT` | From `lf-inc/const.php`; see [MemoryManager](05_config-and-app.md#memorymanager) |

## The Resource Manifest

Controllers, models, schemas, routes, hooks and the rest are found by scanning directories, as described in [Resources](05_config-and-app.md#resources).

When `DEBUG` is `false`, the scan is skipped whenever `lf-storage/cache/resources.php` exists. That file is a compiled manifest written by `php laika app:cache`.

> **Note:** a stale manifest hides changes. After adding or removing classes, or installing or updating packages, run `php laika app:cache` again. `php laika app:sync` clears the cache and rebuilds it.

## A First Controller

Controllers use the relays directly:

```php
namespace App\Controller;

use Laika\Core\App\Template;
use Laika\Service\{Request, Redirect};

class ContactController
{
    public function show(): string
    {
        $tpl = new Template();
        $tpl->assign('title', 'Contact');
        return $tpl->view('contact');           // template/contact.twig
    }

    public function send(): string
    {
        if (!Request::validate(['email' => 'required|email', 'message' => 'required|max:2000'])) {
            return $this->show();               // errors reach the view as `errors`
        }

        Redirect::with('Thanks, we will be in touch.', true)->to('contact');
    }
}
```

## Next

- How relays resolve, and the full list: [Relays](02_relays.md)
- Handling input and output: [HTTP](03_http.md)
- Building pages: [Templates](06_templates.md)
