<?php
/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Auth;

use PDO;
use PDOException;
use Laika\Model\Model;
use Laika\Core\Service\DB;
use Laika\Model\Schema\Schema;
use Laika\Session\Service\Session;
use Laika\Model\Schema\Blueprint;

class Auth
{
    private Model $model;
    private string $table;
    private int $lifetime;
    private static bool $booted = false;

    public function __construct(string $guard = 'client', int $lifetime = 3600)
    {
        //Validate guard name
        if (!preg_match('/^[a-z]+$/i', $guard)) throw new RuntimeException("Invalid guard name: {$guard}");

        DB::run(); // Ensure DB is initialized

        $this->model    = new Model();
        $this->table    = strtolower($guard) . '_auth';
        $this->lifetime = $lifetime;

        if (!self::$booted) {
            $this->createTable();
            self::$booted = true;
        }

        $this->purgeExpired();
    }

    // ── Table ───────────────────────────────────────────────────────────────

    // ── Token ────────────────────────────────────────────────────────────────

    private function buildToken(string $sessionId, int $expiresAt, int $userId): string
    {
        $parts = implode('|', [
            $sessionId,
            $expiresAt,
            $userId,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $this->getIp(),
        ]);

        return hash_hmac('sha256', $parts, $sessionId);
    }

    // ── Login ────────────────────────────────────────────────────────────────

    public function login(int $userId, array $userData = []): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = session_id();
        $now       = time();
        $expiresAt = $now + $this->lifetime;
        $token     = $this->buildToken($sessionId, $expiresAt, $userId);

        $stmt = $this->pdo->prepare("
            INSERT INTO `{$this->table}`
                (token, session_id, user_id, user_agent, ip, user_data, expires_at, created_at)
            VALUES
                (:token, :session_id, :user_id, :user_agent, :ip, :user_data, :expires_at, :created_at)
            ON DUPLICATE KEY UPDATE
                expires_at = VALUES(expires_at),
                user_data  = VALUES(user_data)
        ");

        $stmt->execute([
            ':token'      => $token,
            ':session_id' => $sessionId,
            ':user_id'    => $userId,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':ip'         => $this->getIp(),
            ':user_data'  => json_encode($userData),
            ':expires_at' => $expiresAt,
            ':created_at' => $now,
        ]);

        $_SESSION[$this->table . '_token'] = $token;

        return $token;
    }

    // ── Check / Refresh ──────────────────────────────────────────────────────

    public function check(): bool
    {
        return $this->getRow() !== null;
    }

    public function user(): ?array
    {
        $row = $this->getRow();
        if (!$row) return null;

        $data           = json_decode($row['user_data'] ?? '[]', true) ?? [];
        $data['id']     = $row['user_id'];
        return $data;
    }

    public function refresh(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_SESSION[$this->table . '_token'] ?? null;
        if (!$token) return;

        $newExpiry = time() + $this->lifetime;

        $stmt = $this->pdo->prepare("
            UPDATE `{$this->table}`
            SET expires_at = :expires_at
            WHERE token = :token AND expires_at > :now
        ");

        $stmt->execute([
            ':expires_at' => $newExpiry,
            ':token'      => $token,
            ':now'        => time(),
        ]);
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_SESSION[$this->table . '_token'] ?? null;

        if ($token) {
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE token = :token");
            $stmt->execute([':token' => $token]);
        }

        unset($_SESSION[$this->table . '_token']);
    }

    ##################################################################################
    ################################## INTERNAL API ##################################
    ##################################################################################

    /**
     * Create Table
     * @return void
     */
    private function createTable(): void
    {
        Schema::on()->createIfNotExists($this->table, function (Blueprint $table) {
            $table->id('id');
            $table->string('token', 512);
            $table->string('session_id', 128);
            $table->unsignedInteger('user_id');
            $table->string('user_agent', 512)->nullable();
            $table->string('ip', 40)->nullable();
            $table->serialize('user_data')->nullable()->comment('Serialized Data');
            $table->unsignedInteger('expires_at');
            $table->unsignedInteger('created_at');

            // Indexes
            $table->unique('token');
            $table->index('session_id');
            $table->index('expires_at');
        });
    }

    /**
     * Get a Single Session
     * @retunr ?array
     */
    private function getRow(): ?array
    {
        $token = Session::get("{$this->table}_token");
        if (empty($token)) return null;

        $row = $this->model->table($this->table)->where(['token' => $token])->where(['expires_at' => time()], '>')->first();

        if (empty($row)) return null;

        // Validate IP + UA
        if ($row['ip'] !== $this->getIp()) return null;
        if ($row['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) return null;

        $this->refresh();

        return $row;
    }

    private function purgeExpired(): void
    {
        $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE expires_at <= :now")
                  ->execute([':now' => time()]);
    }

    private function getIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}