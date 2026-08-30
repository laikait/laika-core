<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

use Laika\Service\Url;
use InvalidArgumentException;

class Cookie
{
    /** @var string samesite */
    protected string $samesite = 'Strict'; // Only Accepted Strict or Lax or None

    /** @var int $ttl Total Time Limit */
    protected int $ttl = 604800; // 7 Days

    /**
     * @var bool $httponly Http Only
     * Defaults on: a cookie readable by script is the exception, not the norm,
     * and the auth token cookie was picking up the permissive default.
     */
    protected bool $httponly = true;

    /** @var string $path Cookie Path */
    protected string $path = '/';

    /**
     * Set Cookie Policy
     * @var string $policy
     * @return static
     */
    public function policy(string $policy): static
    {
        // Validated the lowercased string but stored ucfirst() of the raw one,
        // so policy('STRICT') passed and then wrote SameSite=STRICT.
        $policy = strtolower(trim($policy));

        if (!in_array($policy, ['none', 'lax', 'strict'], true)) {
            throw new InvalidArgumentException("Invalid SameSite Policy [{$policy}]! Only Accepted Strict or Lax or None.");
        }

        // Browsers ignore SameSite=None unless the cookie is also Secure.
        if ($policy === 'none' && !Url::isHttps()) {
            throw new InvalidArgumentException('SameSite=None requires a secure (HTTPS) connection.');
        }

        $this->samesite = ucfirst($policy);
        return $this;
    }

    /**
     * Set Total Time Limit
     * @var int $ttl
     * @return static
     */
    public function ttl(int $ttl): static
    {
        $this->ttl = abs($ttl);
        return $this;
    }

    /**
     * Set Http Only
     * @var bool $httponly
     * @return static
     */
    public function httponly(bool $httponly = true): static
    {
        $this->httponly = $httponly;
        return $this;
    }

    /**
     * Set Path
     * @var string $path
     * @return static
     */
    public function path(string $path): static
    {
        $this->path = trim($path);
        return $this;
    }

    /**
     * Set a cookie (supports string, array, or object)
     * @param string $name Cookie name
     * @param mixed  $value String, array, or object to store
     * @return bool
     */
    public function set(string $name, mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        } else {
            $value = (string) $value;
        }

        // No 'domain': under RFC 6265 an explicit Domain *widens* the cookie to
        // subdomains, where omitting it yields the tighter host-only cookie.
        $result = setcookie($name, rawurlencode($value), [
            'expires'  => time() + $this->ttl,
            'path'     => $this->path,
            'secure'   => Url::isHttps(),
            'httponly' => $this->httponly,
            'samesite' => $this->samesite
        ]);
        $this->reset();
        return $result;
    }

    /**
     * Get a cookie value (will decode JSON if possible)
     * @param string $name Cookie name
     * @param mixed $default Default Value to Return. Default is null
     * @return mixed Returns string or decoded array/object if JSON
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        $value = rawurldecode($_COOKIE[$name]);

        // Try to decode JSON; if fails, return raw string
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $value;
        }
    }

    /**
     * Remove Cookie
     * @param string $name Cookie Name
     * @return void
     */
    public function pop(string $name): void
    {
        if (!isset($_COOKIE[$name])) {
            return;
        }
        // Attributes must mirror set() or the browser treats this as a
        // different cookie and the original survives.
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => $this->path,
            'secure'   => Url::isHttps(),
            'httponly' => $this->httponly,
            'samesite' => $this->samesite
        ]);
        unset($_COOKIE[$name]);

        // set() resets; pop() did not, so configuration leaked into every later
        // cookie on this shared instance.
        $this->reset();
    }

    /*==============================================================================*/
    /*================================ INTERNAL API ================================*/
    /*==============================================================================*/
    /**
     * Reset Properties to Default
     * @return void
     */
    protected function reset(): void
    {
        $this->samesite = 'Strict';
        $this->ttl = 604800;
        $this->httponly = true;
        $this->path = '/';
    }
}
