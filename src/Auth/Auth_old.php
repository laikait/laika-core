<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Auth;

use Laika\Model\Model;
use Laika\Model\Schema\Schema;
use Laika\Session\Relay\Session;
use Laika\Model\Schema\Blueprint;
use RuntimeException;

class Auth extends Model
{
    /** @var string $type Auth Type (e.g. admin, client) */
    protected string $type = 'client';

    /** @var string $table DB Table Name */
    protected string $table = 'laika_auth_app';

    /** @var string $cookie Cookie Name */
    protected string $cookie = 'CLIENT_AUTH_TOKEN';

    /** @var int $ttl Cookie Expire After TTL */
    protected int $ttl = 1800;

    /** @var ?array $user User Data */
    protected ?array $user = null;

    /** @var ?string $event Event ID */
    protected ?string $event = null;

    /** @var int $time Real Time */
    protected int $time = 0;

    /** @var bool $booted Whether init() has been called */
    protected bool $booted = false;

    /*======================================================================*/
    /*============================= PUBLIC API =============================*/
    /*======================================================================*/

    /**
     * Set the auth type. Derives table name and cookie name automatically.
     * @param  string $type e.g. 'ADMIN', 'CLIENT'. Default: 'CLIENT'
     * @return static
     */
    public function setType(string $type = 'CLIENT'): static
    {
        $this->type   = strtolower($type);
        $this->table  = "laika_auth_{$this->type}";
        $this->cookie = strtoupper($type) . '_AUTH_TOKEN';
        $this->booted = false;
        return $this;
    }

    /**
     * Set the database connection name.
     * @param  string $connection Default: 'default'
     * @return static
     */
    public function setConnection(string $connection = 'default'): static
    {
        $this->connection = $connection;
        $this->booted = false;
        return $this;
    }

    /**
     * Set TTL in seconds. Default: 1800 (30 minutes).
     * @param int $ttl
     * @return static
     */
    public function setTtl(int $ttl): static
    {
        $this->ttl = $ttl;
        $this->booted = false;
        return $this;
    }

    /**
     * Bootstrap the Auth instance.
     * Must be called after setType() / setConnection() and before any auth operation.
     * @return void
     */
    public function init(): void
    {
        if ($this->booted) {
            return;
        }

        // Boot parent Model with the chosen connection
        parent::__construct($this->connection);

        // Resolve current session event and timestamp
        Session::for($this->type);
        $this->event = Session::get($this->cookie);
        $this->time = time();
        $this->booted = true;

        // Create auth table if it does not exist yet
        if (!Schema::on($this->connection)->hasTable($this->table)) {
            Schema::on($this->connection)->create($this->table, function (Blueprint $t) {
                $t->string($this->id);
                $t->binary('data');
                $t->unsignedInteger('expire');
                $t->unsignedInteger('created');

                $t->unique($this->id);
                $t->index('expire');
                $t->index('created');
            });
        }

        return;
    }

    /**
     * Create an auth token row in the DB and bind it to the session.
     *
     * @param  array  $user User data to persist
     * @return string Event ID
     */
    public function create(array $user): string
    {
        $this->checkBooted();
        $this->user  = $user;
        $this->event = $this->generateEventKey();
        $expire = $this->time + $this->ttl;

        $this->transaction(function ($m) use ($user, $expire) {
            $m->insert([
                $this->id => $this->event,
                'data'    => json_encode($user),
                'expire'  => $expire,
                'created' => $this->time,
            ]);
        });

        Session::set($this->cookie, $this->event);

        return $this->event;
    }

    /**
     * Retrieve authenticated user data, or null if unauthenticated / expired.
     * Automatically regenerates the token when nearing expiry.
     *
     * @return ?array
     */
    public function user(): ?array
    {
        $this->checkBooted();
        if (empty($this->event)) {
            Session::pop($this->cookie);
            return null;
        }

        $row = $this->where([$this->id => $this->event], '=', 'AND')
                    ->where(['expire' => $this->time], '>')
                    ->first();

        if (empty($row)) {
            Session::pop($this->cookie);
            return null;
        }

        $this->user = json_decode($row['data'], true);

        if (($row['expire'] - $this->time) < ($this->ttl / 2)) {
            $this->regenerate();
        }

        return $this->user;
    }

    /**
     * Regenerate Auth Event ID, preserving the current user data.
     * @return string New event ID
     */
    public function regenerate(): string
    {
        $this->checkBooted();
        $this->destroy(true);
        return $this->create($this->user);
    }

    /**
     * Destroy Auth Session.
     * @param bool $soft If true, only removes the token without ending the PHP session.
     * @return void
     */
    public function destroy(bool $soft = false): void
    {
        $this->checkBooted();
        $this->where([$this->id => $this->event])->delete();
        Session::pop($this->cookie);
        $this->event = null;

        if (!$soft) {
            Session::end();
        }
    }

    /*======================================================================*/
    /*============================ INTERNAL API ============================*/
    /*======================================================================*/
    /**
     * Validate Auth is Booted
     * @throws RuntimeException
     * @return void
     */
    protected function checkBooted(): void
    {
        if (!$this->booted) {
            throw new RuntimeException("Auth Not Booted. Run Auth::init().");
        }
    }

    /**
     * Generate a unique, collision-free event key.
     * @return string
     */
    private function generateEventKey(): string
    {
        $key  = bin2hex(random_bytes(32));
        $rows = $this->select($this->id)->where([$this->id => $key])->count();
        return ($rows === 0) ? $key : $this->generateEventKey();
    }
}
