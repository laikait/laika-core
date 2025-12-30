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

use Laika\Model\Connection;
use Laika\Model\Blueprint;
use Laika\Session\Session;
use Laika\Model\Schema;
use Laika\Model\Model;
// use PDO;

class Auth
{
    // Session For
    private string $type;

    // Model Object
    private Model $model;

    // DB Table Name
    private string $table;

    // Cookie Name
    private string $cookie = '__AUTH_TOKEN';

    // Cookie Expire After TTL
    private int $ttl = 1800; // 1800 Seconds or 30 Minutes

    // User Data
    private ?array $user = null;

    // Event ID
    private ?string $event;

    // Real Time
    private int $time;

    /**
     * Initiate Auth Session
     * @param string $type. Auth Type. Example: ADMIN/CLIENT. Default is 'APP'
     */
    public function __construct(string $type = 'APP')
    {
        $this->type = strtolower($type);
        $this->table = "{$this->type}_sessions";
        $this->model = new Model();
        $this->event = Session::get($this->cookie, $this->type);
        $this->time = (int) do_hook('config.env', 'start.time', time());

        Schema::table($this->table)->create(function(Blueprint $table){
            $table->column('event')->varchar()->length(64)->index();
            $table->column('data')->mediumtext();
            $table->column('expire')->int();
            $table->column('created')->int();
        })->execute();

    }

    /**
     * Checkng TTL
     * @param int $ttl Required TTL Numer. Sytem Default is 1800 Seconds or 30 Minutes
     * @return void
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Create Auth Token in DB Table
     * @param array $user User Data
     * @return string Event ID
     */
    public function create(array $user): string
    {
        $this->user = $user;

        // Get Event ID
        $this->event = $this->generateEventKey();
        // Set Expire Time
        $expire = $this->time + $this->ttl;
        
        // Create User Session
        $this->model
            ->table($this->table)
            ->insert([
                'event'    =>  $this->event,
                'data'     =>  json_encode($user),
                'expire'   =>  $expire,
                'created'  =>  $this->time,
            ]);

        // Set Session
        Session::set($this->cookie, $this->event, $this->type);

        return $this->event;
    }

    /**
     * Get User Data
     * Check User is Authenticated and Not Expired
     * @return ?array
     */
    public function user(): ?array
    {
        // Clear Session if Event Mssing
        if (empty($this->event)) {
            Session::pop($this->cookie, $this->type);
            return null;
        }

        // Get Session User
        $row = $this->model
                ->table($this->table)
                ->where(['event' => $this->event, 'expire' => $this->time])
                ->first();

        // Remove Session Key if Empty
        if (empty($row)) {
            Session::pop($this->cookie, $this->type);
            return null;
        }

        $this->user = json_decode($row['data'], true);

        // Regenerate Cookie if Session Expired
        if (($row['expire'] - $this->time) < ($this->ttl / 2)) {
            self::regenerate();
        }

        return $this->user;
    }

    /**
     * Regenerate Auth Event ID
     * @return string
     */
    public function regenerate(): string
    {
        $this->destroy();
        return $this->create($this->user);
    }

    /**
     * Destroy Auth Event ID
     * @return void
     */
    public function destroy(): void
    {
        // Remove Event & Session Cookie
        $this->model
            ->table($this->table)
            ->where(['event' => $this->event])
            ->delete();
        Session::pop($this->cookie, $this->type);
    }

    /**
     * Generate Event Key
     * @return string
     */
    private function generateEventKey(): string
    {
        $uid = bin2hex(random_bytes(32));
        // Check Already Exist & Return
        $row = $this->model
                    ->table($this->table)
                    ->where(['event' => $this->event])
                    ->get();
        return empty($row) ? $uid : $this->generateEventKey();
    }
}
