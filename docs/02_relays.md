# Relays & the Container

## How a Relay Resolves

A relay is a class in the `Laika\Service` namespace that extends `Laika\Relay\Relay`. It has no logic of its own. `getRelayAccessor()` names a container key, and every static call is forwarded to the instance the container holds for that key:

```php
use Laika\Service\Url;

Url::base();          // same as: $registry->make('url')->base()
```

- The relays ship in the `laikait/laika-relay` package (`services/`).
- The bindings behind them are registered by `Laika\Relay\CoreProviders` during boot. See [Getting Started](01_getting-started.md#what-happens-at-boot).
- `helpers/loader.php` hands the registry to `Relay::setRegistry()` once, and from then on every relay works.

The `Relay` base class adds a few static methods to every relay:

| Method | Does |
|---|---|
| `X::relayRoot()` | Returns the underlying instance, when you need the real typed object |
| `X::swap(object $instance)` | Replaces the bound instance, typically in tests |
| `X::clearResolvedInstance()` | Forgets the cached instance, so the next call builds a fresh one |

## Singleton or Per-Use

Most bindings are **singletons**, so one instance serves the whole request. Two are **bound per resolution** instead:
- `image`, because it holds a GD handle
- `upload`, because it holds a pending `$_FILES` entry

Sharing either of those would hand every caller the same half-used object.

> **Note:** singletons keep request state. `Request` parses the input once, `Client` caches the IP and user agent, and `Url` reads `$_SERVER` once. In a long-running worker, reset them between jobs, for example with `Visitor::refresh()` or `X::clearResolvedInstance()`.

## Relay List

| Relay (`Laika\Service\…`) | Key | Class | Lifetime | Page |
|---|---|---|---|---|
| `Activity` | `activity` | `Laika\Core\Log\Activity` | singleton | [Data](10_data.md#activity-log) |
| `AppKey` | `app.key` | `Laika\Core\App\Key` | singleton | [Config](05_config-and-app.md#app-key) |
| `Asset` | `template.asset` | `Laika\Core\Template\Asset` | singleton | [Templates](06_templates.md#asset) |
| `Config` | `config` | `Laika\Core\Helper\Config` | singleton | [Config](05_config-and-app.md#config) |
| `Context` | `template.context` | `Laika\Core\Template\Context` | singleton | [Templates](06_templates.md#context) |
| `Cookie` | `cookie` | `Laika\Core\Helper\Cookie` | singleton | [URL & Client](04_url-client-ip.md#cookie) |
| `CORS` | `cors` | `Laika\Core\Http\CORS` | singleton | [HTTP](03_http.md#cors) |
| `CSRF` | `csrf` | `Laika\Core\Http\CSRF` | singleton | [HTTP](03_http.md#csrf) |
| `Date` | `date` | `Laika\Core\Helper\Date` | singleton | [Utilities](09_utilities.md#date) |
| `Directory` | `directory` | `Laika\Core\Helper\Directory` | singleton | [Files](08_files-and-storage.md#directory) |
| `File` | `file` | `Laika\Core\Helper\File` | singleton | [Files](08_files-and-storage.md#file) |
| `Hook` | `hook` | `Laika\Core\Helper\Hook` | singleton | [Config](05_config-and-app.md#hook) |
| `Icon` | `icon` | `Laika\Core\Generator\Icon` | singleton | [Templates](06_templates.md#icon) |
| `Image` | `image` | `Laika\Core\Helper\Image` | **per use** | [Files](08_files-and-storage.md#image) |
| `Infra` | `infra` | `Laika\Core\App\Infra` | singleton | [Config](05_config-and-app.md#infra) |
| `Init` | `init` | `Laika\Core\Helper\Init` | singleton | [Config](05_config-and-app.md#init) |
| `IP` | `ip` | `Laika\Core\IP\IP` | singleton | [URL & Client](04_url-client-ip.md#ip-utilities) |
| `Local` | `local` | `Laika\Core\Helper\Local` | singleton | [Config](05_config-and-app.md#local-localisation) |
| `Math` | `math` | `Laika\Core\Helper\Math` | singleton | [Utilities](09_utilities.md#math) |
| `Meta` | `template.meta` | `Laika\Core\Template\Meta` | singleton | [Templates](06_templates.md#meta) |
| `MimeType` | `mime` | `Laika\Core\Helper\MimeType` | singleton | [Files](08_files-and-storage.md#mimetype) |
| `Nav` | `nav` | `Laika\Core\Nav\Builder` | singleton | [Templates](06_templates.md#nav) |
| `Option` | `option` | `Laika\Core\Model\OptionModel` | singleton | [Data](10_data.md#options) |
| `Page` | `page` | `Laika\Core\Helper\Page` | singleton | [URL & Client](04_url-client-ip.md#page) |
| `PhpMetadataParser` | `php.metadata.parser` | `Laika\Core\Helper\PhpMetadataParser` | singleton | [Utilities](09_utilities.md#phpmetadataparser) |
| `Redirect` | `redirect` | `Laika\Core\Http\Redirect` | singleton | [HTTP](03_http.md#redirect) |
| `Regex` | `regex` | `Laika\Core\Regex\Regex` | singleton | [Security](07_security.md#regex) |
| `Request` | `request` | `Laika\Core\Http\Request` | singleton | [HTTP](03_http.md#request) |
| `Resource` | `resource` | `Laika\Core\App\Resource` | singleton | [Config](05_config-and-app.md#resources) |
| `Response` | `response` | `Laika\Core\Http\Response` | singleton | [HTTP](03_http.md#response) |
| `Token` | `token` | `Laika\Core\Generator\Token` | singleton | [Security](07_security.md#token-jwt) |
| `Uid` | `uid` | `Laika\Core\Generator\Uid` | singleton | [Security](07_security.md#uid) |
| `Unique` | `unique` | `Laika\Core\Generator\Unique` | singleton | [Security](07_security.md#unique) |
| `Upload` | `upload` | `Laika\Core\Helper\Upload` | **per use** | [Files](08_files-and-storage.md#upload) |
| `Url` | `url` | `Laika\Core\Helper\Url` | singleton | [URL & Client](04_url-client-ip.md#url) |
| `Vault` | `vault` | `Laika\Core\Helper\Vault` | singleton | [Security](07_security.md#vault) |
| `Visitor` | `visitor` | `Laika\Core\Helper\Client` | singleton | [URL & Client](04_url-client-ip.md#visitor-client) |

Two relays don't share their class's name: `Visitor` fronts `Client`, and `AppKey` fronts `App\Key`.

## Using Classes Directly

A relay only forwards to a shared instance, so you can construct any class yourself when you want your own copy:

```php
use Laika\Core\Http\Request;
use Laika\Core\Sanitizer\NullSanitizer;

$raw = new Request(new NullSanitizer()); // a request that doesn't HTML-encode input
```

Some classes are static-only: `CORS`, `Hook`, `Config`, `MimeType`, `Resource`, `Asset`, `Meta`, `Context`, `Icon` and `Uid`. Calling them through the relay or on the class directly does the same thing, because the state lives in static properties.

## Registering Your Own

Applications add bindings through relay providers in `lf-app/Relay/`. Every `RelayProvider` found there is registered after the package providers, so an application can override a core binding. See the [laika-relay README](https://github.com/laikait/laika-relay) for `singleton()`, `bind()`, `instance()` and auto-wiring.
