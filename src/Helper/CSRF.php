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

use Laika\Core\Relay\Relays\Request;
use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\Cookie;
use Laika\Core\Relay\Relays\Token;

class CSRF
{
    /** @var int $ttl CSRF Token Total Time Limit */
    protected int $ttl;

    /** @var string $key Request Key */
    protected string $key;

    // /** @var string $header Header Key */
    // protected string $header;

    /** @var int $time App Start Time */
    protected int $time;

    /** @var string $token CSRF Token */
    protected string $token;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset CSRF Object
     * @return void
     */
    public function reset(): void
    {
        $this->key = 'token';
        $this->ttl = 300; // Default Lifetime is 300 Seconds
        $this->time = (int) Config::get('env', 'start.time', time()); // Realtime
        $this->token = Cookie::get('_xct', '');
    }

    /**
     * Change Request Key
     * @param string $key
     * @return static
     */
    public function setKey(string $key): static
    {
        $this->key = trim($key);
        return $this;
    }

    /**
     * Set Lifetime
     * @param int $ttl Seconds
     * @return static
     */
    public function setTtl(int $ttl): static
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * Create CSRF Token
     * @return string
     */
    public function generate(): string
    {
        $this->token = Cookie::get('_xct', '');
        if (!Token::validateToken($this->token)) {
            return $this->refresh();
        }
        return $this->token;
    }

    /**
     * Get CSRF Token
     * @return string
     */
    public function get(): string
    {
        return $this->token;
    }

    /**
     * Reset CSRF Token
     * @return string
     */
    public function refresh(): string
    {
        $csrf = ['_xct' => bin2hex(random_bytes(16))];
        $this->token = Token::generate($csrf);
        Cookie::ttl($this->ttl)->set('_xct', $this->token);
        return $this->token;
    }

    /**
     * @return string CSRF Html Field
     * @return string
     */
    public function field(): string
    {
        return "<input type=\"hidden\" name=\"{$this->key}\" value=\"{$this->get()}\">\n";
    }

    /**
     * Check CSRF Form Token is Valid
     * @return bool
     */
    public function is_valid(): bool
    {
        // If CSRF Request Key Missing or Blank, Return false
        $request_token = Request::input($this->key, '');
        if ($request_token === '') {
            return false;
        }

        $existing_token = $this->get();
        $this->refresh();
        return hash_equals($request_token, $existing_token);
    }
}
