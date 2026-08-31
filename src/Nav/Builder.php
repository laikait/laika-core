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

namespace Laika\Core\Nav;

use Throwable;
use Laika\Service\Url;
use Laika\Core\Nav\Helper\{Node, Item, Renderer};

class Builder extends Node
{
    /** @var array<string,string|null> Renderer Overrides */
    private array $config = [];

    /** @var string|null Explicit Active URL. Null Means Resolve From the Request. */
    private ?string $current = null;

    /** @var bool Was current() Called? Distinguishes "Auto" From "Deliberately Off". */
    private bool $currentSet = false;

    /**
     * Add a Top-Level Nav Item
     * @param string $title Item Title
     * @param string $named Named Route. An Absolute URL, mailto:/tel: Target or #fragment is Used As-Is.
     * @param array $namedParams Named Route Parameters
     * @param bool $display Set false to Hide (e.g. permission check)
     * @throws \RuntimeException When the Route Name is Not Registered
     * @return Item Returns the NEW item - chain ->child() to add children
     */
    public function add(string $title, string $named, array $namedParams = [], bool $display = true): Item
    {
        return $this->createItem($title, $named, $namedParams, $display);
    }

    /**
     * Override Renderer Config
     * Accepts any key from Renderer::DEFAULTS - tag, id, class, label,
     * menu_class, submenu_class, item_class, link_class, active_class,
     * open_class, parent_class, icon_tag, match.
     * @param array<string,string|null> $config
     * @return static
     */
    public function configure(array $config): static
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Set the URL Used for Active Detection
     * Pass null to Switch Active Detection Off Entirely.
     * @param string|null $url
     * @return static
     */
    public function current(?string $url): static
    {
        $this->current    = $url;
        $this->currentSet = true;
        return $this;
    }

    /**
     * Render the Nav as HTML
     * @param string $class Wrapper class. Default is 'navbar'
     * @return string
     */
    public function render(string $class = 'navbar'): string
    {
        $config = array_merge(['class' => $class], $this->config);

        return (new Renderer($config, $this->resolveCurrent(), $this->resolveBase()))->render($this->items);
    }

    /**
     * Get Raw Item Objects
     * @return Item[]
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Drop Every Item and Start Over
     * The 'nav' Relay is a Registry Singleton, so the Same Builder Lives for
     * the Whole Request - This is How a Second Nav (or a Test) Gets a Clean One.
     * @return static
     */
    public function flush(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * Work Out Which URL Counts as Current
     * Url::current() Rather Than Url::path(): Item URLs Are Base-Absolute, and
     * path() Strips Both the Leading Slash and the Install Subdirectory, so it
     * Could Never Match Them.
     * Falls Back to the Request URL, but Nav Must Still Render Under PHPUnit
     * and the CLI Where the Relay Registry Was Never Set - There, Nothing is
     * Simply Marked Active.
     * @return string|null
     */
    private function resolveCurrent(): ?string
    {
        if ($this->currentSet) {
            return $this->current;
        }

        try {
            return Url::current();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The Install Base URL, So the Renderer Can Fold Away the Subdirectory
     * Guarded Like resolveCurrent() - Rendering Must Survive a Missing Registry.
     * @return string|null
     */
    private function resolveBase(): ?string
    {
        try {
            return Url::base();
        } catch (Throwable) {
            return null;
        }
    }
}
