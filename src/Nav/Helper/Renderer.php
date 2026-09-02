<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Nav\Helper;

/**
 * Turns a Nav Tree Into HTML.
 * Every Value That Reaches the Output Passes Through esc(), With One Exception:
 * Item::svg() is Author-Supplied Markup and is Emitted Verbatim.
 * This is the Only Class in Nav That Emits Markup.
 */
final class Renderer
{
    /** @var array<string,string|null> Renderer Defaults */
    public const DEFAULTS = [
        'tag'           => 'nav',
        'id'            => null,
        'class'         => 'navbar',
        'label'         => 'Main',
        'menu_class'    => 'nav-menu',
        'submenu_class' => 'nav-submenu',
        'item_class'    => 'nav-item',
        'link_class'    => 'nav-link',
        'active_class'  => 'is-active',
        'open_class'    => 'has-active-child',
        'parent_class'  => 'has-children',
        'icon_tag'      => 'i',
        'match'         => 'exact',
    ];

    /** @var array<string,string|null> */
    private array $config;

    /** @var string Host of the Install Base, Lowercased. Empty When Unknown. */
    private string $baseHost = '';

    /** @var string Path of the Install Base (the Subdirectory). Empty When Unknown. */
    private string $basePath = '';

    /** @var string|null Current Request Path, Already Normalised */
    private ?string $current;

    /**
     * @param array<string,string|null> $config Overrides Merged Over DEFAULTS
     * @param string|null $current Current URL or Path. Null Disables Active Detection.
     * @param string|null $base Install Base URL, Used to Fold Away the Subdirectory.
     */
    public function __construct(array $config = [], ?string $current = null, ?string $base = null)
    {
        $this->config = array_merge(self::DEFAULTS, $config);

        if (is_string($base) && $base !== '') {
            $host = parse_url($base, PHP_URL_HOST);
            $path = parse_url($base, PHP_URL_PATH);

            $this->baseHost = is_string($host) ? strtolower($host) : '';
            $this->basePath = is_string($path) ? $path : '';
        }

        $this->current = $current === null ? null : $this->normalise($current);
    }

    /**
     * Render the Whole Nav, Wrapper Included
     * @param Item[] $items
     * @return string
     */
    public function render(array $items): string
    {
        $tag   = $this->config['tag'] ?: 'nav';
        $attrs = '';

        if (($id = $this->config['id']) !== null && $id !== '') {
            $attrs .= ' id="' . $this->esc((string) $id) . '"';
        }

        if (($class = $this->config['class']) !== null && $class !== '') {
            $attrs .= ' class="' . $this->esc((string) $class) . '"';
        }

        if (($label = $this->config['label']) !== null && $label !== '' && $tag === 'nav') {
            $attrs .= ' aria-label="' . $this->esc((string) $label) . '"';
        }

        $tag = $this->esc($tag);

        return "<{$tag}{$attrs}>" . $this->list($items, 0) . "</{$tag}>";
    }

    /**
     * Build One <ul> Level Recursively
     * @param Item[] $items
     * @param int $depth
     * @return string
     */
    private function list(array $items, int $depth): string
    {
        if (empty($items)) {
            return '';
        }

        $class = $depth === 0 ? $this->config['menu_class'] : $this->config['submenu_class'];
        $html  = '<ul' . $this->classAttribute([(string) $class]) . '>';

        foreach ($items as $item) {
            $html .= $this->item($item, $depth);
        }

        return $html . '</ul>';
    }

