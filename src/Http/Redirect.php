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

namespace Laika\Core\Http;

use Laika\Service\Url;
use Laika\Session\Session;
use Laika\Core\Exceptions\HttpException;

class Redirect
{
    private const ALLOWED_CODES = [301, 302, 303];

    ##################################################################
    /*------------------------- PUBLIC API -------------------------*/
    ##################################################################

    /**
     * Set Flass Message
     * @param string $message Message to set.
     * @param bool $status
     * @return static
     */
    public function with(string $message, bool $status): static
    {
        Session::set('alert', ['message' => $message, 'status' => $status]);
        return $this;
    }

    /**
     * Redirect Back to The Previous Link
     * @param int $code Response Code. Default is 302
     * @return void
     */
    public function back(int $code = 302): void
    {
        $this->send($this->backTarget($_SERVER['HTTP_REFERER'] ?? null, Url::host()), $code);
    }

    /**
     * Redirect to A Link
     * @param string $to Named/URL to Redirect.
     * @param array $params Named Route Parameters.
     * @param int $code HTTP Status Code. Default is 302.
     * @return void
     */
    public function to(string $to, array $params = [], int $code = 302): void
    {
        $target = parse_url($to, PHP_URL_HOST) ? $to : named($to, $params, true);
        $this->send($target, $code);
    }

    ####################################################################
    /*------------------------- INTERNAL API -------------------------*/
    ####################################################################

    /**
     * Same-Host Path to Return to
     *
     * The referer is client controlled, so it is only followed when it points
     * at this host, and then only as a path. Hosts are compared without the
     * port: HTTP_HOST carries one ("localhost:8000") and parse_url()'s host
     * does not, so comparing the raw header rejected every referer on a
     * non-standard port. Leading slashes and backslashes are collapsed because
     * "Location: //evil.com" and "/\evil.com" both leave the site.
     *
     * @param ?string $referer Raw Referer Header
     * @param string $host This Request's Host, Without Port
     * @return string Local Path (& Query), or '/' When The Referer Can't be Trusted
     */
    protected function backTarget(?string $referer, string $host): string
    {
        if ($referer === null || $referer === '' || strpbrk($referer, "\r\n") !== false) {
            return '/';
        }

        $parts = parse_url($referer);
        if (!is_array($parts) || !isset($parts['host']) || strtolower($parts['host']) !== strtolower($host)) {
            return '/';
        }

        $path = '/' . ltrim($parts['path'] ?? '', '/\\');
        return $path . (isset($parts['query']) ? "?{$parts['query']}" : '');
    }

    /**
     * Redirect
     * @param string $to URL to Redirect.
     * @param int $code HTTP Status Code. Default is 302.
     * @return never
     */
    private function send(string $to, int $code = 302): never
    {
        if (!in_array($code, self::ALLOWED_CODES, true)) {
            throw new HttpException(500, "Invalid redirect status code: {$code}", 500);
        }
        header("Location: {$to}", true, $code);
        exit;
    }
}
