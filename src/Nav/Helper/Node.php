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

use Laika\Route\Handler;
use Laika\Service\Url;

abstract class Node
{
    /** @var Item[] */
    protected array $items = [];

    /**
     * Create and Register a Item Into This Node
     * A Hidden Item is Still Created and Returned - it is Simply Never
     * Registered, so its Entire Subtree Falls Out of the Tree While the
     * Caller's Chain Keeps Working.
     * @param string $title Item Title
     * @param string $named Named Route, or an Already-Final URL
     * @param array $params Named Route Parameters
     * @param bool $display Set false to Hide
     * @return Item
     */
    protected function createItem(string $title, string $named, array $params, bool $display): Item
    {
        $item = new Item($title, $this->resolveUrl($named, $params), $this);
        if ($display) {
            $this->items[] = $item;
        }
        return $item;
    }

    /**
     * Turn a Named Route Into a URL
     * Mirrors the named() Helper, but Reaches Handler and Url Directly - a
     * src/ Class Cannot Assume helpers/functions Has Been Loaded, Since Those
     * Arrive Lazily Through the Resource System at App Boot.
     * @param string $named Named Route, or an Already-Final URL
     * @param array $params Named Route Parameters
     * @throws \RuntimeException When the Route Name is Not Registered
     * @return string
     */
    protected function resolveUrl(string $named, array $params): string
    {
        // Already-Final Targets Pass Straight Through: Absolute and
        // Protocol-Relative URLs, Other Schemes (mailto:, tel:) and Fragments.
        // A Malformed URL Gives false Here, Which Also Passes Through - Better
        // a Suspect Link Than a Route Lookup That Cannot Possibly Succeed.
        if (
            parse_url($named, PHP_URL_HOST) !== null
            || parse_url($named, PHP_URL_SCHEME) !== null
            || str_starts_with($named, '#')
        ) {
            return $named;
        }

        $name  = parse_url($named, PHP_URL_PATH) ?: '';
        $query = parse_url($named, PHP_URL_QUERY);

        $path = trim(Handler::namedUrl(trim($name, '/'), $params), '/');

        if (is_string($query) && $query !== '') {
            $path .= '?' . $query;
        }

        return Url::base() . $path;
    }
}