    /**
     * Build a Single <li> and its Subtree
     * @param Item $item
     * @param int $depth
     * @return string
     */
    private function item(Item $item, int $depth): string
    {
        $active   = $this->isActive($item);
        $children = $item->getChildren();

        $classes = [(string) $this->config['item_class']];

        if ($children !== []) {
            $classes[] = (string) $this->config['parent_class'];
        }

        if ($active) {
            $classes[] = (string) $this->config['active_class'];
        }

        if ($children !== [] && $this->hasActiveDescendant($item)) {
            $classes[] = (string) $this->config['open_class'];
        }

        $classes = array_merge($classes, $item->getClasses());

        $link  = ' href="' . $this->esc($item->getUrl()) . '"';
        $link .= $this->classAttribute([(string) $this->config['link_class']]);

        if ($active) {
            $link .= ' aria-current="page"';
        }

        $link .= $this->attributes($item->getAttributes());

        $label = '';

        // Inline SVG and an Icon Class Fill the Same Slot, so SVG Wins. It is
        // the One Value That Skips esc() - See Item::svg() for Why That Holds.
        if (($svg = $item->getSvg()) !== null && $svg !== '') {
            $label .= $svg;
        } elseif (($icon = $item->getIcon()) !== null && $icon !== '') {
            $iconTag = $this->esc((string) ($this->config['icon_tag'] ?: 'i'));
            $label  .= '<' . $iconTag . ' class="' . $this->esc($icon) . '" aria-hidden="true"></' . $iconTag . '>';
        }

        $label .= $this->esc($item->getTitle());

        $html  = '<li' . $this->classAttribute($classes) . '>';
        $html .= '<a' . $link . '>' . $label . '</a>';
        $html .= $this->list($children, $depth + 1);

        return $html . '</li>';
    }

    /**
     * Render a class Attribute, or Nothing When Every Class is Blank
     * @param string[] $classes
     * @return string
     */
    private function classAttribute(array $classes): string
    {
        $classes = array_filter($classes, static function (string $class): bool {
            return trim($class) !== '';
        });

        $classes = array_values(array_unique($classes));

        if ($classes === []) {
            return '';
        }

        return ' class="' . $this->esc(implode(' ', $classes)) . '"';
    }

    /**
     * Render Author-Supplied Attributes
     * Names Were Already Validated by Item::attr().
     * @param array<string,string> $attributes
     * @return string
     */
    private function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            $html .= ' ' . $this->esc($name) . '="' . $this->esc($value) . '"';
        }

        return $html;
    }

    /**
     * Decide Whether an Item is the Current One
     * @param Item $item
     * @return bool
     */
    private function isActive(Item $item): bool
    {
        // A Forced State Always Wins.
        if (($forced = $item->getActive()) !== null) {
            return $forced;
        }

        if ($this->current === null) {
            return false;
        }

        $path = $this->normalise($item->getUrl());

        if ($path === $this->current) {
            return true;
        }

        // Prefix Mode Also Lights Up Ancestors of the Current Page. The Root
        // Path Normalises to an Empty String and Would Prefix-Match Every URL,
        // so it Stays Exact-Only.
        if ($this->config['match'] === 'prefix' && $path !== '') {
            return str_starts_with($this->current, $path . '/');
        }

        return false;
    }

    /**
     * Does Any Descendant Resolve to Active?
     * @param Item $item
     * @return bool
     */
    private function hasActiveDescendant(Item $item): bool
    {
        foreach ($item->getChildren() as $child) {
            if ($this->isActive($child) || $this->hasActiveDescendant($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduce a URL to a Comparable Path
     * Named Routes Resolve to Base-Absolute URLs, so the Install Subdirectory
     * is Folded Away Here - Otherwise the Root Item Would Prefix-Match Every
     * Page Under /subdir/, and a Hand-Written '/services' Would Never Equal a
     * Resolved '/subdir/services'.
     * @param string $url
     * @return string
     */
    private function normalise(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        // The prefix must end at a segment boundary, or directory '/app' would
        // eat the front of '/application/list'. Same rule as Url::path().
        if ($this->basePath !== '' && $this->basePath !== '/') {
            $prefix = rtrim($this->basePath, '/');

            if ($path === $prefix) {
                $path = '';
            } elseif (str_starts_with($path, $prefix . '/')) {
                $path = substr($path, strlen($prefix));
            }
        }

        $path = trim($path, '/');
        $path = $path === '' ? '' : '/' . $path;

        // A foreign origin keeps its host, so an external link can never
        // collide with an internal path of the same shape.
        if (is_string($host) && $this->baseHost !== '' && strtolower($host) !== $this->baseHost) {
            return strtolower($host) . $path;
        }

        return $path;
    }

    /**
     * Escape a Value for HTML Output
     * @param string $value
     * @return string
     */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
