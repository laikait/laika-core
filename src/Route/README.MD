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

Router::get('/home', 'HomeController@index');
Router::get('/user/store', 'HomeController@store');
```

Routes are defined using the static `Route` class:

```php
use Laika\Core\Route\Route;

Router::get('/home', [HomeController::class, 'index']);
Router::post('/user/store', [UserController::class, 'store']);
```

---

## HTTP Methods

| Method | Usage |
|--------|-------|
| `Router::get()` | GET requests |
| `Router::post()` | POST requests |
| `Router::put()` | PUT requests |
| `Router::patch()` | PATCH requests |
| `Router::delete()` | DELETE requests |

---

## Router Parameters

### Basic Parameter
Matches any value except `/`:

```php
Router::get('/user/{id}', [UserController::class, 'show']);
```

### Custom Regex Parameter

```php
Router::get('/user/{id:\d+}', [UserController::class, 'show']);
Router::get('/post/{slug:[a-z0-9-]+}', [PostController::class, 'show']);
```

### Multiple Parameters

```php
Router::get('/user/{id:\d+}/post/{slug}', [PostController::class, 'show']);
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
Router::group('/admin', function () {
    Router::get('/dashboard', [AdminController::class, 'dashboard']);
    Router::get('/users', [AdminController::class, 'users']);
    // Resolves to: /admin/dashboard, /admin/users
});
```

### Nested Groups

```php
Router::group('/api', function () {
    Router::group('/v1', function () {
        Router::get('/ping', [ApiController::class, 'ping']);
        // Resolves to: /api/v1/ping
    });
});
```

---

## Middleware

### Single Middleware

```php
Router::get('/dashboard', [DashboardController::class, 'index'])->middleware(AuthMiddleware::class);
```

### Multiple Middleware

```php
Router::get('/admin', [AdminController::class, 'index'])->middleware([
    AuthMiddleware::class,
    AdminMiddleware::class,
]);
```

### Group Middleware & Afterware

```php
Router::group('/admin', function () {
    Router::get('/dashboard', [AdminController::class, 'dashboard']);
}, [AuthMiddleware::class], [LogMiddleware::class]);
```

---

## Fallback Routes

Handle unmatched routes:

```php
// Global fallback
Router::fallback(function () {
    // 404 handler
});

// Prefix-based fallback
Router::fallback('/admin/', [AdminController::class, 'notFound']);
```

---

## Named Routes

Assign a name to generate URLs:

```php
Router::get('/user/{id:\d+}/profile', [UserController::class, 'profile'])->name('user.profile');
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
Author: **Showket Ahmed** — riyadhtayf@gmail.com