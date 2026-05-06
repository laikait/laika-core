# Laika Router

Part of the **Laika PHP MVC Framework** — a lightweight PHP 8.1+ routing system supporting dynamic placeholders, grouped prefixes, middleware, and fallback routes.

---

## Table of Contents

- [Defining Routes](#defining-routes)
- [HTTP Methods](#http-methods)
- [Router Parameters](#route-parameters)
- [Router Groups](#route-groups)
- [Middleware](#middleware)
- [Fallback Routes](#fallback-routes)
- [Named Routes](#named-routes)
- [Router Files](#route-files)

---

## Defining Routes

Routes are defined using `string`:

```php
use Laika\Core\App\Router;

Http::get('/home', 'HomeController@index');
Http::get('/user/store', 'HomeController@store');
```

Routes are defined using the static `Route` class:

```php
use Laika\Core\Route\Route;

Http::get('/home', [HomeController::class, 'index']);
Http::post('/user/store', [UserController::class, 'store']);
```

---

## HTTP Methods

| Method | Usage |
|--------|-------|
| `Http::get()` | GET requests |
| `Http::post()` | POST requests |
| `Http::put()` | PUT requests |
| `Http::patch()` | PATCH requests |
| `Http::delete()` | DELETE requests |

---

## Router Parameters

### Basic Parameter
Matches any value except `/`:

```php
Http::get('/user/{id}', [UserController::class, 'show']);
```

### Custom Regex Parameter

```php
Http::get('/user/{id:\d+}', [UserController::class, 'show']);
Http::get('/post/{slug:[a-z0-9-]+}', [PostController::class, 'show']);
```

### Multiple Parameters

```php
Http::get('/user/{id:\d+}/post/{slug}', [PostController::class, 'show']);
```

### Parameter Reference

| Syntax | Matches |
|--------|---------|
| `{name}` | Any value except `/` → `[^/]+` |
| `{name:pattern}` | Custom regex pattern |

Parameters are injected into the controller method automatically:

```php
class UserController
{
    public function show(int $id): void
    {
        // $id is available here
    }
}
```

---

## Router Groups

Group routes under a shared prefix:

```php
Http::group('/admin', function () {
    Http::get('/dashboard', [AdminController::class, 'dashboard']);
    Http::get('/users', [AdminController::class, 'users']);
    // Resolves to: /admin/dashboard, /admin/users
});
```

### Nested Groups

```php
Http::group('/api', function () {
    Http::group('/v1', function () {
        Http::get('/ping', [ApiController::class, 'ping']);
        // Resolves to: /api/v1/ping
    });
});
```

---

## Middleware

### Single Middleware

```php
Http::get('/dashboard', [DashboardController::class, 'index'])->middleware(AuthMiddleware::class);
```

### Multiple Middleware

```php
Http::get('/admin', [AdminController::class, 'index'])->middleware([
    AuthMiddleware::class,
    AdminMiddleware::class,
]);
```

### Group Middleware & Afterware

```php
Http::group('/admin', function () {
    Http::get('/dashboard', [AdminController::class, 'dashboard']);
})->middleware(AuthMiddleware::class)->afterware(LogMiddleware::class);
```

---

## Fallback Routes

Handle unmatched routes:

```php
// Global fallback
Http::fallback(function () {
    // 404 handler
});

// Prefix-based fallback
Http::fallback('/admin/', [AdminController::class, 'notFound']);
```

---

## Named Routes

Assign a name to generate URLs:

```php
Http::get('/user/{id:\d+}/profile', [UserController::class, 'profile'])->name('user.profile');
```

Generate the URL using the `named()` helper:

```php
named('user.profile', ['id' => 42]);
// Output: /user/42/profile
```

---

## Router Files

Routes are auto-loaded from the `lf-routes/` directory inside your app path. Any `.php` file placed there is loaded automatically — no manual registration needed.

```
app-path/
└── lf-routes/
    ├── web.php
    ├── api.php
    └── admin.php
```

---

## License

MIT — Part of the [Laika PHP MVC Framework](https://github.com/laikait).  
Author: **Showket Ahmed** — strblackhawk@gmail.com