<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Auth;

use Laika\Model\Schema\Blueprint;
use Laika\Model\Schema\Schema;
use Laika\Session\Session;
use Laika\Model\Model;

class Auth extends Model
{
    /** @var string $type Session For */
    protected string $type;

    /** @var string $table DB Table Name */
    protected string $table;

    /** @var string $cookie Cookie Name */
    protected string $cookie;

    /** @var int $ttl Cookie Expire After TTL */
    protected int $ttl;

    /** @var ?array $user User Data */
    protected ?array $user;

    /** @var string $event Event ID */
    protected ?string $event;

    /** @var int $time Real Time */
    protected int $time;

    /**
     * Initiate Auth Session
     * @param string $type. Auth Type. Example: ADMIN/CLIENT. Default is 'APP'
     * @param string $connection. Database Connection Name
     */
    public function __construct(string $type = 'APP', string $connection = 'default')
    {
        $this->type = strtolower($type);
        $this->table = "laika_auth_{$this->type}";
        $this->cookie = strtoupper($type) . "_AUTH_TOKEN";
        $this->ttl = 1800;
        $this->user = null;

        // Set Model Connection
        parent::__construct($connection);

        // Get Existing Event
        $this->event = Session::get($this->cookie, $this->type);
        $this->time = time();

        if (!Schema::on()->hasTable($this->table)) {
            Schema::on()->create($this->table, function (Blueprint $t) {
                $t->string($this->id);
                $t->binary('data');
                $t->unsignedInteger('expire');
                $t->unsignedInteger('created');

                // Indexes
                $t->unique($this->id);
                $t->index('expire');
                $t->index('created');
            });
        }
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
        $this->transaction(function ($m) use($user,$expire) {
            $m->insert([
                $this->id   =>  $this->event,
                'data'      =>  json_encode($user),
                'expire'    =>  $expire,
                'created'   =>  $this->time,
            ]);
        });

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
        $row = $this->where([$this->id => $this->event], '=', 'AND')->where(['expire' => $this->time], '>')->first();

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
        // Destroy Session
        Session::end();
    }

    /**
     * Generate Event Key
     * @return string
     */
    private function generateEventKey(): string
    {
        $key = bin2hex(random_bytes(32));
        // Check Already Exist & Return
        $rows = $this->select($this->id)->where([$this->id => $key])->count();
        return ($rows === 0) ? $key : $this->generateEventKey();
    }
}
