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
use Laika\Core\Helper\Url;

class Redirect
{
    /**
     * @property Redirect $instance
     */
    protected static Redirect $instance;

    /**
     * App Host
     * @var string $host
     */
    protected string $host;

    ##################################################################
    /*------------------------- PUBLIC API -------------------------*/
    ##################################################################

    /**
     * Initiate Object
     */
    public function __construct()
    {
        $this->host = do_hook('app.host', call_user_func([new Url, 'base']));
    }

    /**
     * Get Instance
     * @return Redirect
     */
    public static function instance(): Redirect
    {
        self::$instance ??= new Redirect();
        return self::$instance;
    }

    /**
     * Get Method
     * @return string
     * @return void
     */
    public function back(int $code = 302): void
    {
        $this->send($_SERVER['HTTP_REFERER'] ?? $this->host, $code);
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
     * Get Method
     * @param string $to URL to Redirect.
     * @param int $code HTTP Status Code. Default is 302.
     * @return void
     */
    public function to(string $to, int $code = 302): void
    {
        $url = parse_url($to, PHP_URL_HOST);
        if (!$url) {
            $to = $this->host . trim($to, '/');
        }
        $this->send($to, $code);
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
        $url = parse_url($to, PHP_URL_HOST);
        if (!$url) {
            $to = $this->host . trim($to, '/');
        }
        header("Location:{$to}", true, $code);
        exit();
    }

}
