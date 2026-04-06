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

use Laika\Core\Nav\Helper\Node;
use Laika\Core\Nav\Helper\Item;

class Builder extends Node
{
    /**
     * Add a Top-Level Nav Item
     * @param string $title Item Title
     * @param string $url Item URL
     * @param bool $display Set false to Hide (e.g. permission check)
     * @return Item Returns the NEW item — chain ->child() to add children
     */
    public function add(string $title, string $url, bool $display = true): Item
    {
        return $this->createItem($title, $url, $display);
    }

    /**
     * Render the Nav as HTML
     * @param string $class  Wrapping div class/id. Default is 'navbar'
     * @return string
     */
    public function render(string $class = 'navbar'): string
    {
        return "<div id=\"{$class}\" class=\"{$class}\">{$this->build($this->items)}</div>";
    }

    /**
     * Get Raw Item Objects
     * @return Item[]
     */
    public function items(): array
    {
        return $this->items;
    }
}
