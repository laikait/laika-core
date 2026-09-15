# Templates

## Template

**Class:** `Laika\Core\App\Template`. It's not a relay; controllers create one per render.

A thin wrapper around Twig 3:

| Setting | Value |
|---|---|
| Templates | `template/` (`TEMPLATE_PATH`) |
| Compiled cache | `lf-storage/cache/template` |
| Debug mode | Follows `DEBUG`, which also makes `dump()` available |
| Default file extension | `.twig` |

```php
use Laika\Core\App\Template;

$tpl = new Template();
$tpl->assign('title', 'Orders');
$tpl->assign(['orders' => $orders, 'total' => $total]);

return $tpl->view('admin/orders/list');   // template/admin/orders/list.twig
```

| Method | Does |
|---|---|
| `view(string $name): string` | Renders a view and returns the HTML |
| `assign(string\|array $key, mixed $value = null): void` | Sets one variable, or several from an array |
| `addPath(string $path): static` | Adds a fallback template directory, searched after the view's own |
| `addFilter(string $name, string\|callable $callable): void` | Registers a Twig filter |
| `engine(): Environment` | The Twig environment, for `addFunction()`, `addGlobal()`, `addExtension()` … |
| `vars(): array` | The variables a render will receive: the defaults plus yours |
| `html(): static` / `twig(): static` | Render `.html` files instead of `.twig`, or switch back |

**View names** are slash-separated, and the directory part picks the template root for that render. `view('admin/orders/list')` renders `template/admin/orders/list.twig`, with Twig's loader pointed at `template/admin/orders`, then at any `addPath()` directories. Absolute names and names containing `..` throw `PathException`.

> **Note:** `{% extends %}` and `{% include %}` resolve against the view's **own** directory and the `addPath()` fallbacks only. For a layout shared by views in several sub-directories, add the shared directory: `$tpl->addPath(TEMPLATE_PATH)` or `$tpl->addPath(TEMPLATE_PATH . '/layouts')`.

> **Note:** `extension()` is deprecated and raises `E_USER_DEPRECATED`, which Laika's error handler turns into an exception. Use `html()` or `twig()`.

**Loader files.** On construction, `template/loader.php` is created if missing and then required. When a view comes from a sub-directory, that directory's own `loader.php` is also required, if it exists. These files are the place to [enqueue](#asset) styles, scripts and meta tags.

### Default Template Variables

Every render receives these variables, computed at render time. A variable you `assign()` under the same name replaces the default.

