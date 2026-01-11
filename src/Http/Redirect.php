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

class Redirect
{
    ##################################################################
    /*------------------------- PUBLIC API -------------------------*/
    ##################################################################

    /**
     * Redirect Back to The Previous Link
     * @param string $named Named Route if Rrequired. Default is '/'
     * @param int $code Response Code. Default is 302
     * @return void
     */
    public function back(string $named = '/', int $code = 302): void
    {
        $this->send($_SERVER['HTTP_REFERER'] ?? named('/', url:true), $code);
    }

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
     * Redirect to A Link
     * @param string $to Named/URL to Redirect.
     * @param array $params Named Roue Parameters.
     * @param int $code HTTP Status Code. Default is 302.
     * @return void
     */
    public function to(string $to, array $params = [], int $code = 302): void
    {
        $url = parse_url($to, PHP_URL_HOST);
        if (empty($url)) {
            $url = named($to, $params, true);
        }
        $this->send($url, $code);
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
        header("Location:{$to}", true, $code);
        exit();
    }

}
