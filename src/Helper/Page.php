<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Laika\Core\Helper;

class Page
{
    /**
     * Total Results
     * @var int $totalResults
     */
    private int $totalElements;

    /**
     * Total Pages
     * @var int $totalPages
     */
    private int $totalPages;

    ##################################################################
    /* ------------------------ PUBLIC API ------------------------ */
    ##################################################################
    /**
     * Total Elements
     * @param int $totalElements Total Elements
     * @param int|string $limit Data Limits to Show. Default is 20
     */
    public function __construct(int $totalElements = 0, int|string $limit = 20)
    {
        $this->totalElements = $totalElements < 1  ? 1 : $totalElements;
        $this->totalPages = (int) \ceil($this->totalElements / (int) $limit);
    }

    /**
     * Total Pages Exists
     * @return int
     */
    public function totalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Total Pages Results
     * @param int $totalResults
     * @return int
     */
    public function totalElements(): int
    {
        return $this->totalElements;
    }

    /**
     * Next Page Url
     * @return string
     */
    public function next(): string
    {
        return \call_user_func([new Url, 'incrementQuery']);
    }

    /**
     * Previous Page Url
     * @return string
     */
    public function previous()
    {
        return \call_user_func([new Url, 'decrementQuery']);
    }
}
