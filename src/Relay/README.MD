# Laika Relay — Service Container

**Relay** is the service container and static proxy system for the [Laika PHP MVC Framework](https://github.com/ShowketAhmed/laika-framework). It provides a lightweight dependency injection container (`RelayRegistry`) and a static proxy base class (`Relay`) that lets you call any registered service using clean, static-looking syntax — without sacrificing testability or flexibility.

> Part of `laikait/laika-core`

---

## Table of Contents

- [Concepts](#concepts)
- [File Structure](#file-structure)
- [RelayRegistry](#relayregistry)
  - [bind()](#bind)
  - [singleton()](#singleton)
  - [instance()](#instance)
  - [make()](#make)
  - [forgetInstance()](#forgetinstance)
- [Auto-Wiring](#auto-wiring)
- [Bootstrap](#bootstrap)
- [Creating a Relay Class](#creating-a-relay-class)
- [Using a Relay](#using-a-relay)
- [Method Chaining](#method-chaining)
- [Swapping Instances](#swapping-instances)
- [Testing](#testing)
- [Exceptions](#exceptions)

---

## Concepts

The Relay system has two parts:

**`RelayRegistry`** — the container. Holds bindings and resolves instances. Supports auto-wiring via reflection.

**`Relay`** — the static proxy base. A thin class that forwards static calls to the instance resolved by the registry. You extend this once per service to get clean static syntax with full IDE support.

```
Your Code
    │
    ▼
Auth::check()               ← Relay proxy (static call)
    │
    ▼
RelayRegistry::make('auth') ← Resolves and caches the instance
    │
    ▼
Laika\Core\Auth\Auth        ← Real class, real method
```

---

## File Structure

```
src/
└── Relay/
    ├── Relay.php               # Abstract base — extend to create a proxy
    ├── RelayRegistry.php       # The container
    ├── Exceptions/
    │   └── RelayException.php  # Thrown on misconfiguration or resolution failure
    └── Relays/
        ├── Auth.php
        ├── Config.php
        ├── Session.php
        ├── Date.php
        └── Csrf.php
```

> `Relays/` is a convention, not a requirement. You can place Relay classes anywhere.

---

## RelayRegistry

The registry is the heart of the container. All bindings are registered here before the application starts.

```php
use Laika\Core\Relay\RelayRegistry;

$registry = new RelayRegistry();
```

---

### `bind()`

Creates a **new instance on every `make()` call**. Nothing is cached.

```php
$registry->bind(string $key, Closure|string $concrete, array $args = []);
```

Use for stateless, disposable objects where shared state would be a bug.

```php
$registry->bind('validator', Validator::class);

// Every call returns a fresh instance
$v1 = $registry->make('validator');
$v2 = $registry->make('validator');
// $v1 !== $v2
```

---

### `singleton()`

Builds the instance **once on first `make()`**, then returns the same instance on every subsequent call.

```php
$registry->singleton(string $key, Closure|string $concrete, array $args = []);
```

Use for stateful services shared across the entire request — session, auth, config, mailer.

```php
// Class string — no args
$registry->singleton('session', Session::class);

// Class string — with primitive args
$registry->singleton('mailer', Mailer::class, ['smtp']);

// Class string — args by name
$registry->singleton('queue', DatabaseDriver::class, [
    'table'   => 'async_jobs',
    'retries' => 3,
]);

// Closure — full manual control
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

---

### `instance()`

Registers an **already-constructed object** directly. The registry stores exactly what you give it — it never calls `new`.

```php
$registry->instance(string $key, object $instance);
```

Use when the object already exists before the registry is set up, or when construction has side effects you need to control manually.

```php
$pdo = new PDO($dsn, $user, $pass);
$registry->instance('db', $pdo);
```

---

### Lifetime Comparison

| Method | Who builds it | How many instances | When built | Cached |
|---|---|---|---|---|
| `instance()` | You | 1 (yours) | Before registration | Yes — immediately |
| `singleton()` | Registry | 1 | On first `make()` | Yes — after first use |
| `bind()` | Registry | N (one per call) | On every `make()` | Never |

> **Prefer `singleton()` over `instance()`** for most services. `singleton()` is lazy — if nothing ever calls `make('x')`, the object is never built. `instance()` is eager — it exists from the moment you register it.

---

### `make()`

Resolves a binding by key.

```php
$auth = $registry->make('auth');
```

Resolution order:

1. Pre-bound instance (`instance()`)
2. Singleton cache (already resolved)
3. Singleton binding — build, cache, return
4. Transient binding — build and return
5. Bare class name — attempt direct auto-wire
6. `RelayException` — nothing matched

---

### `forgetInstance()`

Clears a cached singleton instance, forcing re-resolution on the next `make()`.

```php
$registry->forgetInstance('date');
$registry->singleton('date', Date::class, ['America/New_York']);

// Next make('date') builds a fresh instance with the new timezone
```

---

## Auto-Wiring

When a class string is registered (not a Closure), the registry uses **reflection** to resolve constructor parameters automatically.

```php
// Auth depends on Session and Config — both already registered
$registry->singleton('session', Session::class);
$registry->singleton('config',  Config::class);
$registry->singleton('auth',    Auth::class);   // Session and Config injected automatically
```

Resolution order per constructor parameter:

```
1. Type-hinted class found in registry   → make() it
2. Type-hinted class exists (not in registry) → build() recursively
3. Primitive / override                  → pull from $args (named or positional)
4. Has default value                     → use it
5. Nullable                              → pass null
6. Nothing matched                       → throw RelayException
```

Mixed example — auto-wire objects, provide primitives:

```php
// Mailer::__construct(Config $config, string $driver)
// Config is in the registry; 'smtp' cannot be auto-wired
$registry->singleton('mailer', Mailer::class, ['smtp']);
```

---

## Bootstrap

Call `Relay::setRegistry()` once during application bootstrap. This wires the registry into every Relay class in the system.

```php
use Laika\Core\Relay\Relay;
use Laika\Core\Relay\RelayRegistry;

$registry = new RelayRegistry();

$registry->singleton('config',  Config::class);
$registry->singleton('session', Session::class);
$registry->singleton('auth',    Auth::class);
$registry->singleton('date',    Date::class);
$registry->singleton('csrf',    Csrf::class);

Relay::setRegistry($registry);
```

> `setRegistry()` throws `RelayException` if called a second time. Use `swapRegistry()` in tests only.

---

## Creating a Relay Class

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

**Rules for `@method` on Relay classes:**

- Always include `static` — every call through a Relay is a static call.
- Use imported short names instead of FQCNs where possible.
- Union return types are valid: `@method static Item|Builder end()`
- Array types are valid: `@method static Item[] all()`

---

## Using a Relay

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

You do **not** need a Relay class for every service. Direct registry access works too:

```php
// Via registry directly
$session = Relay::getRegistry()->make('session');
$session->set('user_id', 42);
```

---

## Method Chaining

Chaining works automatically as long as the underlying method returns `$this`.

```php
// First call goes through __callStatic, rest are normal instance calls
Date::now()
    ->setTimezone('Asia/Dhaka')
    ->modify('+7 days')
    ->setFormat('d M Y')
    ->format();

Date::fromFormat('d/m/Y', '01/04/2025')
    ->toUtc()
    ->toIso8601();
```

> Chaining breaks if a method returns a non-object (string, int, array). Terminal methods like `format()`, `getTimestamp()`, and `toArray()` end the chain.

---

## Swapping Instances

To replace the resolved instance for a specific Relay (e.g. switching guards in middleware):

```php
use Laika\Core\Auth\Auth as AuthService;
use Laika\Core\Relay\Relays\Auth;

// Swap to a new Auth instance with different parameters
Auth::swap(new AuthService(guard: 'admin', table: 'admins'));

// All subsequent Auth:: calls now use the swapped instance
Auth::check(); // checks against 'admins' table
```

Or re-register via the registry:

```php
Relay::getRegistry()->forgetInstance('auth');
Relay::getRegistry()->singleton('auth', AuthService::class, ['admin', 'admins']);
```

---

## Testing

Use `swap()` to inject mocks or stubs. Use `clearResolvedInstance()` to restore original behaviour between tests.

```php
use Laika\Core\Relay\Relays\Auth;

// Inject a mock
$mock = $this->createMock(\Laika\Core\Auth\Auth::class);
$mock->method('check')->willReturn(true);

Auth::swap($mock);

// Run test
$this->assertTrue(Auth::check());

// Restore
Auth::clearResolvedInstance();
```

Replace the entire registry for isolated unit tests:

```php
use Laika\Core\Relay\Relay;
use Laika\Core\Relay\RelayRegistry;

$testRegistry = new RelayRegistry();
$testRegistry->instance('auth', $mockAuth);
$testRegistry->instance('session', $mockSession);

Relay::swapRegistry($testRegistry);

// ... run tests ...
```

---

## Exceptions

All errors throw `Laika\Core\Relay\Exceptions\RelayException`.

| Situation | Message |
|---|---|
| `setRegistry()` called twice | `RelayRegistry has already been set.` |
| `getRegistry()` before `setRegistry()` | `RelayRegistry has not been set.` |
| No binding found for key | `No binding registered for [key].` |
| Class not found during build | `Class [ClassName] not found.` |
| Unresolvable constructor parameter | `Cannot resolve parameter [$name] for class [ClassName].` |
| Method not found on resolved instance | `Method [method] does not exist on [ClassName].` |

---

## License

MIT — see [LICENSE](../../LICENSE) for details.

**Author:** Showket Ahmed  
**Email:** riyadhtayf@gmail.com  
**Package:** `laikait/laika-core`  
**GitHub:** [ShowketAhmed/laika-framework](https://github.com/laikait/laika-framework)