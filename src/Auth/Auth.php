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
use Laika\Model\Schema\Schema;
use Laika\Core\Service\Visitor;
use Laika\Session\Service\Session;
use Laika\Model\Schema\Blueprint;

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

    /** @var bool $booted */
    private static bool $booted = false;

    /** Constants */
    private CONST AUTHORIZED = 1;
    private CONST UNAUTHORIZED = 2;
    private CONST INVALID_TOKEN = 3;
    private CONST INVALID_USER_AGENT = 4;
    private CONST INVALID_DEVICE = 5;
    private CONST INVALID_OS = 6;

    /**
     * @param string $guard Example: ADMIN, CLIENT
     * @param int $lifetime Default is 3600 Seconds
     */
    public function __construct(string $guard, int $lifetime = 3600)
    {
        // Validate guard name
        if (!preg_match('/^[a-z]+$/i', $guard)) throw new RuntimeException("Invalid Auth Guard Name: [{$guard}]". " Only Letters Allowed.");

        DB::run(); // Ensure DB is initialized
        Session::config(DB::connection()); // Config Session

        $this->model        =   new Model();
        $this->token_key    =   'TOKEN_' . strtoupper($guard);
        $this->table        =   'lf_auth_' . strtolower($guard);
        $this->lifetime     =   $lifetime;
        $this->realtime     =   time();

        // Create Table if Does Not Exists
        $this->createTable();
    }

    ##################################################################################
    ################################### PUBLIC API ###################################
    ##################################################################################
    /**
     * Login
     * @param int $userId
     * @param array $userData
     * @return string
     */
    public function login(int $userId, array $userData = []): string
    {
        $token = $this->buildToken($userId);

        $sql = "INSERT INTO `{$this->table}`
                (token, session_id, user_id, user_agent, device, os, user_data, expires_at, created_at)
            VALUES
                (:token, :session_id, :user_id, :user_agent, :device, :os, :user_data, :expires_at, :created_at)
            ON DUPLICATE KEY UPDATE
                expires_at = VALUES(expires_at),
                user_data  = VALUES(user_data)";
        $params = [
            ':token'        =>  $token,
            ':session_id'   =>  Session::id(),
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

        return $token;
    }

    /**
     * Get Logged-in User
     * @return array{success:string,status:int|string,data:array}
     */
    public function user(): array
    {
        $row = $this->getRow();
        if (!$row['success'] || ($row['message'] !== Auth::AUTHORIZED)) return response(false, Auth::UNAUTHORIZED, []);
        $user = unserialize($row['data']['user_data'] ?? '[]') ?? [];

        if (empty($user)) return response(false, Auth::UNAUTHORIZED, []);

        return response(true, Auth::AUTHORIZED, $user);
    }

    public function refresh(): void
    {
        $token = Session::get('token', null, $this->token_key);
        if (!$token) return;

        $newExpiry = $this->realtime + $this->lifetime;

        $this->model->table($this->table)
                    ->where(['token' => $token])
                    ->where(['expires_at' => $this->realtime], '>')
                    ->update(['expires_at' => $newExpiry]);
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $token = Session::get('token', null, $this->token_key);
        Session::destroy();
        if (!$token) return;
        $this->model->table($this->table)->where(['token' => $token])->delete();
    }

    ##################################################################################
    ################################## INTERNAL API ##################################
    ##################################################################################
    /**
     * Build Token
     * @param int|string $userId
     * @return string
     */
    private function buildToken(int|string $userId): string
    {
        $expiresAt = $this->realtime + $this->lifetime;
        $str = implode('|', [
            Session::id(),
            $expiresAt,
            $userId,
            Visitor::userAgent(),
            Visitor::deviceType(),
            Visitor::os(),
        ]);

        return hash_hmac('sha256', $str, config('secret', 'key'));
    }

    /**
     * Create Table
     * @return void
     */
    private function createTable(): void
    {
        if (self::$booted) return;
        Schema::on()->createIfNotExists($this->table, function (Blueprint $table) {
            $table->id('id');
            $table->string('token', 512);
            $table->string('session_id', 128);
            $table->unsignedInteger('user_id');
            $table->string('user_agent', 512)->nullable();
            $table->string('device', 40)->nullable();
            $table->string('os', 40)->nullable();
            $table->serialize('user_data')->nullable()->comment('Serialized Data');
            $table->unsignedInteger('expires_at');
            $table->unsignedInteger('created_at');

            // Indexes
            $table->unique('token');
            $table->index('session_id');
            $table->index('expires_at');
        });
        self::$booted = true;
    }

    /**
     * Get a Single Session
     * @retunr array
     */
    private function getRow(): array
    {
        $token = Session::get('token', null, $this->token_key);
        if (!$token) return response(false, Auth::INVALID_TOKEN, []);

        $row = $this->model
                    ->table($this->table)
                    ->where(['token' => $token])
                    ->where(['expires_at' => $this->realtime], '>')
                    ->first();

        if (empty($row)) return response(false, Auth::INVALID_TOKEN, []);

        // Validate User Agent
        if ($row['user_agent'] !== Visitor::userAgent()) {
            $this->logout();
            return response(false, Auth::INVALID_USER_AGENT, []);
        }
        // Validate Device
        if ($row['device'] !== Visitor::deviceType()) {
            $this->logout();
            return response(false, Auth::INVALID_DEVICE, []);
        }
        // Validate OS
        if ($row['os'] !== Visitor::os()) {
            $this->logout();
            return response(false, Auth::INVALID_OS, []);
        }

        // Refresh Expire Time
        $this->refresh();

        return response(true, Auth::AUTHORIZED, $row);
    }
}