# Laika Relay — Service Container & Static Proxy

**Relay** is the service container and static proxy system built for the [Laika Framework](https://github.com/laikait/laika-framework). It gives you a lightweight dependency injection container (`RelayRegistry`), a clean static proxy base (`Relay`), and a two-phase provider system (`RelayProvider` + `ProviderRegistry`) that lets third-party packages register their own services into the framework.

> Part of `laikait/laika-core` · Requires PHP 8.1+

---

## Table of Contents

- [How It Works](#how-it-works)
- [File Structure](#file-structure)
- [RelayRegistry](#relayregistry)
  - [instance()](#instance)
  - [singleton()](#singleton)
  - [bind()](#bind)
  - [make()](#make)
  - [has()](#has)
  - [forgetInstance()](#forgetinstance)
  - [Lifetime Comparison](#lifetime-comparison)
- [Auto-Wiring](#auto-wiring)
- [RelayProvider](#RelayProvider)
  - [register()](#register)
  - [boot()](#boot)
  - [register() vs boot()](#register-vs-boot)
- [ProviderRegistry](#providerregistry)
- [Bootstrap](#bootstrap)
- [Third-Party Integration](#third-party-integration)
- [Relay — The Static Proxy](#relay--the-static-proxy)
  - [Creating a Relay Class](#creating-a-relay-class)
  - [Using a Relay](#using-a-relay)
  - [Method Chaining](#method-chaining)
  - [Switching Instances at Runtime](#switching-instances-at-runtime)
- [Testing](#testing)
- [Exceptions](#exceptions)

---

## How It Works

```
Your Code
    │
    ▼
Auth::check()                    ← Relay proxy  (static call)
    │
    ▼
RelayRegistry::make('auth')      ← Container    (resolves & caches)
    │
    ▼
Laika\Core\Auth\Auth::check()    ← Real class   (real method)
```

There are three independent pieces:

| Class | Role |
|---|---|
| `RelayRegistry` | The container. Holds bindings, resolves and caches instances. |
| `Relay` | Abstract base. Forwards static calls to the resolved instance. |
| `RelayProvider` | Integration point. Packages extend this to register services. |
| `ProviderRegistry` | Manages provider lifecycle — calls `register()` then `boot()`. |

---

## File Structure

```
src/
└── Relay/
    ├── Relay.php                       # Abstract base — extend to create a proxy
    ├── RelayRegistry.php               # The container
    ├── RelayProvider.php             # Abstract base for service providers
    ├── ProviderRegistry.php            # Manages provider loading and booting
    ├── Exceptions/
    │   └── RelayException.php          # All Relay errors throw this
    ├── Providers/
    │   └── CoreRelayProvider.php     # Registers built-in Laika services
    └── Relays/
        ├── Auth.php
        ├── Config.php
        ├── Session.php
        ├── Date.php
        └── Csrf.php
```

> `Relays/` is a convention, not a requirement. Relay classes can live anywhere.

---

## RelayRegistry

The container. All services are registered here before the application starts handling requests.

```php
use Laika\Core\Relay\RelayRegistry;

$registry = new RelayRegistry();
```

---

### `instance()`

Register an **already-constructed object** directly.

```php
$registry->instance(string $key, object $instance): static
```

The registry stores exactly the object you give it — it never calls `new`. The object is available immediately on `make()`.

```php
$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$registry->instance('db', $pdo);
```

Use when the object already exists before the registry is set up, or when construction has side effects that must be controlled manually.

---

### `singleton()`

Register a **singleton binding** — built once on the first `make()` call, then cached and reused for the lifetime of the request.

```php
$registry->singleton(string $key, Closure|string $concrete, array $args = []): static
```

```php
// Class string — no args needed
$registry->singleton('session', Session::class);

// Class string — with primitive args (positional)
$registry->singleton('mailer', Mailer::class, ['smtp']);

// Class string — with primitive args (named)
$registry->singleton('queue', DatabaseDriver::class, [
    'table'   => 'async_jobs',
    'retries' => 3,
]);

// Closure — manual control, receives the registry
$registry->singleton('db', function (RelayRegistry $r) {
    $config = $r->make('config');
    $pdo    = new PDO(
        $config->get('db.dsn'),
        $config->get('db.user'),
        $config->get('db.pass')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});

// Closure — conditional factory
$registry->singleton('cache', function (RelayRegistry $r) {
    return match ($r->make('config')->get('cache.driver')) {
        'redis' => new RedisCache(),
        'file'  => new FileCache(),
        default => new ArrayCache(),
    };
});
```

Use for stateful services shared across the entire request: session, auth, config, mailer, cache.

---

### `bind()`

Register a **transient binding** — a brand-new instance is created on every `make()` call. Nothing is ever cached.

```php
$registry->bind(string $key, Closure|string $concrete, array $args = []): static
```

```php
$registry->bind('validator', Validator::class);

$v1 = $registry->make('validator');
$v2 = $registry->make('validator');
// $v1 !== $v2  — completely independent instances
```

Use for stateless, disposable objects where shared state would be a bug: validators, form request objects, DTOs, value objects.

---

### `make()`

Resolve a binding by key and return the object.

```php
$auth = $registry->make('auth');
```

**Resolution order:**

```
1. Pre-bound instance      (instance())
2. Cached singleton        (already resolved on a prior make())
3. Singleton binding       → build, cache, return
4. Transient binding       → build fresh, return (no cache)
5. Bare class name         → attempt direct auto-wire if class exists
6. RelayException          → nothing matched
```

---

### `has()`

Check whether a key has any binding registered.

```php
if ($registry->has('payment')) {
    $gateway = $registry->make('payment');
}
```

---

### `forgetInstance()`

Clear a cached singleton instance, forcing re-resolution on the next `make()`.

```php
$registry->forgetInstance('date');
$registry->singleton('date', Date::class, ['America/New_York']);

// Next make('date') builds a fresh instance with the new args
```

---

### Lifetime Comparison

| Method | Who builds it | How many instances | When built | Cached |
|---|---|---|---|---|
| `instance()` | You | 1 (yours) | Before registration | Yes — immediately |
| `singleton()` | Registry | 1 | On first `make()` | Yes — after first use |
| `bind()` | Registry | N (one per call) | On every `make()` | Never |

> **Prefer `singleton()` over `instance()`** for most services. `singleton()` is **lazy** — if nothing ever calls `make('x')`, the object is never constructed. `instance()` is **eager** — the object exists the moment you register it, whether anything uses it or not.

---

## Auto-Wiring

When a **class string** is registered (not a Closure), the registry uses PHP reflection to resolve constructor parameters automatically.

```php
// Auth::__construct(Session $session, Config $config)
// Both 'session' and 'config' are already in the registry → auto-wired

$registry->singleton('session', Session::class);
$registry->singleton('config',  Config::class);
$registry->singleton('auth',    Auth::class);   // Session and Config injected automatically
```

**Per-parameter resolution order:**

```
1. Type-hinted class found in registry        → make() it
2. Type-hinted class not in registry, exists  → build() recursively
3. Primitive — named key in $args             → use it
4. Primitive — positional in $args            → use it
5. Has a default value                        → use it
6. Nullable                                   → pass null
7. Nothing matched                            → throw RelayException
```

**Mixed — auto-wire objects, supply primitives:**

```php
// Mailer::__construct(Config $config, string $driver)
// Config is in the registry; 'smtp' cannot be auto-wired
$registry->singleton('mailer', Mailer::class, ['smtp']);
```

---

## RelayProvider

The integration point for packages. Extend `RelayProvider` and implement `register()`. Optionally override `boot()`.

```php
<?php

namespace YourPackage;

use Laika\Core\Relay\RelayProvider;

class YourRelayProvider extends RelayProvider
{
    public function register(): void
    {
        // Bind your services here
        $this->registry->singleton('your-service', YourService::class);
    }

    public function boot(): void
    {
        // Use other services here — everything is registered by now
        $config = $this->registry->make('config');
        $this->registry->make('your-service')->configure($config->get('your.key'));
    }
}
```

---

### `register()`

**Only call `bind()`, `singleton()`, or `instance()` here.** Do not call `make()`.

When `register()` runs, other providers may not have registered their services yet. Calling `make()` at this stage risks a `RelayException` if the dependency is not yet bound.

```php
public function register(): void
{
    // ✅ Correct
    $this->registry->singleton('payment', PaymentGateway::class);
    $this->registry->bind('payment.invoice', Invoice::class);

    // ❌ Wrong — 'config' may not be registered yet
    $key = $this->registry->make('config')->get('payment.key');
}
```

---

### `boot()`

Called after **all** providers have run `register()`. Safe to call `make()` freely.

```php
public function boot(): void
{
    // ✅ Config-driven setup
    $config  = $this->registry->make('config');
    $gateway = $this->registry->make('payment');
    $gateway->setApiKey($config->get('payment.stripe_key'));

    // ✅ Register middleware
    $router = $this->registry->make('router');
    $router->middleware('payment.verified', PaymentVerifiedMiddleware::class);

    // ✅ Attach event listeners
    $events = $this->registry->make('events');
    $events->listen('order.placed', SendPaymentRequestListener::class);

    // ✅ Register routes
    $router->group('/payment', function () use ($router) {
        $router->post('/charge',  'PaymentController@charge');
        $router->post('/webhook', 'PaymentController@webhook');
    });
}
```

---

### `register()` vs `boot()`

| | `register()` | `boot()` |
|---|---|---|
| Purpose | **Promise** a service exists | **Use** services that others promised |
| When called | Before other providers boot | After ALL providers have registered |
| Call `make()`? | ⚠️ Risky — others may not be ready | ✅ Safe — everything is registered |
| Typical use | `singleton`, `bind`, `instance` | Config setup, routes, middleware, events |

> One-line rule: **`register()` = bind things. `boot()` = use things.**

---

## ProviderRegistry

Manages the full provider lifecycle. Register providers in any order — the two-phase approach guarantees correctness.

```php
use Laika\Core\Relay\ProviderRegistry;

$providers = new ProviderRegistry($registry);

// Register accepts class string or instance
$providers->register(CoreRelayProvider::class);
$providers->register(new PaymentRelayProvider($registry));

// Boot all providers after all register() calls
$providers->boot();
```

**Duplicate registrations are silently ignored** — registering the same provider class twice is safe.

**`has()`** — check if a provider is registered:

```php
if ($providers->has(PaymentRelayProvider::class)) {
    // ...
}
```

---

## Bootstrap

Complete application bootstrap sequence:

```php
<?php

use Laika\Core\Relay\Relay;
use Laika\Core\Relay\RelayRegistry;
use Laika\Core\Relay\ProviderRegistry;
use Laika\Core\Relay\Providers\CoreRelayProvider;

// 1. Build the container
$registry  = new RelayRegistry();

// 2. Build the provider manager
$providers = new ProviderRegistry($registry);

// 3. Register the Laika core provider (always first)
$providers->register(CoreRelayProvider::class);

// 4. Register providers declared in config/app.php
foreach (config('app.providers') as $providerClass) {
    $providers->register($providerClass);
}

// 5. Boot all providers (runs after all register() calls are complete)
$providers->boot();

// 6. Wire the registry into the Relay system
Relay::setRegistry($registry);
```

`config/app.php`:

```php
return [
    'providers' => [
        // Third-party packages
        Acme\LaikaPayment\PaymentRelayProvider::class,
        Acme\LaikaBarcode\BarcodeRelayProvider::class,
    ],
];
```

---

## Third-Party Integration

This is how an external package integrates with Laika.

### 1. Create a RelayProvider

```php
<?php

namespace Acme\LaikaPayment;

use Laika\Core\Relay\RelayProvider;

class PaymentRelayProvider extends RelayProvider
{
    public function register(): void
    {
        $this->registry->singleton('payment',         PaymentGateway::class);
        $this->registry->singleton('payment.webhook', WebhookHandler::class);
        $this->registry->bind('payment.invoice',      Invoice::class);
    }

    public function boot(): void
    {
        $key = $this->registry->make('config')->get('payment.stripe_key');
        $this->registry->make('payment')->setApiKey($key);
    }
}
```

### 2. Ship a Relay class (optional but recommended)

```php
<?php

namespace Acme\LaikaPayment\Relay;

use Laika\Core\Relay\Relay;

/**
 * @method static string charge(int $amount, string $currency)
 * @method static bool   verify(string $token)
 * @method static array  history(int $limit = 10)
 */
class Payment extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'payment';
    }
}
```

### 3. User adds provider to `config/app.php`

```php
'providers' => [
    Acme\LaikaPayment\PaymentRelayProvider::class,
],
```

### 4. User calls it like any built-in service

```php
use Acme\LaikaPayment\Relay\Payment;

Payment::charge(1000, 'USD');
```

### Full flow

```
composer require acme/laika-payment
        ↓
Add PaymentRelayProvider to config/app.php
        ↓
Bootstrap: ProviderRegistry calls register() → binds 'payment'
        ↓
Bootstrap: ProviderRegistry calls boot()     → configures with API key
        ↓
Relay::setRegistry($registry)
        ↓
Payment::charge() → __callStatic → make('payment') → PaymentGateway::charge()
```

---

## Relay — The Static Proxy

### Creating a Relay Class

Extend `Relay`, implement `getRelayAccessor()`, and document every proxied method with `@method static` tags.

```php
<?php

declare(strict_types=1);

namespace Laika\Core\Relay\Relays;

use Laika\Core\Relay\Relay;

/**
 * @method static bool   attempt(array $credentials)
 * @method static bool   check()
 * @method static bool   guest()
 * @method static mixed  user()
 * @method static void   logout()
 */
class Auth extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'auth';
    }
}
```

**`@method` tag rules:**

- Always include `static` — every call through a Relay is a static call.
- Import types with `use` statements — same as real code.
- Union return types are valid: `@method static Item|Builder end()`
- Array types are valid: `@method static Item[] all()`
- FQCNs or short names both work (short names require `use`).

```php
use Laika\Core\Nav\Helper\Item;
use Laika\Core\Nav\Builder;

/**
 * @method static Item|Builder  end()
 * @method static Item[]        all()
 * @method static Item|null     find(string $name)
 */
```

---

### Using a Relay

Import the **Relay class**, not the underlying service class.

```php
// ✅ Correct — imports the Relay proxy
use Laika\Core\Relay\Relays\Auth;
use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\Session;

if (Auth::check()) {
    $name = Config::get('app.name');
    Session::set('last_page', '/dashboard');
}
```

```php
// ❌ Wrong — imports the real class, static calls fail
use Laika\Core\Auth\Auth;

Auth::check(); // Fatal: Non-static method cannot be called statically
```

You do **not** need a Relay class to access a service. The registry is always available:

```php
// Direct registry access — no Relay class needed
$session = Relay::getRegistry()->make('session');
$session->set('user_id', 42);
```

---

### Method Chaining

Chaining works automatically. The first static call goes through `__callStatic` and returns whatever the underlying method returns. If that's `$this`, subsequent calls are normal instance calls.

```php
// Entire chain — first call is static, rest are instance
Date::now()
    ->setTimezone('Asia/Dhaka')
    ->modify('+7 days')
    ->setFormat('d M Y')
    ->format();

Date::fromFormat('d/m/Y', '01/04/2025')
    ->toUtc()
    ->toIso8601();

Date::setTimestamp(time())
    ->modify('+1 hour')
    ->humanDiff();
```

Chaining ends when a method returns a non-object (string, int, array). Do not chain after terminal methods like `format()`, `getTimestamp()`, or `toArray()`.

---

### Switching Instances at Runtime

Use `swap()` to replace the resolved instance for a specific Relay — for example, switching auth guard in middleware.

```php
<?php

namespace Laika\Core\Middleware;

use Laika\Core\Auth\Auth as AuthService;
use Laika\Core\Relay\Relays\Auth;

class AdminMiddleware
{
    public function handle(): void
    {
        Auth::swap(new AuthService(guard: 'admin', table: 'admins'));
        // All subsequent Auth:: calls now resolve against the admin guard
    }
}
```

Or re-register via the registry for a cleaner approach:

```php
Relay::getRegistry()->forgetInstance('auth');
Relay::getRegistry()->singleton('auth', AuthService::class, ['admin', 'admins']);
```

---

## Testing

### Inject a mock for one test

```php
use Laika\Core\Relay\Relays\Auth;

protected function setUp(): void
{
    $mock = $this->createMock(\Laika\Core\Auth\Auth::class);
    $mock->method('check')->willReturn(true);

    Auth::swap($mock);
}

protected function tearDown(): void
{
    Auth::clearResolvedInstance();
}

public function test_dashboard_requires_auth(): void
{
    $this->assertTrue(Auth::check()); // uses mock
}
```

### Replace the entire registry for full isolation

```php
use Laika\Core\Relay\Relay;
use Laika\Core\Relay\RelayRegistry;

protected function setUp(): void
{
    $testRegistry = new RelayRegistry();
    $testRegistry->instance('auth',    $this->createMock(Auth::class));
    $testRegistry->instance('session', $this->createMock(Session::class));
    $testRegistry->instance('config',  $this->createMock(Config::class));

    Relay::swapRegistry($testRegistry);
}
```

> `swapRegistry()` is intentionally separate from `setRegistry()` so that accidental double-calls in production code throw a `RelayException`, while tests can swap freely.

---

## Exceptions

All errors throw `Laika\Core\Relay\Exceptions\RelayException`.

| Situation | Message |
|---|---|
| `setRegistry()` called more than once | `RelayRegistry has already been set.` |
| `getRegistry()` before `setRegistry()` | `RelayRegistry has not been set.` |
| No binding found for key | `No binding registered for [key].` |
| Class not found during build | `Class [ClassName] not found.` |
| Unresolvable constructor parameter | `Cannot resolve parameter [$name] for [ClassName].` |
| Method not found on resolved instance | `Method [method] does not exist on [ClassName].` |
| RelayProvider class not found | `RelayProvider [ClassName] class not found.` |
| Provider does not extend RelayProvider | `[ClassName] must extend Laika\Core\Relay\RelayProvider.` |

---

## License

MIT — see [LICENSE](../../LICENSE) for details.

**Author:** Showket Ahmed
**Email:** riyadhtayf@gmail.com
**Package:** `laikait/laika-relay`
**GitHub:** [laikait/laika-relay](https://github.com/laikait/laika-relay)