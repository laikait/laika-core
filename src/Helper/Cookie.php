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

use Laika\Core\Relay\Relays\Url;
use InvalidArgumentException;

class Cookie
{
    /** @var string samesite */
    protected string $samesite = 'Strict'; // Only Accepted Strict or Lax or None

    /** @var int $ttl Total Time Limit */
    protected int $ttl = 604800; // 7 Days

    /** @var bool $httponly Http Only */
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
        if (!in_array(strtolower($policy), ['none', 'lax', 'strict'])) {
            throw new InvalidArgumentException("Invalid SameSite Policy [{$policy}]! Only Accepted Strict or Lax or None.");
        }
        $this->samesite = ucfirst(trim($policy));
        return $this;
    }

    /**
     * Set Total Time Limit
     * @var int $ttl
     * @return static
     */
    public function expire(int $ttl): static
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
     * @param int $expires Lifetime in seconds (default 7 days)
     * @param string $path Cookie path (default '/')
     * @param bool $httponly Cookie path (default '/')
     * @return bool
     */
    public function set(string $name, mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        } else {
            $value = (string) $value;
        }

        $result = setcookie($name, rawurlencode($value), [
            'expires'  => time() + $this->ttl,
            'path'     => $this->path,
            'domain'   => Url::host(),
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
            $this->reset();
            return $default;
        }

        $value = rawurldecode($_COOKIE[$name]);

        // Try to decode JSON; if fails, return raw string
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            $this->reset();
            return $decoded;
        } catch (\JsonException $e) {
            $this->reset();
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
            $this->reset();
            return;
        }
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => $this->path,
            'domain'   => Url::host(),
            'secure'   => Url::isHttps(),
            'httponly' => $this->httponly,
            'samesite' => $this->samesite
        ]);
        unset($_COOKIE[$name]);
        $this->reset();
    }

    /*==============================================================================*/
    /*================================ INTERNAL API ================================*/
    /*==============================================================================*/
    /**
     * Reset Properties to Default
     * @return void
     */
    protected function reset()
    {
        $this->samesite = 'Strict';
        $this->ttl = 604800;
        $this->httponly = true;
        $this->path = '/';
    }
}
