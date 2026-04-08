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

namespace Laika\Core\Generator;

use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\Vault;
use Laika\Core\Relay\Relays\Url;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/*======================================================================================*/
/*================================= MOVED TO GENERATOR =================================*/
/*======================================================================================*/
class Token
{
    /** @var string $secret Secret Key */
    private string $secret;

    /** @var int $time Time */
    private int $time;

    /** @var string $issuer Token Issuer */
    private string $issuer;

    /** @var string $audience Token Audience */
    private string $audience;

    /** @var string $algorithm Algorithm */
    private string $algorithm;

    /** @var int $ttl Total Time Limit */
    private int $ttl;

    /** @var array $currentUser User Data */
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->secret = Config::get('secret', 'key');
        $this->time = (int) Config::get('env', 'start.time', time());
        $this->issuer = Url::host();
        $this->algorithm = 'HS256';
        $this->audience = Url::host();
        $this->ttl = 3600;
    }

    /**
     * Set Token Total Time Limit
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Register
     * @param array $user Requried Argument. Example ['id'=>1,'type'=>'staff']
     * @return string
     */
    public function generate(?array $user = null): string
    {
        $payload = [
            'iss'   =>  $this->issuer,
            'aud'   =>  $this->audience,
            'iat'   =>  $this->time,
            'nbf'   =>  $this->time,
            'exp'   =>  $this->time + $this->ttl,
            'data'  =>  $user
        ];

        return Vault::encrypt(JWT::encode($payload, $this->secret, $this->algorithm));
    }

    /**
     * Validate Token
     * @param ?string $token Required Argument. Example: JWT Encoded Token
     * @return bool
     */
    public function validateToken(?string $token): bool
    {
        try {
            $decoded = JWT::decode(Vault::decrypt($token ?: ''), new Key($this->secret, $this->algorithm));
            $this->currentUser = (array) $decoded->data;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check User Data Exist
     * @return bool
     */
    public function check(): bool
    {
        return $this->currentUser ? true : false;
    }

    /**
     * Get User Data
     * Run validateToken() First
     * @return ?array
     */
    public function user(): ?array
    {
        return $this->currentUser;
    }

    /**
     * Flush User Data
     * @return void
     */
    public function flush(): void
    {
        $this->currentUser = null;
    }

    /**
     * Refresh JWT Token With New Expired Time
     * @return ?string
     */
    public function refresh(string $token): ?string
    {
        if (!$this->validateToken($token)) {
            return null;
        }
        return $this->generate($this->currentUser);
    }
}
