# Errors & Exceptions

## The Error Handler

**Class:** `Laika\Core\Exceptions\Handler`. `CoreProviders::boot()` registers it on every request by calling `Handler::register()`, which installs three hooks:

| Hook | Effect |
|---|---|
| `set_exception_handler()` | Uncaught exceptions go to `handle()` |
| `set_error_handler()` | Every warning, notice or deprecation that `error_reporting()` reports becomes an `ErrorException`. Errors suppressed with `@`, or masked out of `error_reporting()`, are left to PHP. |
| `register_shutdown_function()` | Fatal errors (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`) are rendered through `handle()` |

Because warnings become exceptions, things PHP normally tolerates stop the request: an undefined array key, a `setcookie()` after output, a call to a deprecated function. Use `@` only for calls that are meant to fail softly.

### What `handle()` Does

`handle(Throwable $e)` logs the exception, then renders it.

**Logging.** When `DEBUG` is on, the handler appends the exception class, message, location and stack trace to `lf-logs/{Y}-{Mon}-{d}-error.log`.

> **Note:** nothing is logged when `DEBUG` is `false`. Production errors leave no record unless your web server or PHP's own `error_log` captures them.

**Rendering.** The handler replies with JSON when any of these hold:
- the `Accept` header is exactly `application/json`
- the `Content-Type` starts with `application/json`
- `X-Requested-With` is `XMLHttpRequest`

| Exception | JSON response | HTML response, `DEBUG` on | HTML response, `DEBUG` off |
|---|---|---|---|
| `ValidationException` | Its status (422): `{"message": …, "errors": {…}}` | Whoops error page | Its status, generic error page |
| Other `HttpException` | Its status: `{"message": …}` | Whoops error page | Its status, generic error page |
| Anything else | 500: `{"message": "Application Error!", "exception": …}` | Whoops error page | 500, generic error page |

The `exception` field holds the message only when `DEBUG` is on; otherwise it's `null`. The generic error page is [`ServerError::show()`](#servererror).

> **Warning (5.1.x):** the JSON branch calls `Response::contentType()`, which doesn't exist: the method is `setContentType()`. The relay throws `RelayException` inside the exception handler, so **an error during a JSON or AJAX request ends in a PHP fatal error** instead of the JSON body described above.

> **Note:** `Accept` is compared exactly. Clients that send `application/json, text/plain, */*` (axios's default) or add no header at all (`fetch()`) get HTML unless they send `Content-Type: application/json` or `X-Requested-With`.

> **Note:** the production page always reads "5xx Internal Server Error", even when an `HttpException` sets 404 or 401 as the status. laika-route renders its own 404 page for unmatched routes; for other cases, catch the exception and render your own response.

## Throwing HTTP Errors

Only `HttpException` and its subclasses control the response status:

```php
use Laika\Core\Exceptions\{HttpException, NotFoundHttpException, AuthenticationException, ValidationException};

throw new NotFoundHttpException();                         // 404 "Page Not Found"
throw new AuthenticationException();                       // 401 "Unauthenticated."
throw new HttpException(403, 'You cannot edit this order.');
throw new ValidationException(['email' => ['Already registered.']]); // 422
```

## Exception Classes

All live in `Laika\Core\Exceptions`.

| Class | Extends | `getStatusCode()` | Thrown by |
|---|---|---|---|
| `HttpException` | `Exception` | Constructor's `$statusCode` (500) | Your code; `Redirect` for a bad status code |
| `NotFoundHttpException` | `HttpException` | 404 | Your code |
| `AuthenticationException` | `HttpException` | 401 | Your code |
| `ValidationException` | `HttpException` | 422; also `errors(): array` | Your code |
| `AppKeyException` | `RuntimeException` | Constructor code (500) | [App key](05_config-and-app.md#app-key) |
| `ExtensionException` | `RuntimeException` | Constructor code (500) | Vault, Math, Zip, the Redis/Memcached/S3 factories |
| `PathException` | `RuntimeException` | Constructor code (0) | Template, Zip |
| `ResourceException` | `RuntimeException` | Constructor code (0) | [Resources](05_config-and-app.md#resources), through named constructors: `invalidName()`, `invalidNamespace()`, `pathNotFound()`, `classNotFound()`, `notInstanceOf()`, `notClassMap()`, `unknownResource()` |
| `ConfigException` | `RuntimeException` | Constructor code (0) | — |
| `IPException` | `RuntimeException` | — | [IP utilities](04_url-client-ip.md#ip-utilities) |
| `RelayException` | `RuntimeException` | — | — |
| `RouteException` | `RuntimeException` | — | — |
| `CSRFException` | `Exception` | Constructor code (0) | [`CSRF::validate()`](03_http.md#csrf) |
| `ContextException` | `Exception` | Constructor code | [Context](06_templates.md#context) |
| `LocalException` | `Exception` | Constructor code | [Local](05_config-and-app.md#local-localisation) |
| `LogException` | `Exception` | Constructor code | [Activity](10_data.md#activity-log) |
| `OptionException` | `Exception` | — | [OptionModel](10_data.md#options), in `DEBUG` mode |
| `SchemaException` | `Exception` | — | `Infra::getSchemaClasses()` |

The handler reads `getStatusCode()` only from `HttpException` subclasses. Every other exception renders as a 500 unless you catch it. For example, catch `CSRFException` and answer 403 or 419 yourself.

Two more things to know:
- **Relays throw a different `RelayException`.** Calling a method a relay's class doesn't have throws `Laika\Relay\Exceptions\RelayException`, from laika-relay, not `Laika\Core\Exceptions\RelayException`.
- **`LocalException` and `ResourceException` can't chain.** Both type `?Throwable $previous` without importing `Throwable`, so passing a previous exception to either raises a `TypeError`.

## ServerError

`Laika\Core\Exceptions\ServerError::show(): string` returns the self-contained HTML page shown for errors in production.
