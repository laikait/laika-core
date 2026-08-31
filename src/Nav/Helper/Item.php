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

    /** @var string[] Extra Classes for the <li> */
    private array $classes = [];

    /** @var array<string,string> Extra Attributes for the <a> */
    private array $attributes = [];

    /** @var string|null Icon CSS Class Rendered Before the Title */
    private ?string $icon = null;

    /** @var bool|null Forced Active State. Null Means Detect From the URL. */
    private ?bool $active = null;

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
    public function getActive(): ?bool        { return $this->active; }

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