| Variable | Contents |
|---|---|
| `local` | The selected [language](05_config-and-app.md#local-localisation) |
| `page` | `{number, next, previous}` from [Page](04_url-client-ip.md#page) |
| `input` | Request input: `{{ input.email }}`, or `{{ input.tags(0) }}` for an array item. A missing key reads as `''`. |
| `errors` | `Request::errors()` |
| `visitor` | `Visitor::info()`: `ip`, `os`, `browser`, `device`, `language`, `agent`, `isBot` |
| `context` | Everything in [Context](#context) |

### Twig Filters

| Filter | Example | Does |
|---|---|---|
| `hook` | `{{ 'page_title'|hook('Home') }}` | `apply_hook()`; see [Helper Functions](12_helper-functions.md#using-hooks-from-twig) |
| `asset` | `{{ 'css/app.css'|asset }}` | An absolute asset URL |
| `named` | `{{ 'orders.show'|named({id: order.id}) }}` | A named route's URL |
| `query` | `{{ 'search'|query }}` | A query-string value |
| `slug` | `{{ 2|slug }}` | A URL segment, counted from 1 |
| `context` | `{{ 'user'|context }}` | A context value |
| `decode` | `{{ body|decode }}` | `htmlspecialchars_decode()`, to undo input encoding for display |

```twig
<!doctype html>
<html lang="{{ local }}">
<head>
    <title>{{ 'page_title'|hook(title) }}</title>
    {{ 'lf_header'|hook }}
</head>
<body>
    <form method="post" action="{{ 'contact.send'|named }}">
        {{ 'csrf_field'|hook }}
        <input name="email" value="{{ input.email }}">
        {% for message in errors.email ?? [] %}<p class="error">{{ message }}</p>{% endfor %}
    </form>
    <a href="{{ page.next }}">Next page</a>
    {{ 'lf_footer'|hook }}
</body>
</html>
```

## Asset

**Relay:** `Laika\Service\Asset` (`template.asset`). **Class:** `Laika\Core\Template\Asset` (static). **Helpers:** `enqueue_style()`, `enqueue_script()`, `print_styles()`, `print_scripts()`.

| Method | Does |
|---|---|
| `addStyle(string $handle, string $src, string $version = '1.0.0', string $media = 'all'): void` | Queues a stylesheet |
| `addScript(string $handle, string $src, string $version = '1.0.0', bool $defer = false): void` | Queues a script |
| `printStyles(): void` / `printScripts(): void` | Prints the queued `<link>` / `<script>` tags, each with `?v={version}` |
| `headerScripts(): void` | Prints the JS constants `TOKEN` (a fresh CSRF token) and `APP_URI` (the base URL) |

Relative sources resolve against `Url::base()`. A handle registered twice keeps its first registration. `lf_header()` prints the metas, styles and header scripts, and `lf_footer()` prints the scripts.

## Meta

**Relay:** `Laika\Service\Meta` (`template.meta`). **Class:** `Laika\Core\Template\Meta` (static). **Helpers:** `enqueue_meta()`, `print_metas()`.

| Method | Does |
|---|---|
| `add(string $name, string $content, string $type = 'name'): void` | Queues `<meta name="…">`, or `property="…"` for Open Graph. Another type throws. The same name replaces the earlier tag. |
| `print(): void` | Prints the tags, HTML-escaped |

## Context

**Relay:** `Laika\Service\Context` (`template.context`). **Class:** `Laika\Core\Template\Context` (static). **Helpers:** `context_add()`, `context_get()`.

A request-wide key/value store for passing data to templates from anywhere: pipelines, hooks, services.

| Method | Does |
|---|---|
| `set(string $key, mixed $value): void` | Stores a value |
| `get(?string $key = null, mixed $default = null): mixed` | One value, or everything when `$key` is null |
| `has(string $key): bool` / `pop(string $key): void` / `clear(): void` | Manage keys |

Keys must match `\w+` and are lowercased; anything else throws `ContextException`. In templates, use the `context` variable or the `|context` filter.

## Nav

**Relay:** `Laika\Service\Nav` (`nav`). **Classes:** `Laika\Core\Nav\Builder`, `Laika\Core\Nav\Helper\Item`.

Menus built from named routes. The active item is detected from the current URL.

```php
use Laika\Service\Nav;

Nav::add('Dashboard', 'dashboard')->icon('bi bi-speedometer');
Nav::add('Orders', 'orders.index')
    ->child('All orders', 'orders.index')->end()
    ->child('Refunds', 'orders.refunds', display: user_can('refunds'));

echo Nav::render('navbar');
```

| `Builder` method | Does |
|---|---|
| `add(string $title, string $named, array $namedParams = [], bool $display = true): Item` | A top-level item |
| `configure(array $config): static` | Renderer settings (classes, markup) |
| `current(?string $url): static` | Overrides the URL used to mark the active item |
| `find(string $name): ?Item` / `extend(string $name, callable $callback): static` | Look up or extend a named item |
| `render(string $class = 'navbar'): string` | The menu HTML |
| `items(): array` / `flush(): static` | Raw items / start over |

An `Item` offers:
- **Children:** `child()` and `end()`
- **Naming and lookup:** `name()` and `find()`
- **Attributes:** `addClass()`, `setId()`, `attr()`, `target()` and `rel()`
- **Icons:** `icon()` for a class name, `svg()` for inline SVG
- **State:** `active()`
- **Reading back:** `getTitle()`, `getUrl()`, `getName()`, `getIcon()`, `getSvg()`, `getActive()`, `getChildren()`, `hasChildren()`, `getClasses()`, `getAttributes()`, `getParent()`

The full guide, covering active-state rules, conditional display, styling and security notes, is in [src/Nav/README.MD](../src/Nav/README.MD).

## Icon

**Relay:** `Laika\Service\Icon` (`icon`). **Class:** `Laika\Core\Generator\Icon` (static).

Inline SVG icons (Bootstrap Icons path data, MIT). Nothing is loaded from a CDN, and icons inherit the text colour through `currentColor`.

```php
use Laika\Core\Generator\Icon;

Icon::svg('trash', 20);    // <svg … width="20" height="20" …>…</svg>
Icon::trash(20);           // the same, through the magic method
Icon::has('rocket');       // false
```

| Method | Returns |
|---|---|
| `svg(string $name, int $size = 16): string` | SVG markup. Size is clamped to 8–128; an unknown name falls back to `info`. |
| `has(string $name): bool` | Whether the icon exists |
| `names(): array` | Every icon name |

Available icons:
- **Arrows:** `arrow-left`, `arrow-right`, `arrow-up`, `arrow-down`
- **Actions:** `plus`, `edit`, `trash`, `save`, `search`, `refresh`, `download`, `printer`, `check`, `cross`, `ban`
- **Status:** `info`, `warning`, `eye`, `activity`
- **Account:** `user`, `staff`, `clients`, `roles`, `key`, `login`, `logout`
- **Business:** `dashboard`, `reports`, `orders`, `products`, `invoices`, `transactions`, `currency`, `card`, `tickets`
- **Infrastructure:** `database`, `servers`, `domains`, `modules`, `settings`
- **Other:** `mail`, `megaphone`, `calendar`, `book`, `folder`, `menu`

Twig autoescapes filter output, so register the filter yourself and mark it raw:

```php
$tpl->addFilter('icon', [\Laika\Core\Generator\Icon::class, 'svg']);
```

```twig
<button>{{ 'trash'|icon(14)|raw }} Delete</button>
```
