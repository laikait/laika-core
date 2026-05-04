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

use Laika\Core\Service\Config;
use RuntimeException;

class Vault
{
    /** @var string $cipher */
    private string $cipher;

    /** @var string $key */
    private string $key;

    /** @var int $ivLength */
    private int $ivLength;

    /** @var int $tagLength */
    private int $tagLength;

    /** @var array<string> $allowedCiphers */
    private array $allowedCiphers = [
        'aes-256-gcm',
        'aes-128-gcm',
        'chacha20-poly1305',
    ];

    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException("Extension Not Found: 'openssl'");
        }

        $this->cipher    = 'aes-256-gcm';
        $this->tagLength = 16;

        $secret = Config::get('secret', 'key');
        if (empty($secret)) {
            throw new RuntimeException("Encryption key is not set in config.");
        }
        $this->key = hash('sha256', $secret, true);

        $ivLength = openssl_cipher_iv_length($this->cipher);
        if ($ivLength === false) {
            throw new RuntimeException("Invalid cipher: {$this->cipher}");
        }
        $this->ivLength = $ivLength;
    }

    // -------------------------------------------------------------------------
    // Cipher
    // -------------------------------------------------------------------------

    /**
     * Set Cipher Algorithm
     * @param string $cipher
     * @return static
     */
    public function setCipher(string $cipher): static
    {
        $cipher = strtolower($cipher);
        if (!in_array($cipher, $this->allowedCiphers, true)) {
            throw new RuntimeException("Unsupported cipher: '{$cipher}'. Allowed: " . implode(', ', $this->allowedCiphers));
        }

        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false) {
            throw new RuntimeException("Could not determine IV length for cipher: '{$cipher}'");
        }

        $this->cipher   = $cipher;
        $this->ivLength = $ivLength;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Encryption / Decryption
    // -------------------------------------------------------------------------

    /**
     * Encrypt String
     * @param string $text
     * @return string
     */
    public function encrypt(string $text): string
    {
        $iv  = random_bytes($this->ivLength);
        $tag = '';
        $encrypted = openssl_encrypt($text, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', $this->tagLength);

        if ($encrypted === false) {
            throw new RuntimeException("Encryption failed.");
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt String
     * @param string $encryptedBase64
     * @return string
     */
    public function decrypt(string $encryptedBase64): string
    {
        $data = base64_decode($encryptedBase64, true);
        if ($data === false || strlen($data) <= ($this->ivLength + $this->tagLength)) {
            throw new RuntimeException("Invalid Encrypted Data!");
        }

        $iv        = substr($data, 0, $this->ivLength);
        $tag       = substr($data, $this->ivLength, $this->tagLength);
        $encrypted = substr($data, $this->ivLength + $this->tagLength);

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($decrypted === false) {
            throw new RuntimeException("Decryption failed. Data may be tampered.");
        }

        return $decrypted;
    }

    /**
     * Encrypt Array
     * @param array<mixed> $data
     * @return string
     */
    public function encryptArray(array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        return $this->encrypt($json);
    }

    /**
     * Decrypt Array
     * @param string $encrypted
     * @return array<mixed>
     */
    public function decryptArray(string $encrypted): array
    {
        $json = $this->decrypt($encrypted);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    // -------------------------------------------------------------------------
    // Hashing
    // -------------------------------------------------------------------------

    /**
     * Hash a String (One-Way)
     * @param string $text
     * @param string $algo
     * @return string
     * @throws RuntimeException
     */
    public function hash(string $text, string $algo = 'sha256'): string
    {
        if (!in_array($algo, hash_algos(), true)) {
            throw new RuntimeException("Unsupported hash algorithm: '{$algo}'");
        }
        return hash_hmac($algo, $text, $this->key);
    }

    /**
     * Verify a Hash
     * @param string $text
     * @param string $hash
     * @param string $algo
     * @return bool
     */
    public function hashVerify(string $text, string $hash, string $algo = 'sha256'): bool
    {
        return hash_equals($this->hash($text, $algo), $hash);
    }

    // -------------------------------------------------------------------------
    // Password
    // -------------------------------------------------------------------------

    /**
     * Hash Password
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verify Password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if Password Hash Needs Rehash
     * @param string $hash
     * @return bool
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }

    // -------------------------------------------------------------------------
    // HMAC Signature
    // -------------------------------------------------------------------------

    /**
     * Sign Data
     * @param string $text
     * @return string  base64(data.signature)
     */
    public function sign(string $text): string
    {
        $signature = hash_hmac('sha256', $text, $this->key);
        return base64_encode("{$text}.{$signature}");
    }

    /**
     * Verify Signed Data
     * @param string $signed
     * @return string  Original data
     * @throws RuntimeException
     */
    public function verify(string $signed): string
    {
        $decoded = base64_decode($signed, true);
        if ($decoded === false) {
            throw new RuntimeException("Invalid signed data.");
        }

        $pos = strrpos($decoded, '.');
        if ($pos === false) {
            throw new RuntimeException("Malformed signed data.");
        }

        $data      = substr($decoded, 0, $pos);
        $signature = substr($decoded, $pos + 1);
        $expected  = hash_hmac('sha256', $data, $this->key);

        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException("Signature verification failed. Data may be tampered.");
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Token Generation
    // -------------------------------------------------------------------------

    /**
     * Generate URL-Safe Random Token
     * @param int $length  Byte length (output will be longer due to encoding)
     * @return string
     */
    public function token(int $length = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }

    /**
     * Generate Numeric OTP
     * @param int $digits
     * @return string
     */
    public function numericOtp(int $digits = 6): string
    {
        if ($digits < 4 || $digits > 12) {
            throw new RuntimeException("OTP digits must be between 4 and 12.");
        }
        $max = (int) str_repeat('9', $digits);
        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }
}
