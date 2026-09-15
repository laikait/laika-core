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
        $to = $_SERVER['HTTP_REFERER'] ?? '/';
        if (strpbrk($to, "\r\n") !== false) {
            $to = '/';
        } else {
            $parts = parse_url($to);
            $host  = $parts['host'] ?? null;
            // Accept same-host referers, rebuild as path; reject external hosts
            if ($host === null) {
                $to = '/'; // not a valid absolute URL — fail safe
            } elseif ($host !== ($_SERVER['HTTP_HOST'] ?? '')) {
                $to = '/';
            } else {
                $to = ($parts['path'] ?? '/') . (isset($parts['query']) ? "?{$parts['query']}" : '');
            }
        }

        $this->send($to, $code);
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
