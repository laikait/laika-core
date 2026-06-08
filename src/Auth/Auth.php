<?php
/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Auth;

use RuntimeException;
use Laika\Model\Model;
use Laika\Core\Service\DB;
use InvalidArgumentException;
use Laika\Model\Schema\Schema;
use Laika\Core\Service\Visitor;
use Laika\Model\Schema\Blueprint;
use Laika\Session\Service\Session;

class Auth
{
    /** @var Model $model */
    private Model $model;

    /** @var string $table */
    private string $table;

    /** @var int $lifetime */
    private int $lifetime;

    /** @var int $realtime */
    private int $realtime;

    /** @var string $token_key */
    private string $token_key;

    /** @var ?array $session */
    private ?array $session;

    /** @var string $guard */
    private string $guard;

    /** Constants */
    public CONST AUTHORIZED = 1;
    public CONST UNAUTHORIZED = 2;
    public CONST INVALID_TOKEN = 3;
    public CONST INVALID_USER_AGENT = 4;
    public CONST INVALID_DEVICE = 5;
    public CONST INVALID_OS = 6;

    public function __construct(string $guard = 'user')
    {
        // Validate Guard
        if (!preg_match('/^[\w]+$/', $guard)) throw new InvalidArgumentException("Guard Has Unsupported Character: [{$guard}]");

        DB::run(); // Ensure DB is initialized
        DB::session(); // Ensure Session in DB

        $this->model        =   new Model();
        $this->guard        =   strtolower($guard);
        $this->token_key    =   strtoupper($guard) . '_AUTH_TOKEN';
        $this->table        =   'lf_authorizations';
        $this->lifetime     =   3600;
        $this->realtime     =   time();
        $this->session      =   null;
    }

    ##################################################################################
    ################################### PUBLIC API ###################################
    ##################################################################################
    /**
     * Set Lifetime
     * @param int $ttl In Seconds
     * @return static
     */
    public function setLifetime(int $ttl): static
    {
        if ($ttl < 120) throw new InvalidArgumentException("Lifetime Should Be Greater Than 120 Seconds!");
        $this->lifetime = $ttl;
        return $this;
    }

    /**
     * Login
     * @param int $userId
     * @param array $userData
     * @return string
     */
    public function login(int $userId, array $userData): string
    {
        // Check User Data
        if (empty($userData)) throw new InvalidArgumentException('User data cannot be empty.');

        // Session::regenerate();
        $token = $this->buildToken();

        $sql = "INSERT INTO `{$this->table}`
                (token, session_id, user_type, user_id, user_agent, device, os, user_data, expires_at, created_at)
            VALUES
                (:token, :session_id, :user_type, :user_id, :user_agent, :device, :os, :user_data, :expires_at, :created_at)
            ON DUPLICATE KEY UPDATE
                expires_at = VALUES(expires_at),
                user_data  = VALUES(user_data)";
        $params = [
            ':token'        =>  $token,
            ':session_id'   =>  Session::id(),
            ':user_type'    =>  $this->guard,
            ':user_id'      =>  $userId,
            ':user_data'    =>  serialize($userData),
            ':user_agent'   =>  Visitor::userAgent(),
            ':device'       =>  Visitor::deviceType(),
            ':os'           =>  Visitor::os(),
            ':expires_at'   =>  $this->realtime + $this->lifetime,
            ':created_at'   =>  $this->realtime,
        ];
        try {
            $this->model->execute($sql, $params);
        } catch (\Throwable $th) {
            throw new RuntimeException("Failed to create auth session: " . $th->getMessage());
        }

        Session::set('token', $token, $this->token_key);
        $this->session = null;

        return $token;
    }

    /**
     * Check User Exists
     * @return bool
     */
    public function check(): bool
    {
        return $this->getRow()['success'];
    }

    /**
     * Check Guest
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get User Data
     * @return array
     */
    public function data(): array
    {
        return unserialize($this->getRow()['data']['user_data'] ?? 'N;') ?? [];
    }

    /**
     * Get Logged-in User
     * @return array{success:string,message:int|string,data:array}
     */
    public function user(): array
    {
        $row = $this->getRow();
        if (!$row['success'] || ($row['message'] !== Auth::AUTHORIZED)) return response(false, Auth::UNAUTHORIZED, []);
        $user = unserialize($row['data']['user_data'] ?? 'N;') ?? [];

        if (empty($user)) return response(false, Auth::UNAUTHORIZED, []);

        return response(true, Auth::AUTHORIZED, $user);
    }

    /**
     * Get User ID
     * @return ?int
     */
    public function id(): ?int
    {
        return $this->getRow()['data']['user_id'] ?? null;
    }

    /**
     * Get User Type
     * @return ?string
     */
    public function type(): ?string
    {
        return $this->getRow()['data']['user_type'] ?? null;
    }

    /**
     * Logout
     * @return void
     */
    public function logout(): void
    {
        $token = Session::get('token', null, $this->token_key);
        if (!$token) return;
        Session::pop('token', $this->token_key);
        $this->session = null;
        $this->model->table($this->table)->where(['token' => $token])->delete();
    }

    ##################################################################################
    ################################## INTERNAL API ##################################
    ##################################################################################
    /**
     * Refresh Expire Time
     * @return void
     */
    private function refresh(): void
    {
        $token = Session::get('token', null, $this->token_key);
        if (!$token) return;

        $newExpiry = $this->realtime + $this->lifetime;
        $this->session['expires_at'] = $newExpiry;

        $this->model->table($this->table)
                    ->where(['token' => $token, 'session_id' => Session::id(), 'user_type' => $this->guard])
                    ->where(['expires_at' => $this->realtime], '>')
                    ->update(['expires_at' => $newExpiry]);
    }

    /**
     * Build Token
     * @return string
     */
    private function buildToken(): string
    {
        return $this->guard . bin2hex(random_bytes(32));
    }

    /**
     * Get a Single Session
     * @return array
     */
    private function getRow(): array
    {
        // Return If Already Session Exists
        if ($this->session !== null) return response(true, Auth::AUTHORIZED, $this->session);

        $token = Session::get('token', null, $this->token_key);
        if (!$token) return response(false, Auth::INVALID_TOKEN, []);

        $this->session = $this->model
                            ->table($this->table)
                            ->where(['token' => $token, 'session_id' => Session::id(), 'user_type' => $this->guard])
                            ->where(['expires_at' => $this->realtime], '>')
                            ->first();

        if (empty($this->session)) return response(false, Auth::INVALID_TOKEN, []);

        // Validate User Agent
        if ($this->session['user_agent'] !== Visitor::userAgent()) {
            $this->logout();
            return response(false, Auth::INVALID_USER_AGENT, []);
        }
        // Validate Device
        if ($this->session['device'] !== Visitor::deviceType()) {
            $this->logout();
            return response(false, Auth::INVALID_DEVICE, []);
        }
        // Validate OS
        if ($this->session['os'] !== Visitor::os()) {
            $this->logout();
            return response(false, Auth::INVALID_OS, []);
        }

        // Refresh Expire Time
        if (($this->session['expires_at'] - $this->realtime) < 120) {
            $this->refresh();
        }

        return response(true, Auth::AUTHORIZED, $this->session);
    }

    /**
     * Delete Expired
     * @return void
     */
    public function deleteExpired(): void
    {
        $this->model
            ->table($this->table)
            ->where(['expires_at' => $this->realtime], '<')
            ->delete();
    }
}