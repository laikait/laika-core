# URL, Client & IP

## Url

**Relay:** `Laika\Service\Url` (`url`). **Class:** `Laika\Core\Helper\Url`.

Built once per request from `$_SERVER`. It works out the scheme, host, port and the sub-directory the app is installed in, so links stay correct behind proxies and in sub-folder installs.

```php
use Laika\Service\Url;

Url::base();                         // https://example.com/shop/
Url::build('orders', ['page' => 2]); // https://example.com/shop/orders?page=2
Url::path();                         // orders/42 (sub-directory stripped)
Url::segment(2);                     // "42"
```

| Method | Returns |
|---|---|
| `current(): string` | The full current URL, without a trailing slash |
| `base(): string` | `scheme://host[:port]/sub-dir/`, always with a trailing slash |
| `directory(): string` | The sub-directory the app lives in (`shop`), or `''` |
| `path(): string` | The request path relative to the base, trimmed of slashes |
| `port(): int` | The detected port |
| `queries(): array` | The query string as an array, passed through `purify()` |
| `query(string $key, ?string $default = null): ?string` | One query value |
| `build(string $path, array $params = []): string` | An absolute URL under the base |
| `segment(int $index): ?string` | One path segment, **counted from 1** |
| `segments(): array` | All path segments |
| `withQuery(array $params): string` | The current path with those query values merged in |
| `withoutQuery(array $keys): string` | The current path without those query keys |
| `incrementQuery(?string $key = null): string` | The current URL with `?page` (or `$key`) plus 1 |
| `decrementQuery(?string $key = null): string` | The same, minus 1, never below 1 |
| `host(): string` | The host, without port |
| `isHttps(): bool` | Whether the client-facing scheme is HTTPS |
| `scheme(): string` | `http` or `https` |

**How the scheme is detected** (first match wins):
1. `X-Forwarded-Proto`, only from a trusted proxy
2. `X-Forwarded-Ssl` / `Front-End-Https`
3. `X-Url-Scheme`
4. Cloudflare's `CF-Visitor`
5. `$_SERVER['HTTPS']`
6. `REQUEST_SCHEME`
7. port 443

**How the host is detected:**
1. `X-Forwarded-Host`, trusted proxies only
2. `HTTP_HOST`
3. `SERVER_NAME`
4. `localhost`

Every candidate is validated before use, since the Host header is client-controlled.

> **Note:** proxy headers are ignored unless the request comes from an address listed in `trusted_proxies` in `lf-config/app.php`. Behind a load balancer, set it, or `isHttps()` and `base()` will describe the proxy's hop. See [ProxyTrust](03_http.md#proxytrust).

## Page

**Relay:** `Laika\Service\Page` (`page`). **Class:** `Laika\Core\Helper\Page`.

Pagination helpers built on the `?page=` query value.

| Method | Returns |
|---|---|
| `number(?string $key = null): int` | The current page from the request input, never below 1 |
| `next(?string $key = null): string` | The URL of the next page |
| `previous(?string $key = null): string` | The URL of the previous page, never below page 1 |

