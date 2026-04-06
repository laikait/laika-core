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

abstract class Node
{
    /** @var Item[] */
    protected array $items = [];

    /**
     * Create and Register a Item Into This Node
     * @param string $title Item Title
     * @param string $url Item URL
     * @param bool $display Set false to Hide
     * @return Item
     */
    protected function createItem(string $title, string $url, bool $display): Item
    {
        $item = new Item($title, $url, $this);
        if ($display) {
            $this->items[] = $item;
        }
        return $item;
    }

    /**
     * Build HTML List Recursively
     * @param Item[] $items
     * @return string
     */
    protected function build(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<ul>';
        foreach ($items as $item) {
            $title = htmlspecialchars($item->getTitle(), ENT_QUOTES, 'UTF-8');
            $url   = htmlspecialchars($item->getUrl(), ENT_QUOTES, 'UTF-8');

            $html .= '<li>';
            $html .= "<a href=\"{$url}\">{$title}</a>";

            if ($item->hasChildren()) {
                $html .= $this->build($item->getChildren());
            }

            $html .= '</li>';
        }

        return $html . '</ul>';
    }
}
