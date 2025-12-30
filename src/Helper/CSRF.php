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

use Laika\Core\Http\Response;
use Laika\Core\Http\Request;
use Laika\Session\Session;

class CSRF
{
    /**
     * @var int $lifetime CSRF Token Lifetime
     */
    protected int $lifetime;

    /**
     * @var string $for CSRF Session For
     */
    protected string $for;

    /**
     * @var string $key Request Key
     */
    protected string $key;

    /**
     * @var string $header Header Key
     */
    protected string $header;

    /**
     * @var int $time App Start Time
     */
    protected int $time;

    /**
     * Initiate CSRF Object
     * @param ?string $for Default is null. In null 'APP' will be placed as argument
     * @param ?string $key Default is null. In null 'token' will be placed as argument
     */
    public function __construct(?string $for = null, ?string $key = null)
    {
        $this->for = $for ? strtoupper($for) : 'APP';
        $this->key = $key ? strtolower($key) : 'token';
        $this->header = "X-Laika-Token";
        $this->lifetime = (int) \do_hook('option', 'csrf.lifetime', 300); // Default Lifetime is 300
        $this->time = (int) config('env', 'start.time', 300);
        $this->generate();
    }

    /**
     * Create CSRF Token
     * @return string
     */
    private function generate(): string
    {
        $csrf = Session::get($this->key, $this->for);
        // Generate CSRF Token if Not Exists
        if (
            !isset($csrf['created'], $csrf['token']) || // Check Token & Created Time Exists
            !$csrf['created'] || // Check CSRF Created Time is Valid
            !$csrf['token'] || // Check CSRF Token is Valid
            ((int) config('env', 'start.time', 300) - $csrf['created'] > $this->lifetime) // Check Token is Not Expired
        ) {
            return $this->reset();
        }
        $this->header($csrf['token']);
        return $csrf['token'];
    }

    /**
     * Get CSRF Token
     * @return string
     */
    public function get(): string
    {
        $csrf = Session::get($this->key, $this->for);
        if (!isset($csrf['token']) || !$csrf['token']) {
            return $this->reset();
        }
        return $csrf['token'];
    }

    /**
     * Reset Form Token
     * @return string
     */
    public function reset(): string
    {
        $arr = [
            'created'   =>  (int) config('env', 'start.time', 300),
            'token'     =>  bin2hex(random_bytes(32))
        ];
        Session::set($this->key, $arr, $this->for);
        $this->header($arr['token']);
        return $arr['token'];
    }

    /**
     * @return string CSRF Html Field
     */
    public function field(): string
    {
        return "<input type=\"hidden\" name=\"{$this->key}\" value=\"{$this->get()}\">\n";
    }

    /**
     * Validate Form Token
     * @return bool
     */
    public function validate(): bool
    {
        // If CSRF Request Key Missing or Blank, Return false
        $request_token = (string) call_user_func([new Request, 'input'], $this->key);
        if (!$request_token) {
            return false;
        }

        $existing_token = $this->get();
        $this->reset();
        return hash_equals($request_token, $existing_token);
    }

    ##################################################################
    /*------------------------ INTERNAL API ------------------------*/
    ##################################################################
    /**
     * Set Heder Token Key
     * @return void
     */
    private function header(string $value): void
    {
        call_user_func([new Response, 'setHeader'], [$this->header => $value]);
    }
}