`$key` defaults to `page` and is lowercased, matching `Url::incrementQuery()`. Templates get these three values as the `page` variable (see [Templates](06_templates.md#default-template-variables)).

## Visitor (Client)

**Relay:** `Laika\Service\Visitor` (`visitor`). **Class:** `Laika\Core\Helper\Client`.

Information about the client making the request.

| Method | Returns |
|---|---|
| `ip(): ?string` | The client IP, or `null` when none is valid. Proxy-aware, see below. |
| `userAgent(): string` | The raw user agent, or `Unknown` |
| `language(): string` | The first `Accept-Language` entry, or `en-US` |
| `os(): string` | For example `Windows 10`, `Android 14`, `Mac OS X 10.15.7`, `Linux`, `Unknown` |
| `browser(): string` | Name and version, for example `Chrome 124.0.0.0` or `Firefox 125.0` |
| `deviceType(): string` | `Bot`, `Tablet`, `Mobile` or `Desktop` |
| `isBot(): bool` | Whether the user agent matches known crawlers or the `SomeBot/1.0` and `+http://` shapes |
| `info(): array` | All of the above: `ip`, `os`, `browser`, `device`, `language`, `agent`, `isBot` |
| `refresh(): static` | Forgets the cached IP and user agent, for long-running workers |

`ip()` uses `REMOTE_ADDR` unless the peer is a trusted proxy. In that case laika-shield's `IpHelper` walks `X-Forwarded-For` from right to left and returns the first hop your proxies didn't add.

> **Note:** browser detection is a pattern list checked in order, and `Chrome/` is checked before Opera, Brave, Vivaldi and Samsung Internet. Those browsers all include `Chrome/` in their user agent, so they're reported as Chrome. Use the result for display and statistics, not for feature decisions.

## Cookie

**Relay:** `Laika\Service\Cookie` (`cookie`). **Class:** `Laika\Core\Helper\Cookie`.

```php
use Laika\Service\Cookie;

Cookie::set('theme', 'dark');                        // 7 days, httponly, SameSite=Strict
Cookie::ttl(3600)->path('/admin')->set('tab', 'users');
Cookie::set('prefs', ['lang' => 'en']);              // arrays/objects are JSON-encoded

Cookie::get('prefs');                                // ['lang' => 'en']
Cookie::pop('theme');
```

| Method | Does |
|---|---|
| `policy(string $policy): static` | SameSite: `strict`, `lax` or `none`. `none` throws `InvalidArgumentException` on plain HTTP. |
| `ttl(int $ttl): static` | Lifetime in seconds. Default 604800 (7 days). |
| `httponly(bool $httponly = true): static` | Hide from JavaScript. Default `true`. |
| `path(string $path): static` | Cookie path. Default `/`. |
| `set(string $name, mixed $value): bool` | Writes the cookie |
| `get(string $name, mixed $default = null): mixed` | Reads a cookie, JSON-decoding it when possible |
| `pop(string $name): void` | Expires a cookie present on this request |

Behaviour to know:
- **Options apply to one call.** `policy()`, `ttl()`, `httponly()` and `path()` configure the next `set()` or `pop()`, then reset to defaults. Chain them in the same expression.
- **`secure` follows `Url::isHttps()`,** so it's right behind a trusted proxy too.
- **No `domain` is ever sent.** The cookie is host-only, which is the tighter RFC 6265 behaviour.
- **To delete a cookie, match its path.** Call `pop()` with the same `path()` the cookie was set with, or the browser keeps the original.

> **Note:** `get()` JSON-decodes every value it can. A cookie holding `123` comes back as the integer `123`, and `true` comes back as the boolean `true`. Cast when you need a string.

> **Note:** set cookies before any output. After output starts, PHP can't send headers, and Laika's error handler turns that warning into an exception.

## IP Utilities

**Relay:** `Laika\Service\IP` (`ip`). **Classes:** `Laika\Core\IP\IP`, `Laika\Core\IP\Version\IPv4`, `Laika\Core\IP\Version\IPv6`.

CIDR maths for both address families.

```php
use Laika\Service\IP;

$net = IP::parse('192.168.10.0/24');   // IPv4 instance
$net->getBroadcastAddress();           // 192.168.10.255
$net->getUsableHosts();                // 254
$net->contains('192.168.10.42');       // true

IP::ipInCidr('2001:db8::1', '2001:db8::/32');         // true
IP::summarise(['10.0.0.0/25', '10.0.0.128/25']);      // ['10.0.0.0/24']
```

| `IP` method | Returns |
|---|---|
| `parse(string $cidr): IPv4\|IPv6` | A network object for the CIDR |
| `fromRange(string $startIp, string $endIp): IPv4\|IPv6` | The smallest block covering the range |
| `fromMask(string $networkIp, string $subnetMask): IPv4` | A block from a dotted mask (IPv4 only) |
| `ipInCidr(string $ip, string $cidr): bool` | Whether the IP is inside the block |
| `summarise(array $cidrs): array` | Merges adjacent and overlapping blocks |

What the network objects offer:
- **Address details:** network, broadcast, masks, first and last host, totals, class and flags (private, loopback, link-local, and multicast for IPv6)
- **Relationships:** `contains()`, `overlaps()`
- **Resizing:** `split()`, `supernet()`, `sibling()` (IPv4)
- **Enumeration:** host generators and `toArray()`
- **Other:** reverse-DNS zones and `info()`

Invalid input throws `Laika\Core\Exceptions\IPException`.

The full guide, with every method, the `/31` and `/32` edge cases, and IPv6 examples, is in [src/IP/README.MD](../src/IP/README.MD).
