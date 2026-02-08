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
    ##################################################################
    /*------------------------- PUBLIC API -------------------------*/
    ##################################################################

    /**
     * Set Flass Message
     * @param string $message Message to set.
     * @param bool $status
     * @return self
     */
    public function with(string $message, bool $status): self
    {
        Session::set('message', ['info'=>$message,'status'=>$status]);
        return $this;
    }

    /**
     * Redirect Back to The Previous Link
     * @param int $code Response Code. Default is 302
     * @return void
     */
    public function back(int $code = 302): void
    {
        $this->send($_SERVER['HTTP_REFERER'] ?? '/', $code);
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
        if (!\in_array($code, [301,302])) {
            throw new HttpException(500, "Invelid Redirect Code: {$code}", 500);
        }

        if (\parse_url($to, PHP_URL_HOST)) {
            $this->send($to, $code);
            return;
        }

        $this->send(\named($to, $params, true), $code);
        return;
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
        \header("Location:{$to}", true, $code);
        exit();
    }
}
