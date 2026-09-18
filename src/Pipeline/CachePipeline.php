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

namespace Laika\Core\Pipeline;

use Laika\Route\Contracts\PipelineInterface;
use Laika\Service\CSRF;
use Laika\Service\Cache;
use Laika\Service\Response;
use Throwable;

/**
 * Cache a Route's Response
 *
 * Opt-in per route, and it must be the route's LAST pipeline:
 *
 *     Url::get('/pricing', 'PageController@pricing')
 *         ->pipeline(\Laika\Core\Pipeline\CachePipeline::class . '|cache_ttl=300');
 *
 * Last, because pipelines run in order and a hit returns without calling the
 * rest. Anything after this one -- an auth check, an IP allowlist, a rate
 * limit -- would be skipped for every cached request. Registered as a global
 * pipeline it would run before every route's own authorization, so it refuses
 * to work there: see handle().
 *
 * What is cached is the string the controller returned, plus the status,
 * content type and headers it set. It is captured before Dispatcher renders
 * it, so the hidden _csrf inputs Html::render() adds to forms are added fresh
 * on every hit. A token rendered into the body by the controller itself --
 * lf_header()'s `const TOKEN` -- is not, which is why a response that issued
 * one is never stored.
 *
 * A response is stored only when all of these hold. Each one exists because
 * without it one visitor's response could be served to another:
 *
 * - GET, with no Authorization header and no session cookie on the request
 * - status 200, and a non-empty body
 * - no CSRF token generated while rendering (single-use and bound to the user agent)
 * - no session started while rendering, and no Set-Cookie header sent
 *
 * The key covers the host, path, query string, and the Accept and
 * X-Requested-With headers a controller may vary its output on. Entries expire
 * on their TTL; there is no write-driven invalidation, so choose a TTL you can
 * live with being stale for, and run `php laika cache:clear --data` after a
 * deploy that changes these pages.
 */
class CachePipeline implements PipelineInterface
{
    /** @var string Parameter name for the TTL, namespaced so it cannot collide with a controller argument */
    public const TTL_PARAM = 'cache_ttl';

    /** @var int Seconds, when the route gives none */
    private const DEFAULT_TTL = 300;

    /** @var string[] Headers never replayed from the cache */
    private const UNSAFE_HEADERS = ['set-cookie', 'x-laika-cache'];

    public function handle(callable $next, array &$params): ?string
    {
        $ttl = $this->ttl($params);

        // Never passed on: pipeline arguments are merged into the controller's
        unset($params[self::TTL_PARAM]);

        if (!$this->cacheableRequest() || $this->isGlobal()) {
            return $next();
        }

        $key = $this->key();

        try {
            $hit = Cache::get($key);
        } catch (Throwable) {
            // A broken cache is a miss, never a failed request
            return $next();
        }

        if (is_array($hit) && isset($hit['body'], $hit['status'], $hit['type'], $hit['headers'])) {
            Response::setStatus((int) $hit['status']);
            Response::setContentType((string) $hit['type']);
            Response::setHeaders((array) $hit['headers']);
            Response::setHeader('X-Laika-Cache', 'HIT');

            return (string) $hit['body'];
        }

        $issued = CSRF::issued();
        $sessionBefore = session_status() === PHP_SESSION_ACTIVE;

        $body = $next();

        Response::setHeader('X-Laika-Cache', 'MISS');

        if ($this->storable($body, $issued, $sessionBefore)) {
            try {
                Cache::set($key, [
                    'body'    => $body,
                    'status'  => Response::getStatus(),
                    'type'    => Response::getContentType(),
                    'headers' => $this->replayableHeaders(),
                ], $ttl);
            } catch (Throwable) {
                // Not caching is always a safe outcome
            }
        }

        return $body;
    }

    /*=============================== INTERNAL ===============================*/

    /**
     * @return bool
     */
    protected function cacheableRequest(): bool
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }

        // Credentials mean the response may be specific to whoever sent them
        if (!empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return false;
        }

        // A session cookie means a visitor who may be signed in. Serving them
        // the anonymous page is wrong; storing their page is a leak.
        return !isset($_COOKIE[$this->sessionName()]);
    }

    /**
     * Whether This Instance Was Registered as a Global Pipeline
     *
     * Global pipelines run before a route's own, so a hit would bypass every
     * route-level authorization check. Rather than document that and hope, it
     * declines to cache at all there.
     *
     * @return bool
     */
    protected function isGlobal(): bool
    {
        if (!class_exists(\Laika\Route\Handler::class)) {
            return false;
        }

        foreach (\Laika\Route\Handler::getGlobalPipelines() as $entry) {
            $name = ltrim(explode('|', (string) $entry, 2)[0], '\\');

            if (strcasecmp($name, static::class) === 0 || strcasecmp($name, self::class) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ?string $body
     * @param int $issuedBefore CSRF tokens generated before rendering
     * @param bool $sessionBefore Whether a session was already active
     * @return bool
     */
    protected function storable(?string $body, int $issuedBefore, bool $sessionBefore): bool
    {
        if ($body === null || $body === '') {
            return false;
        }

        if (Response::getStatus() !== 200) {
            return false;
        }

        // A single-use token bound to this visitor's user agent is in the body
        if (CSRF::issued() !== $issuedBefore) {
            return false;
        }

        // The controller read or wrote the session, so the output may be personal
        if (!$sessionBefore && session_status() === PHP_SESSION_ACTIVE) {
            return false;
        }

        foreach (headers_list() as $header) {
            if (stripos($header, 'set-cookie:') === 0) {
                return false;
            }
        }

        foreach (array_keys(Response::getHeaders()) as $name) {
            if (strtolower((string) $name) === 'set-cookie') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string,string>
     */
    protected function replayableHeaders(): array
    {
        $headers = [];

        foreach (Response::getHeaders() as $name => $value) {
            if (!in_array(strtolower((string) $name), self::UNSAFE_HEADERS, true)) {
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @return string
     */
    protected function key(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');

        // Sorted, so ?a=1&b=2 and ?b=2&a=1 share an entry
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $query);
        ksort($query);

        return 'response:' . sha1(serialize([
            strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')),
            $path,
            $query,
            (string) ($_SERVER['HTTP_ACCEPT'] ?? ''),
            (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''),
        ]));
    }

    /**
     * @param array $params
     * @return int
     */
    protected function ttl(array $params): int
    {
        $ttl = $params[self::TTL_PARAM] ?? null;

        return is_numeric($ttl) && (int) $ttl > 0 ? (int) $ttl : self::DEFAULT_TTL;
    }

    /**
     * @return string
     */
    protected function sessionName(): string
    {
        if (class_exists(\Laika\Session\SessionConfig::class)) {
            try {
                $name = \Laika\Session\SessionConfig::options()['name'] ?? null;

                if (is_string($name) && $name !== '') {
                    return $name;
                }
            } catch (Throwable) {
            }
        }

        return session_name() ?: 'PHPSESSID';
    }
}
