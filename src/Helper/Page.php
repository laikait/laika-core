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

namespace Laika\Core\Helper;

use Laika\Service\{Url, Request};

class Page
{
    /**
     * Current Page Url
     * @param ?string $key Request Key. Page Key Query String. Example: 'page'
     * @return int
     */
    public function number(?string $key = null): int
    {
        // Url::incrementQuery() lowercases the key, so this has to agree or
        // next() writes a query key that number() never reads back.
        $key = strtolower($key ?: 'page');

        return max(1, (int) Request::input($key, 1));
    }

    /**
     * Next Page Url
     * @param ?string $key Request Key. Page Key Query String. Example: 'page'
     * @return string
     */
    public function next(?string $key = null): string
    {
        return Url::incrementQuery($key);
    }

    /**
     * Previous Page Url
     * @param ?string $key Request Key. Page Key Query String. Example: 'page'
     * @return string
     */
    public function previous(?string $key = null): string
    {
        return Url::decrementQuery($key);
    }
}
