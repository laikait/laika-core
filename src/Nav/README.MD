# Laika Nav

A fluent, recursive navigation builder for the [Laika PHP MVC Framework](https://github.com/ShowketAhmed/laika-framework).
Build deeply nested navigation trees from **named routes** with a chainable API, mark the current page automatically, and render them as escaped, semantic HTML.

---

## Namespace Structure

```
Laika\Core\Nav\Builder          — Root entry point
Laika\Core\Nav\Helper\Node      — Abstract base (shared item list)
Laika\Core\Nav\Helper\Item      — Chainable nav item (child / end / attributes)
Laika\Core\Nav\Helper\Renderer  — Turns a tree into HTML (all escaping lives here)
```

---

## Installation

The Nav package ships as part of `laikait/laika-core`. No separate installation required.

It is also bound in the relay registry as the `nav` singleton, so `Laika\Service\Nav` works out of the box — see [Using the Relay](#using-the-relay).

---

## Basic Usage

```php
use Laika\Core\Nav\Builder;

$nav = new Builder();

$nav->add('Home', 'home');       // 'home' and 'about' are route names,
$nav->add('About', 'about');     // registered with Route::name()

echo $nav->render();
```

**Output** (unindented — line breaks below are for readability only):

```html
<nav class="navbar" aria-label="Main">
  <ul class="nav-menu">
    <li class="nav-item"><a href="http://example.com/" class="nav-link">Home</a></li>
    <li class="nav-item"><a href="http://example.com/about" class="nav-link">About</a></li>
  </ul>
</nav>
```

---

## Route Names, Not Paths

The second argument to `add()` and `child()` is a **named route**, resolved through `Laika\Route\Handler::namedUrl()` and prefixed with `Url::base()`. Move a route and every menu entry follows it.

```php
$nav->add('Post', 'post.show', ['id' => 7]);
// -> http://example.com/post/7
```

Route parameters go in the third argument. A query string may be appended to the name and survives resolution:

```php
$nav->add('Archive', 'posts?tab=old');   // -> http://example.com/posts?tab=old
```

**Already-final targets pass through untouched** - absolute URLs, protocol-relative URLs, other schemes, and bare fragments:

```php
$nav->add('Docs',    'https://example.com/docs');
$nav->add('CDN',     '//cdn.example.com/app.js');
$nav->add('Contact', 'mailto:hello@example.com');
$nav->add('Top',     '#top');
```

An unregistered route name throws `RuntimeException` from `Handler::namedUrl()`. That is deliberate: a typo in a menu should fail loudly in development, not render a dead link.

> Because item URLs are base-absolute, Nav works unchanged under a subdirectory install. The renderer folds the subdirectory away before matching, so `/laika-project/services` and `/services` are the same item.

---

## Adding Children

Use `->child()` to go deeper, and `->end()` to climb back up to the parent.

```php
$nav = new Builder();

$nav->add('Home', 'home');
$nav->add('Services', 'services')
        ->child('Help', 'services.help')->end()
        ->child('Other', 'services.other')
            ->child('Etc 1', 'etc1')->end()
            ->child('Etc 2', 'etc2');

echo $nav->render();
```

**Output:**

```html
<nav class="navbar" aria-label="Main">
  <ul class="nav-menu">
    <li class="nav-item"><a href="http://example.com/" class="nav-link">Home</a></li>
    <li class="nav-item has-children"><a href="http://example.com/services" class="nav-link">Services</a>
      <ul class="nav-submenu">
        <li class="nav-item"><a href="http://example.com/services/help" class="nav-link">Help</a></li>
        <li class="nav-item has-children"><a href="http://example.com/services/other" class="nav-link">Other</a>
          <ul class="nav-submenu">
            <li class="nav-item"><a href="http://example.com/services/other/etc1" class="nav-link">Etc 1</a></li>
            <li class="nav-item"><a href="http://example.com/services/other/etc2" class="nav-link">Etc 2</a></li>
          </ul>
        </li>
      </ul>
    </li>
  </ul>
</nav>
```

### Chaining Flow Explained

```
$nav->add('Services')        → returns Item(Services)   [depth 0]
    ->child('Help')          → returns Item(Help)        [depth 1]
    ->end()                  → returns Item(Services)    [back to depth 0]
    ->child('Other')         → returns Item(Other)       [depth 1]
        ->child('Etc 1')     → returns Item(Etc 1)       [depth 2]
        ->end()              → returns Item(Other)       [back to depth 1]
        ->child('Etc 2')     → returns Item(Etc 2)       [depth 2, no ->end() needed]
```

There is no depth limit. Every `->child()` goes one level deeper. Every `->end()` climbs one level back up.

> **Careful:** `->end()` on a **top-level** item returns the `Builder`, not an `Item`. `Builder` has no `child()` — use `add()` to start the next top-level item. Calling `$nav->add('A', 'home')->end()->child(...)` is a fatal error.

---

## Active State

Nav marks the item matching the current request with `aria-current="page"` and the active class, and gives every ancestor of that item the open class — so accordion menus can open on load.

```php
$nav->add('Home', 'home');
$nav->add('Services', 'services')
        ->child('Help', 'services.help');

echo $nav->render();   // during a request to /services/help
```

```html
<nav class="navbar" aria-label="Main">
  <ul class="nav-menu">
    <li class="nav-item"><a href="http://example.com/" class="nav-link">Home</a></li>
    <li class="nav-item has-children has-active-child"><a href="http://example.com/services" class="nav-link">Services</a>
      <ul class="nav-submenu">
        <li class="nav-item is-active"><a href="http://example.com/services/help" class="nav-link" aria-current="page">Help</a></li>
      </ul>
    </li>
  </ul>
</nav>
```

The current URL comes from `Laika\Service\Url::current()`. Outside a request — CLI, PHPUnit — the relay registry is not set, so Nav degrades quietly and marks nothing active instead of throwing.

**Override it** with `current()`:

```php
$nav->current('/services/help');   // a bare path works
$nav->current(null);               // switch active detection off entirely
```

Both sides are reduced to a path, folded against the install subdirectory, and stripped of a trailing slash before comparison - so a bare `/about`, a resolved `http://example.com/about` and `/subdir/about` all match one another. A URL on a **different host** keeps its host and can never match an internal path, so an external link never lights up by accident.

**Prefix matching** lights up a parent whenever any page beneath it is current:

```php
$nav->configure(['match' => 'prefix']);
// 'services' is active during a request to /services/help
```

The root item is always exact-only — it would otherwise prefix-match every page.

**Force it** per item, overriding detection entirely:

```php
$nav->add('Dashboard', 'dashboard')->active();       // always on
$nav->add('Search',    'search')->active(false);     // never on
```

---

## Conditional Display

Pass `display: false` to hide an item and its entire subtree — useful for permission or role checks.

```php
$nav->add('Admin', 'admin', display: $user->isAdmin())
        ->child('Staff', 'admin.staff')->end()
        ->child('Logs',  'admin.logs');
```

`display` is the **fourth** argument, after the route parameters - use a named argument as above, or pass `[]` for the params: `add('Admin', 'admin', [], $user->isAdmin())`.

If `display: false`, the item is silently skipped. No `if` blocks needed around your nav-building code.

> **Note:** `->child()` is always returned even when `display: false`, so the chain never breaks regardless of display state. The hidden item is built as normal — it is simply never attached to its parent, so the whole subtree falls out of the tree.

---

## Item Attributes

Every setter on `Item` returns the item, so they chain freely.

```php
$nav->add('Home', 'home')
        ->icon('fa fa-home')
        ->addClass('featured')
        ->setId('home-link')
        ->attr('data-turbo', 'false');

$nav->add('Docs', 'https://example.com/docs')->target('_blank');
// → <a href="..." class="nav-link" target="_blank" rel="noopener noreferrer">
```

- `addClass()` adds to the `<li>`; everything else lands on the `<a>`.
- `target('_blank')` adds `rel="noopener noreferrer"` automatically, unless you called `rel()` yourself.
- `icon()` renders `<i class="…" aria-hidden="true"></i>` before the title. Change the tag with the `icon_tag` config key.
- `attr()` rejects `href` and `class` — those belong to the item URL and `addClass()` — and rejects any name that is not a valid HTML attribute, throwing `InvalidArgumentException`.

---

## Styling

Every tag and class in the output is a config key. Pass overrides to `configure()`:

| Key | Default | Applies to |
|-----|---------|-----------|
| `tag` | `nav` | Wrapper element |
| `id` | `null` | Wrapper `id` — omitted entirely unless set |
| `class` | `navbar` | Wrapper class |
| `label` | `Main` | Wrapper `aria-label` (only when `tag` is `nav`) |
| `menu_class` | `nav-menu` | Top-level `<ul>` |
| `submenu_class` | `nav-submenu` | Nested `<ul>` |
| `item_class` | `nav-item` | Every `<li>` |
| `link_class` | `nav-link` | Every `<a>` |
| `active_class` | `is-active` | `<li>` of the current page |
| `open_class` | `has-active-child` | `<li>` of any ancestor of the current page |
| `parent_class` | `has-children` | `<li>` that has a submenu |
| `icon_tag` | `i` | Icon element |
| `match` | `exact` | `exact` or `prefix` |

Set any of them to an empty string to drop the attribute completely.

**Bootstrap 5:**

```php
$nav->configure([
    'class'        => 'navbar navbar-expand-lg',
    'menu_class'   => 'navbar-nav',
    'submenu_class'=> 'dropdown-menu',
    'item_class'   => 'nav-item',
    'link_class'   => 'nav-link',
    'active_class' => 'active',
    'parent_class' => 'dropdown',
]);
```

```html
<nav class="navbar navbar-expand-lg" aria-label="Main">
  <ul class="navbar-nav">
    <li class="nav-item"><a href="http://example.com/" class="nav-link">Home</a></li>
    <li class="nav-item dropdown"><a href="http://example.com/services" class="nav-link">Services</a>
      <ul class="dropdown-menu">
        <li class="nav-item"><a href="http://example.com/services/help" class="nav-link">Help</a></li>
      </ul>
    </li>
  </ul>
</nav>
```

**Tailwind:**

```php
$nav->configure([
    'class'        => 'flex items-center gap-6',
    'menu_class'   => 'flex gap-4',
    'submenu_class'=> 'absolute hidden group-hover:block bg-white shadow rounded',
    'item_class'   => 'relative group',
    'link_class'   => 'px-3 py-2 text-slate-700 hover:text-slate-900',
    'active_class' => 'font-semibold text-slate-900',
]);
```

---

## Using the Relay

`nav` is registered as a **singleton**, so `Laika\Service\Nav` accumulates into one shared Builder for the whole request — which is what you want for a site-wide menu:

```php
use Laika\Service\Nav;

Nav::add('Home', 'home');
Nav::add('About', 'about');

echo Nav::render();
```

`Nav::add()` returns a real `Item`, so `->child()` and `->end()` chain off it as usual. To start over — a second independent menu, or a test — call `Nav::flush()`, or build a `new Builder()` directly.

---

## Accessing Raw Items

Use `items()` to get the array of `Item` objects for custom rendering or testing:

```php
foreach ($nav->items() as $item) {
    echo $item->getTitle();  // e.g. "Home"
    echo $item->getUrl();    // e.g. "http://example.com/"

    if ($item->hasChildren()) {
        foreach ($item->getChildren() as $child) {
            echo $child->getTitle();
        }
    }
}
```

---

## API Reference

### `Builder`

| Method | Returns | Description |
|--------|---------|-------------|
| `add(string $title, string $named, array $namedParams = [], bool $display = true)` | `Item` | Add a top-level nav item from a named route |
| `configure(array $config)` | `static` | Override any renderer config key |
| `current(?string $url)` | `static` | Set the URL used for active detection; `null` disables it |
| `render(string $class = 'navbar')` | `string` | Render nav as HTML |
| `items()` | `Item[]` | Get raw item objects |
| `flush()` | `static` | Drop every item and start over |

> `configure(['class' => …])` takes precedence over the `render()` argument.

### `Item`

| Method | Returns | Description |
|--------|---------|-------------|
| `child(string $title, string $named, array $namedParams = [], bool $display = true)` | `Item` | Add a child under this item (returns the new child) |
| `end()` | `Item\|Builder` | Go back up to the parent node |
| `addClass(string ...$classes)` | `static` | Add CSS classes to the `<li>` |
| `setId(string $id)` | `static` | Set the `<a>` id |
| `attr(string $name, string $value)` | `static` | Set any `<a>` attribute |
| `target(string $target)` | `static` | Set the link target (`_blank` also sets `rel`) |
| `rel(string $rel)` | `static` | Set the link `rel` |
| `icon(string $icon)` | `static` | Icon CSS class rendered before the title |
| `active(bool $state = true)` | `static` | Force the active state |
| `getTitle()` | `string` | Get item title |
| `getUrl()` | `string` | Get the resolved item URL |
| `getChildren()` | `Item[]` | Get child items |
| `hasChildren()` | `bool` | Check if item has children |
| `getParent()` | `Item\|Builder` | Get the node this item hangs under |
| `getClasses()` | `string[]` | Get the extra `<li>` classes |
| `getAttributes()` | `array<string,string>` | Get the extra `<a>` attributes |
| `getIcon()` | `?string` | Get the icon class |
| `getActive()` | `?bool` | Forced state, or `null` when detection applies |

---

## Security

Every value that reaches the output — titles, URLs, attribute names and values, icon classes, and the wrapper tag, id and class — is escaped with `htmlspecialchars()` (`ENT_QUOTES`, UTF-8) by `Renderer`. No other class in Nav emits markup.

Attribute names are additionally validated against `/^[A-Za-z_:][A-Za-z0-9_.:-]*$/` at the point they are set, so a malformed name fails loudly at build time rather than silently producing broken HTML.

---

## Upgrading to 4.6

The rendered markup changed shape. If you styled the old output, update your CSS:

- The wrapper is now `<nav class="navbar" aria-label="Main">`, not `<div id="navbar" class="navbar">`. The `id` is gone unless you set one via `configure(['id' => …])` — the old behaviour duplicated `id="navbar"` whenever two navs rendered at the default.
- `<ul>`, `<li>` and `<a>` now carry default classes (`nav-menu` / `nav-submenu`, `nav-item`, `nav-link`). Set the matching config keys to `''` to get the old bare markup back.
- `Item::end()` now declares `Node` natively (documented as `Item|Builder`). Nothing changes at the call site.
- **`add()` and `child()` take a route name, not a URL**, and gained `$namedParams` in third position — `add('Admin', '/admin', false)` becomes `add('Admin', 'admin', [], false)`. Literal paths no longer work; register the route and use its name, or pass a full absolute URL.
- Item URLs are now base-absolute (`http://host/subdir/services`) rather than root-relative. Anything comparing `getUrl()` against a raw path needs updating.
- Active detection reads `Url::current()` instead of `Url::path()`. `path()` strips both the leading slash and the install subdirectory, so it could never match a resolved item URL — under a subdirectory install nothing was ever marked active.
- `Node::build()` was removed; rendering moved to `Helper\Renderer`. Only relevant if you extended `Node` yourself.

Everything else is additive.

---

## License

MIT — see [LICENSE](../../LICENSE) for details.
