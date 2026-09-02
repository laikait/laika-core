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

use InvalidArgumentException;
use Laika\Core\Nav\Builder;

class Item extends Node
{
    /** Attribute Names the Renderer Owns. Set These Through Their Own Methods. */
    private const RESERVED = ['href', 'class'];

    /** A Valid HTML Attribute Name. */
    private const ATTRIBUTE = '/^[A-Za-z_:][A-Za-z0-9_.:-]*$/';

    /** Markup That Opens With an <svg> Element. */
    private const SVG = '/^<svg[\s\/>]/i';

    /** @var string[] Extra Classes for the <li> */
    private array $classes = [];

    /** @var array<string,string> Extra Attributes for the <a> */
    private array $attributes = [];

    /** @var string|null Icon CSS Class Rendered Before the Title */
    private ?string $icon = null;

    /** @var string|null Inline SVG Markup Rendered Before the Title */
    private ?string $svg = null;

    /** @var bool|null Forced Active State. Null Means Detect From the URL. */
    private ?bool $active = null;

    /** @var string|null Stable Lookup Key. Null Means the Item Cannot Be Targeted. */
    private ?string $name = null;

    /**
     * @internal Items are Only Legitimate Through Builder::add() or Item::child().
     */
    public function __construct(protected string $title, protected string $url, protected Node $parent
    ) {}

    public function getTitle(): string        { return $this->title; }
    public function getUrl(): string          { return $this->url; }
    public function getChildren(): array      { return $this->items; }
    public function hasChildren(): bool       { return !empty($this->items); }
    public function getClasses(): array       { return $this->classes; }
    public function getAttributes(): array    { return $this->attributes; }
    public function getIcon(): ?string        { return $this->icon; }
    public function getSvg(): ?string         { return $this->svg; }
    public function getActive(): ?bool        { return $this->active; }
    public function getName(): ?string        { return $this->name; }

    /**
     * Get the Node This Item Hangs Under
     * @return Item|Builder
     */
    public function getParent(): Node
    {
        return $this->parent;
    }

    /**
     * Add a Child Item Under This Item
     * @param string $title Child Title
     * @param string $named Named Route. An Absolute URL, mailto:/tel: Target or #fragment is Used As-Is.
     * @param array $namedParams Named Route Parameters
     * @param bool $display Set false to Hide (e.g. permission check)
     * @throws \RuntimeException When the Route Name is Not Registered
     * @return Item Returns the NEW child - chain ->child() to go deeper
     */
    public function child(string $title, string $named, array $namedParams = [], bool $display = true): Item
    {
        return $this->createItem($title, $named, $namedParams, $display);
    }

    /**
     * Go Back Up to the Parent Node
     * A Top-Level Item Returns the Builder, Which Has No child() - Use add().
     * @return Item|Builder
     */
    public function end(): Node
    {
        return $this->parent;
    }

    /**
     * Give the Item a Stable Lookup Key
     * This is What Builder::find() and Builder::extend() Target, so it Should
     * Outlive the Title - Which Gets Translated and Reworded.
     * @param string $name e.g. 'services'
     * @throws InvalidArgumentException When the Name is Blank
     * @return static
     */
    public function name(string $name): static
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Item Name Cannot Be Blank.');
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Find a Named Item Below This One
     * Scopes a Lookup to This Subtree - Builder::find() Searches the Whole Tree.
     * @param string $name Name Set Through name()
     * @return Item|null
     */
    public function find(string $name): ?Item
    {
        return $this->findNamed($name);
    }

    /**
     * Add One or More CSS Classes to the <li>
     * @param string ...$classes
     * @return static
     */
    public function addClass(string ...$classes): static
    {
        foreach ($classes as $class) {
            foreach (preg_split('/\s+/', trim($class), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $single) {
                $this->classes[] = $single;
            }
        }
        $this->classes = array_values(array_unique($this->classes));
        return $this;
    }

    /**
     * Set the id Attribute on the <a>
     * @param string $id
     * @return static
     */
    public function setId(string $id): static
    {
        return $this->attr('id', $id);
    }

    /**
     * Set Any Attribute on the <a> - data-*, aria-*, title, and so on
     * @param string $name
     * @param string $value
     * @throws InvalidArgumentException
     * @return static
     */
    public function attr(string $name, string $value): static
    {
        $name = strtolower(trim($name));

        if (!preg_match(self::ATTRIBUTE, $name)) {
            throw new InvalidArgumentException("Invalid Attribute Name [{$name}].");
        }

        if (in_array($name, self::RESERVED, true)) {
            throw new InvalidArgumentException("Attribute [{$name}] is Managed by the Renderer. Use the Item URL or addClass() Instead.");
        }

        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set the Link Target
     * _blank Also Gets rel="noopener noreferrer" Unless rel() Was Already Called.
     * @param string $target
     * @return static
     */
    public function target(string $target): static
    {
        $this->attr('target', $target);

        if ($target === '_blank' && !isset($this->attributes['rel'])) {
            $this->attr('rel', 'noopener noreferrer');
        }

        return $this;
    }

    /**
     * Set the Link rel
     * @param string $rel
     * @return static
     */
    public function rel(string $rel): static
    {
        return $this->attr('rel', $rel);
    }

    /**
     * Set an Icon CSS Class Rendered Before the Title
     * @param string $icon e.g. 'fa fa-home'
     * @return static
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set Inline SVG Markup Rendered Before the Title
     * Fills the Same Slot as icon() and Wins When Both Are Set.
     * The Markup is Emitted Verbatim, Unescaped - it is Author-Supplied and
     * Trusted the Same Way a Template Partial is. Never Pass User Input Here.
     * Mark a Decorative Icon aria-hidden="true" Yourself: the Renderer Emits
     * What You Give it and Rewrites Nothing.
     * @param string $svg e.g. '<svg viewBox="0 0 24 24" aria-hidden="true">...</svg>'
     * @throws InvalidArgumentException When the Markup is Not an <svg> Element
     * @return static
     */
    public function svg(string $svg): static
    {
        $svg = trim($svg);

        // Also Catches the Likely Slip of Passing a CSS Class Here - That is icon().
        if (!preg_match(self::SVG, $svg)) {
            throw new InvalidArgumentException('Item SVG Must Be an <svg> Element. Use icon() for a CSS Class.');
        }

        $this->svg = $svg;
        return $this;
    }

    /**
     * Force the Active State, Overriding URL Detection
     * @param bool $state
     * @return static
     */
    public function active(bool $state = true): static
    {
        $this->active = $state;
        return $this;
    }
}
