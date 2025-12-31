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

use Laika\Model\Blueprint;
use Laika\Session\Session;
use Laika\Model\Schema;
use Laika\Model\Model;
// use PDO;

class Auth extends Model
{
    // Session For
    private string $type;

    // DB Table Name
    public string $table;

    // Table ID Column
    public string $id;

    // Table UUID Column
    public string $uuid;

    // Cookie Name
    private string $cookie;

    // Cookie Expire After TTL
    private int $ttl;

    // User Data
    private ?array $user;

    // Event ID
    private ?string $event;

    // Real Time
    private int $time;

    /**
     * Initiate Auth Session
     * @param string $type. Auth Type. Example: ADMIN/CLIENT. Default is 'APP'
     * @param string $connection. Database Connection Name
     */
    public function __construct(string $type = 'APP', string $connection = 'default')
    {
        $this->type = strtolower($type);
        $this->table = "sessions_{$this->type}";
        $this->id = "event";
        $this->uuid = "uuid";
        $this->cookie = strtoupper($type) . "_AUTH_TOKEN";
        $this->ttl = 1800;
        $this->user = null;

        // Set Model Connection
        parent::__construct($connection);
        // $this->model = new Model();
        $this->event = Session::get($this->cookie, $this->type);
        $this->time = time();

        Schema::table($this->table, $connection)->create(function(Blueprint $table){
            $table->column($this->id)->varchar()->length(64)->index();
            $table->column('data')->mediumtext();
            $table->column('expire')->int()->index();
            $table->column('created')->int()->index();
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
        $this->insert([
            $this->id   =>  $this->event,
            'data'      =>  json_encode($user),
            'expire'    =>  $expire,
            'created'   =>  $this->time,
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
        $row = $this->where([$this->id => $this->event, 'expire' => $this->time])->first();

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
        $this->where([$this->id => $this->event])->delete();
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
        $row = $this->where(['event' => $this->event])->get();
        return empty($row) ? $uid : $this->generateEventKey();
    }
}
