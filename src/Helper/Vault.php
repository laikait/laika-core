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

use RuntimeException;

class Vault
{
    /**
     * Cipher Method
     * @var string $chiper
     */
    private string $cipher;

    /**
     * Encryption Key
     * @var string $chiper
     */
    private string $key;

    /**
     * Encryption IV Length
     * @var int $chiper
     */
    private int $ivLength;

    /**
     * Encryption Tag Length
     * @var int $chiper
     */
    private int $tagLength;

    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException("Extension Not Found: 'openssl'");
        }

        $this->cipher = 'aes-256-gcm';
        $this->tagLength = 16;

        // Hash The Key for Consistency
        $this->key = \hash('sha256', Config::get('secret', 'key'), true);
        $this->ivLength = \openssl_cipher_iv_length($this->cipher);
    }

    /**
     * Encrypt Data
     * @param string $text String to Encrypt.
     * @return false|string
     */
    public function encrypt(string $text): false|string
    {
        // Get IV
        $iv = \random_bytes($this->ivLength);
        // Make Tag
        $tag = \bin2hex(random_bytes($this->tagLength));
        $encrypted = \openssl_encrypt($text, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', $this->tagLength);

        // Store IV + Encrypted Data Together (Base64 Encoded)
        if ($encrypted) {
            return \base64_encode($iv . $tag . $encrypted);
        }
        return false;
    }

    /**
     * Decrypt Data
     * @param string $encryptedBase64 Required Argument.
     * @return false|string
     */
    public function decrypt(string $encryptedBase64): false|string
    {
        $data = \base64_decode($encryptedBase64, true);
        if ($data === false || \strlen($data) <= ($this->ivLength + $this->tagLength)) {
            throw new RuntimeException("Invalid Encrypted Data!");
        }

        $iv = \substr($data, 0, $this->ivLength);
        $tag = \substr($data, $this->ivLength, $this->tagLength);
        $encrypted = \substr($data, $this->ivLength + $this->tagLength);

        return \openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
