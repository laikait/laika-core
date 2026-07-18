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
use Laika\Service\Cookie;
use Laika\Service\Visitor;
use Laika\Core\Exceptions\CSRFException;

defined('APP_PATH') || http_response_code(403) . die('Direct access not allowed.');

class CSRF
{
    protected int $ttl = 3600;
    protected bool $bindFingerprint = true;
    protected string $usedCookieName = '_xct';

    /**
     * Set Token Total Time Limit
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Bind Fingerprint
     * @param bool $bind
     */
    public function bindFingerprint(bool $bind = true): void
    {
        $this->bindFingerprint = $bind;
    }

    /**
     * Generate CSRF Token
     * @return string
     */
    public function generate(): string
    {
        $payload = $this->b64url((string) json_encode([
            'token' =>  bin2hex(random_bytes(16)),
            'iat'   =>  time(),
            'exp'   =>  time() + $this->ttl,
            'fgp'   =>  $this->fingerprint(),
        ]));

        $signature = $this->b64url(hash_hmac('sha256', "{$payload}", enckey(), true));

        return "{$payload}.{$signature}";
    }

    /**
     * Validate CSRF Token
     * @param string|null $token
     * @return bool
     * @throws CSRFException
     */
    public function validate(?string $token): bool
    {
        if (!$token || substr_count($token, '.') !== 1) {
            throw new CSRFException('Malformed CSRF Token');
        }

        [$payload, $signature] = explode('.', $token);

        $data = json_decode($this->b64urlDecode($payload), true);
        if (!is_array($data) || !isset($data['exp'])) {
            throw new CSRFException('Invalid CSRF Payload');
        }

        if (time() > $data['exp']) {
            throw new CSRFException('Expired CSRF token');
        }

        if ($this->bindFingerprint && ($data['fgp'] ?? '') !== $this->fingerprint()) {
            throw new CSRFException('CSRF Fingerprint Mismatch');
        }

        if (!$this->checkAndBurnToken($data['token'] ?? '', $data['exp'])) {
            throw new CSRFException('CSRF Token Already Used');
        }

        return true;
    }

    /**
     * Get CSRF Token from Request
     * @param string $header
     * @return string|null
     */
    public function fromRequest(string $header = 'X-Csrf-Token'): ?string
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($header));

        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $_POST['_csrf'] ?? null;
    }

    /**
     * Render CSRF Field
     * @return string
     */
    public function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($this->generate()) . '">';
    }

    ####################################################################################
    /*================================= INTERNAL API =================================*/
    ####################################################################################
    /**
     * Base64 URL Encode
     * @param string $string
     * @return string
     */
    protected function b64url(string $string): string
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    /**
     * Base64 URL Decode
     * @param string $string
     * @return string
     */
    protected function b64urlDecode(string $string): string
    {
        $pad = strlen($string) % 4;
        if ($pad) $string .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($string, '-_', '+/'));
    }

    /**
     * Generate Fingerprint
     * @return string
     */
    protected function fingerprint(): string
    {
        if (!$this->bindFingerprint) {
            return '';
        }
        return hash('sha256', Visitor::os() . '|' . Visitor::userAgent());
    }

    /**
     * Check and Burn token to prevent replay attacks
     * @param string $token
     * @param int $exp Expire Time
     * @return bool
     */
    protected function checkAndBurnToken(string $token, int $exp): bool
    {
        if ($token === '') return false;

        $usedArr = Cookie::get($this->usedCookieName, []) ?: [];

        foreach ($usedArr as $j => $e) {
            if ($e < time()) {
                unset($usedArr[$j]);
            }
        }

        if (isset($usedArr[$token])) return false;

        $usedArr[$token] = $exp;
        // cap size to prevent unbounded cookie growth
        if (count($usedArr) > 20) {
            $usedArr = array_slice($usedArr, -20, null, true);
        }

        // Set Cookie with used tokens and their expiration times
        Cookie::ttl($this->ttl)->set($this->usedCookieName, $usedArr);

        return true;
    }
}
