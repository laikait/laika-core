# Laika Nav

A fluent, recursive navigation builder for the [Laika PHP MVC Framework](https://github.com/ShowketAhmed/laika-framework).  
Build deeply nested navigation trees with a clean chainable API and render them as HTML.

---

## Namespace Structure

```
Laika\Core\Nav\Builder          — Root entry point
Laika\Core\Nav\Helper\Node      — Abstract base (shared item list + recursive build)
Laika\Core\Nav\Helper\Item      — Chainable nav item (child / end)
```

---

## Installation

The Nav package ships as part of `laika/laika-framework`. No separate installation required.

---

## Basic Usage

```php
use Laika\Core\Nav\Builder;

$nav = new Builder();

$nav->add('Home', '/');
$nav->add('About', '/about');

echo $nav->render();
```

**Output:**
```html
<div id="navbar" class="navbar">
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about">About</a></li>
    </ul>
</div>
```

---

## Adding Children

Use `->child()` to go deeper, and `->end()` to climb back up to the parent.

```php
$nav = new Builder();

$nav->add('Home', '/');
$nav->add('About', '/about');
$nav->add('Services', '/services')
        ->child('Help', '/services/help')->end()
        ->child('Other', '/services/other')
            ->child('Etc 1', '/services/other/etc1')->end()
            ->child('Etc 2', '/services/other/etc2')->end()
            ->child('Etc 3', '/services/other/etc3');

echo $nav->render();
```

**Output:**
```html
<ul>
    <li><a href="/">Home</a></li>
    <li><a href="/about">About</a></li>
    <li><a href="/services">Services</a>
        <ul>
            <li><a href="/services/help">Help</a></li>
            <li><a href="/services/other">Other</a>
                <ul>
                    <li><a href="/services/other/etc1">Etc 1</a></li>
                    <li><a href="/services/other/etc2">Etc 2</a></li>
                    <li><a href="/services/other/etc3">Etc 3</a></li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
```

---

## Chaining Flow Explained

```
$nav->add('Services')        → returns Item(Services)   [depth 0]
    ->child('Help')          → returns Item(Help)        [depth 1]
    ->end()                  → returns Item(Services)    [back to depth 0]
    ->child('Other')         → returns Item(Other)       [depth 1]
        ->child('Etc 1')     → returns Item(Etc 1)       [depth 2]
        ->end()              → returns Item(Other)       [back to depth 1]
        ->child('Etc 2')     → returns Item(Etc 2)       [depth 2]
        ->end()              → returns Item(Other)
        ->child('Etc 3')     → returns Item(Etc 3)       [depth 2, no ->end() needed]
```

There is no depth limit. Every `->child()` goes one level deeper. Every `->end()` climbs one level back up.

---

## Conditional Display

Pass `display: false` to hide an item and its entire subtree — useful for permission or role checks.

```php
$nav->add('Admin', '/admin', display: $user->isAdmin())
        ->child('Staff',  '/admin/staff')->end()
        ->child('Logs',   '/admin/logs');
```

If `display: false`, the item is silently skipped. No `if` blocks needed around your nav-building code.

> **Note:** `->child()` is always returned even when `display: false`, so the chain never breaks regardless of display state.

---

## Custom Wrapper Class

By default `render()` wraps the nav in `<div id="navbar" class="navbar">`.  
Pass a custom class name to change both the `id` and `class`:

```php
echo $nav->render('main-nav');
// <div id="main-nav" class="main-nav">...</div>
```

---

## Accessing Raw Items

Use `items()` to get the array of `Item` objects for custom rendering or testing:

```php
$items = $nav->items(); // Item[]

foreach ($items as $item) {
    echo $item->getTitle();  // e.g. "Home"
    echo $item->getUrl();    // e.g. "/"

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
| `add(string $title, string $url, bool $display = true)` | `Item` | Add a top-level nav item |
| `render(string $class = 'navbar')` | `string` | Render nav as HTML |
| `items()` | `Item[]` | Get raw item objects |

### `Item`

| Method | Returns | Description |
|--------|---------|-------------|
| `child(string $title, string $url, bool $display = true)` | `Item` | Add a child under this item (returns the new child) |
| `end()` | `Item\|Builder` | Go back up to the parent node |
| `getTitle()` | `string` | Get item title |
| `getUrl()` | `string` | Get item URL |
| `getChildren()` | `Item[]` | Get child items |
| `hasChildren()` | `bool` | Check if item has children |

---

## Security

All titles and URLs are escaped with `htmlspecialchars()` (ENT_QUOTES, UTF-8) before rendering.

---

## License

MIT — see [LICENSE](../../LICENSE) for details.
