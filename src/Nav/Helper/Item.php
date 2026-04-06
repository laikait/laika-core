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

use Laika\Core\Nav\Builder;

class Item extends Node
{
    public function __construct(protected string $title, protected string $url, protected Node $parent
    ) {}

    public function getTitle(): string   { return $this->title; }
    public function getUrl(): string     { return $this->url; }
    public function getChildren(): array { return $this->items; }
    public function hasChildren(): bool  { return !empty($this->items); }

    /**
     * Add a Child Item Under This Item
     * @param string $title Child Title
     * @param string $url Child URL
     * @param bool $display Set false to Hide (e.g. permission check)
     * @return Item Returns the NEW child — chain ->child() to go deeper
     */
    public function child(string $title, string $url, bool $display = true): Item
    {
        return $this->createItem($title, $url, $display);
    }

    /**
     * Go Back Up to the Parent Node
     * @return Item|Builder
     */
    public function end(): Item|Builder
    {
        return $this->parent;
    }
}
