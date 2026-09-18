<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Template;

use Laika\Service\CSRF;
use Laika\Service\Cache;
use Throwable;

/**
 * Storage Behind The {% cache %} Twig Tag
 *
 * Compiled templates call these statics directly, so they have to be cheap,
 * need no instance, and never throw: a cache that fails renders the fragment
 * as if it were not cached, rather than failing the page.
 */
final class FragmentCache
{
    /** @var string Key namespace, apart from query and response entries */
    private const PREFIX = 'fragment:';

    /**
     * @param string $key As written in the template
     * @return ?string The cached HTML, or null on a miss
     */
    public static function get(string $key): ?string
    {
        try {
            $html = Cache::get(self::PREFIX . $key);
        } catch (Throwable) {
            return null;
        }

        return is_string($html) ? $html : null;
    }

    /**
     * Tokens Issued So Far, Read Before The Fragment Renders
     * @return int
     */
    public static function issued(): int
    {
        try {
            return CSRF::issued();
        } catch (Throwable) {
            return -1;
        }
    }

    /**
     * Store a Rendered Fragment
     *
     * Skipped when rendering it generated a CSRF token -- lf_header() or a
     * form built by hand. That token is single-use and bound to this visitor's
     * user agent; stored, it would be handed to everyone else.
     *
     * @param string $key As written in the template
     * @param string $html
     * @param mixed $ttl Seconds from the template; null uses the cache's default
     * @param int $issuedBefore issued() before the fragment rendered
     * @return void
     */
    public static function put(string $key, string $html, mixed $ttl, int $issuedBefore): void
    {
        if ($issuedBefore < 0 || self::issued() !== $issuedBefore) {
            return;
        }

        try {
            Cache::set(self::PREFIX . $key, $html, is_numeric($ttl) ? max(0, (int) $ttl) : null);
        } catch (Throwable) {
            // Not caching is always a safe outcome
        }
    }
}
