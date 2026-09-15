# HTTP

## Request

**Relay:** `Laika\Service\Request` (`request`). **Class:** `Laika\Core\Http\Request`.

Parsed once per request. The constructor reads:
- **Query string and form fields:** `$_GET` and `$_POST`, passed through a sanitizer (see [Sanitizers](#sanitizers))
- **Uploads:** `$_FILES`, untouched
- **Raw body:** `php://input`, kept verbatim for `raw()`
- **JSON body:** decoded when `Content-Type` starts with `application/json`. Invalid JSON adds an error under the key `body`.
- **Method:** `REQUEST_METHOD`, overridden to `PUT`, `PATCH` or `DELETE` when the form or JSON body carries a `_method` field with one of those values. That's how HTML forms send non-POST verbs.

```php
use Laika\Service\Request;

$email = Request::input('email');
$data  = Request::only(['name', 'email']);
$token = Request::header('authorization');

if (Request::isPost() && Request::has('title')) { /* ... */ }
```

| Method | Returns |
|---|---|
| `method(): string` | The uppercase method, after `_method` spoofing |
| `isGet()` / `isPost()` / `isPut()` / `isPatch()` / `isDelete(): bool` | Method checks |
| `isAjax(): bool` | `X-Requested-With: XMLHttpRequest` |
| `input(string $key, mixed $default = null): mixed` | One value. Looks in the JSON body, then POST, then GET. |
| `inputs(): array` | GET, POST and JSON merged; later sources win |
| `only(array $keys): array` | Just those keys; missing ones are `null` |
| `has(string $key): bool` | Whether any source has the key |
| `body(): array` | The decoded JSON body |
| `file(?string $key = null): ?array` | One `$_FILES` entry, or all of them |
| `raw(): string` | The raw request body, **not** sanitized |
| `headers(): array` | All request headers |
| `header(string $name): ?string` | One header, case-insensitive |
| `validate(array $rules, array $customMessages = []): bool` | Validates `inputs()`; see [Validator](#validator) |
| `addError(string $key, string $error): void` | Adds your own error message |
| `addBulkError(array $errors): void` | Adds several, as `['field' => ['msg', ...]]` |
| `errors(): array` | Every error collected so far |

**Authorization header.** Apache doesn't pass `Authorization` to PHP-FPM, so `header('Authorization')` falls back through these sources:
1. `REDIRECT_HTTP_AUTHORIZATION`, set by the shipped `.htaccess` rule
2. `PHP_AUTH_USER` / `PHP_AUTH_PW`, rebuilt into a `Basic` value
3. `PHP_AUTH_DIGEST`

> **Note:** by default every string input is **HTML-encoded**. `Tom & Jerry` arrives as `Tom &amp; Jerry`, and `O'Neil` as `O&apos;Neil`. That makes values safe to echo, but they're stored encoded and their lengths include the entities. If you escape on output yourself, swap in a request that doesn't encode, early (for example in a hook file), before anything reads the request:
>
> ```php
> use Laika\Service\Request;
> use Laika\Core\Sanitizer\NullSanitizer;
>
> Request::swap(new \Laika\Core\Http\Request(new NullSanitizer()));
> ```
>
> `raw()` always holds the original body.

### Validating Input

```php
$ok = Request::validate([
    'email'   => 'required|email|max:100',
    'age'     => 'nullable|integer|between:18,120',
    'confirm' => 'required|match:password',
], [
    'email.required' => 'We need your email address.',
]);

if (!$ok) {
    $errors = Request::errors(); // ['email' => ['We need your email address.'], ...]
}
```

Errors from repeated `validate()` calls and from `addError()` accumulate, so check `errors()` once at the end. Templates receive them as the `errors` variable.

## Validator

**Class:** `Laika\Core\Http\Validator` (static).

```php
use Laika\Core\Http\Validator;

$errors = Validator::make($data, ['name' => 'required|string|max:50']);
// [] when valid, otherwise ['name' => ['The [name] field is required.']]
```

`make(array $data, array $rules, array $customMessages = []): array` checks each field in `$rules` against a pipe-separated rule string:
- Parameters follow a colon and are comma-separated: `between:2,10`, `in:draft,published`.
- A custom message is keyed `field.rule`.

| Rule | Passes when the value… |
|---|---|
| `required` | …is not `null` and not `''`. An empty array passes. |
| `nullable` | Marker only. Every rule except `required` already skips `null` and `''`. |
| `bail` | Marker: stop checking this field after its first error |
| `email` | …passes `FILTER_VALIDATE_EMAIL` |
| `url` | …passes `FILTER_VALIDATE_URL` |
| `numeric` | …is numeric (`is_numeric`) |
| `integer` | …is an integer: `"42"` and `-7` pass, `"3.5"` and `"1e2"` don't |
| `float` | …passes `FILTER_VALIDATE_FLOAT` |
| `boolean` | …is `true`/`false`, `1`/`0`, or `"true"`, `"false"`, `"yes"`, `"no"`, `"on"`, `"off"` |
| `array` / `string` | …is of that type |
| `date` / `date:FORMAT` | …parses with `strtotime()`, or matches the exact `DateTime` format |
| `time` / `time:FORMAT` | Same, for times: `time:H:i` |
| `before:DATE` / `after:DATE` | …is strictly before or after the date. An unparseable limit throws. |
| `min:N` / `max:N` | …is at least or at most `N`. Numbers are compared by value, strings by character count, arrays by item count. |
| `between:A,B` | …falls inside the inclusive range, measured as for `min` |
| `size:N` | …equals `N`, measured as for `min` |
| `match:FIELD` | …is identical to another field in the data |
| `in:A,B,…` / `not_in:A,B,…` | …is (or isn't) one of the listed values. Case-sensitive. |
| `alpha` / `alpha_num` / `alpha_dash` | …is Unicode letters / plus digits / plus `-` and `_` |
| `upper` / `lower` | …is **only** upper- or lower-case letters |
| `regex:PATTERN` | …matches the pattern. An invalid pattern is reported as an error. |
| `ip` / `ipv4` / `ipv6` | …is a valid address of that family |
| `uid` | …is an 8-4-4-4-12 hex UUID |
| `json` | …decodes as JSON |
| `callback:NAME[,ARGS…]` | …makes the callable `NAME($value, $data, $args)` return `true`. A returned string becomes the error message. |

An unknown rule name throws `InvalidArgumentException`.

> **Note:** `min`, `max`, `between` and `size` check `is_numeric()` first, so a numeric **string** is compared by value, not length. `min:8` accepts the PIN `"1234"`, because 1234 ≥ 8. Use `regex:/^.{8,}$/` for a length check on values that may be all digits.

> **Note:** the rule string is split on `|` before anything else, so a `regex:` pattern can't contain a `|`. Use the `callback` rule for alternations.

## Response

**Relay:** `Laika\Service\Response` (`response`). **Class:** `Laika\Core\Http\Response`.

Holds the status, content type, headers and body for the current response. Controllers usually just return a string; laika-route reads the content type set here to choose how to render it. You can also build and send a response yourself.

```php
use Laika\Service\Response;

return Response::json(['ok' => true], 201)->getBody();

Response::setStatus(404)->setHeader('X-Reason', 'missing');
```

| Method | Does |
|---|---|
| `setStatus(int $code): static` | 100–599, otherwise `InvalidArgumentException` |
| `getStatus(): int` | Default 200 |
| `setContentType(string $type): static` / `getContentType(): string` | Default `text/html; charset=UTF-8` |
| `setHeader(string $name, string $value): static` | The name is reduced to `[A-Za-z0-9_-]` and title-cased; control characters are stripped from the value |
| `setHeaders(array $headers): static` | Several at once |
| `getHeader(string $name): ?string` / `getHeaders(): array` | Read back |
| `removeHeader(string $name): static` | Drop one |
| `setBody(mixed $body): static` / `getBody(): mixed` | The body. `body()` is a deprecated alias. |
| `json(mixed $data, int $status = 200, bool $pretty = false): static` | JSON body and content type. Encoding failures throw `RuntimeException`. |
| `html(string $html, int $status = 200): static` | HTML body |
| `text(string $text, int $status = 200): static` | Plain-text body |
| `noContent(): static` | 204, no body |
| `send(): void` | Emits the status, headers and body |
| `statusCodes(): array` | Every status code with its reason phrase and RFC reference |

`send()` does nothing once headers have been sent, and it never writes a body for 1xx, 204 or 304 responses. It echoes only strings, numbers and objects with `__toString()`; any other body is silently skipped, so use `json()` for arrays.

## Redirect

**Relay:** `Laika\Service\Redirect` (`redirect`). **Class:** `Laika\Core\Http\Redirect`.

```php
use Laika\Service\Redirect;

Redirect::with('Profile saved.', true)->to('profile.show', ['id' => 7]);
Redirect::back();
Redirect::to('https://example.org/docs', code: 301);
```

| Method | Does |
|---|---|
| `with(string $message, bool $status): static` | Stores a flash alert in the session. Read it back with [`alert_get()`](12_helper-functions.md#alerts). |
| `back(int $code = 302): void` | Returns to the referring page, if it's on this host |
| `to(string $to, array $params = [], int $code = 302): void` | Redirects to a named route, or to an absolute URL as-is |

Both redirect methods send a `Location` header and **exit**. Allowed codes are 301, 302 and 303; anything else throws `HttpException` (500).

`back()` treats the `Referer` header as untrusted. It follows the header only when its host matches this request's host (without port, and honouring trusted proxies), and only as a local path. Otherwise it goes to `/`. It also collapses `//evil.com` and `/\evil.com` style paths, which would otherwise leave the site.

> **Note:** `to()` sends any value containing a host straight to the browser. Never pass it user input (such as a `?next=` parameter) without checking the host first, or it becomes an open redirect.

## CORS

**Relay:** `Laika\Service\CORS` (`cors`). **Class:** `Laika\Core\Http\CORS` (static).

laika-route calls `CORS::handle()` at the start of every request. Configure it in a hook file, which runs before routing:

```php
// lf-hooks/cors.php
use Laika\Service\CORS;

CORS::origins(['https://app.example.com']);
CORS::credentials(true);
CORS::expose(['X-Total-Count']);
```

| Method | Default |
|---|---|
| `origins(array $origins): void` | `['*']` |
| `methods(array $methods): void` | `GET, POST, PUT, PATCH, DELETE, OPTIONS` |
| `headers(array $headers): void` | `Content-Type, Authorization, X-Requested-With, Accept` |
| `expose(array $headers): void` | none |
| `credentials(bool $allow = true): void` | `false` |
| `maxAge(int $seconds): void` | 86400 |
| `securityHeaders(array $headers): void` | See below. Replaces the whole set. |
| `handle(): void` | Sends the headers, and answers preflights |

What `handle()` does:
- **Security headers,** always sent: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: sameorigin`, `Content-Security-Policy: frame-ancestors 'self'`, and `X-Powered-By: Laika Framework`.
- **Allowed origin:** `Access-Control-Allow-Origin` and `Vary: Origin` when the request's `Origin` is allowed.
- **Preflight:** on `OPTIONS` requests it also sends the allowed methods, headers and max-age, then answers `204` and exits.

> **Note:** with `credentials(true)` the matching `Origin` is echoed back with `Access-Control-Allow-Credentials: true`. Combined with the default `['*']` origins, that lets **any** website make authenticated requests to your API. Always list explicit origins when you enable credentials.

To hide the `X-Powered-By` header, pass your own list to `securityHeaders()` without it.

## CSRF

**Relay:** `Laika\Service\CSRF` (`csrf`). **Class:** `Laika\Core\Http\CSRF`.

Stateless, signed, single-use tokens.

```php
use Laika\Service\CSRF;

echo CSRF::field();                    // <input type="hidden" name="_csrf" value="…">

try {
    CSRF::validate(CSRF::fromRequest()); // X-Csrf-Token header, else POST _csrf
} catch (\Laika\Core\Exceptions\CSRFException $e) {
    // malformed, bad signature, expired, fingerprint mismatch, or already used
}
```

| Method | Does |
|---|---|
| `generate(): string` | A new token: `payload.signature` |
| `validate(?string $token): bool` | `true`, or throws `CSRFException` with the reason |
| `fromRequest(string $header = 'X-Csrf-Token'): ?string` | The token from that header, or the `_csrf` form field |
| `field(): string` | A hidden `<input>` with a fresh token |
| `setTtl(int $ttl): void` | Lifetime in seconds. Default 3600. |
| `bindFingerprint(bool $bind = true): void` | Bind tokens to the client's OS and user agent. Default on. |

How a token works:
- The payload holds a random id, the issue and expiry times, and the fingerprint.
- It's signed with HMAC-SHA256 using the [app key](05_config-and-app.md#app-key), so no server-side storage is needed.
- On successful validation the token id is **burned**: recorded in a `_xct` cookie, which keeps the last 20 ids. Presenting the same token again fails with "CSRF Token Already Used".

> **Note:** because tokens are single-use, a page that sends several AJAX requests needs a fresh token for each. The `TOKEN` constant that [`lf_header()`](12_helper-functions.md#template-output) prints is consumed by the first request that validates it.

> **Note:** the burn list lives in a cookie on the client. A client that deletes the cookie can replay a token until it expires. Keep `setTtl()` short for sensitive actions.

## ProxyTrust

**Class:** `Laika\Core\Http\ProxyTrust` (static).

Decides whether proxy headers (`X-Forwarded-*`, `CF-Connecting-IP`, …) may be believed. They're only trusted when `REMOTE_ADDR` is one of the proxies listed in `lf-config/app.php`:

```php
'trusted_proxies' => ['10.0.0.0/8', '192.168.1.5'],
```

| Method | Returns |
|---|---|
| `ranges(): array` | The configured IPs and CIDRs, read once per process |
| `trusts(?string $ip = null): bool` | Whether the IP (default: `REMOTE_ADDR`) is a trusted proxy |
| `enabled(): bool` | Whether any proxy is configured |
| `flush(): void` | Re-read the configuration on next use |

An empty list, which is the default, trusts nothing: correct for a server reached directly. `'*'` trusts every peer. Use it only when the platform never exposes a stable proxy address. `Url`, `Visitor` and the cookie `secure` flags all rely on this class.

## Sanitizers

A sanitizer cleans request input before `Request` stores it. All of them implement `Laika\Core\Contracts\SanitizerInterface`:

```php
public function sanitize(array $data): array; // recursive
public function clean(mixed $value): mixed;   // one value
```

| Class | Does |
|---|---|
| `Laika\Core\Sanitizer\InputSanitizer` (default) | Trims and removes NUL bytes, optionally truncates, then `htmlspecialchars()` |
| `Laika\Core\Sanitizer\StripTagsSanitizer` | The same, but runs `strip_tags()` (keeping `$allowedTags`) before encoding |
| `Laika\Core\Sanitizer\NullSanitizer` | Returns everything unchanged |

`InputSanitizer::__construct()` takes these parameters:
- `$htmlFlags`: default `ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE`
- `$encoding`: default `UTF-8`
- `$doubleEncode`: default `true`
- `$maxDepth`: default 50; deeper arrays are dropped
- `$maxStringLength`: default 0, meaning no limit

It keeps integers, floats and booleans as they are, and turns `NaN`, `INF`, objects and resources into `null`. Array keys are trimmed and stripped of NUL bytes.
