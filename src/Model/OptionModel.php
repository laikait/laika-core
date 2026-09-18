<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Model;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

use Laika\Model\Model;
use Laika\Core\Schema\OptionSchema;
use Laika\Core\Exceptions\OptionException;

class OptionModel
{
    /** @var string Table Name */
    protected string $table = 'options';

    /** @var array<string,Model> Models by Connection Name */
    private static array $models = [];

    /** @var string Option Key Column */
    private string $key = 'op_key';

    /** @var string Option Value Column */
    private string $value = 'op_value';

    /** @var string Database Connection Name */
    protected string $connection = 'default';

    /** @var array<string,bool> Connections Whose Schema Was Installed This Process */
    private static array $installed = [];

    /** @var array<string,array<string,?string>> Cached Values by Connection Name. null Means Missing */
    private static array $cached = [];

    /** @var array<string,bool> Connections Whose Whole Table Was Loaded Into $cached This Process */
    private static array $warmed = [];

    public function __construct(?string $connection = null)
    {
        // Set Connection Name
        if (($connection !== null) && ($connection !== '')) {
            $this->connection = $connection;
        }

        try {
            self::$models[$this->connection] ??= new Model($this->connection);
        } catch (\Throwable $e) {
            throw new OptionException("Option Model Initialization Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
        }

        // app:migrate No Longer Discovers OptionSchema, So The Table & Its
        // Defaults Are Created Here: Once Per Process Per Connection
        $this->install();
    }

    /**
     * Option Schema Install & Default Seed
     * Runs Once Per Process Per Connection. Seeding Skips Keys That Already
     * Exist, So Running it Against an Installed Table Changes Nothing.
     * @param ?string $connection Default is This Model's Connection
     * @return void
     * @throws OptionException In DEBUG Mode When The Install Fails
     */
    public function install(?string $connection = null): void
    {
        $connection = (($connection !== null) && ($connection !== '')) ? $connection : $this->connection;

        if (self::$installed[$connection] ?? false) {
            return;
        }

        try {
            (new OptionSchema($connection))->up();

            // Mark Before Seeding, So insert() Can Never Re-Enter The Install
            self::$installed[$connection] = true;

            $option = ($connection === $this->connection) ? $this : new static($connection);
            foreach (OptionSchema::defaults() as $k => $v) {
                $option->insert($k, $v);
            }
        } catch (\Throwable $e) {
            if (DEBUG) {
                throw new OptionException("Option Schema Install Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * Get Single Value
     * @param string $key
     * @param ?string $default
     * @return ?string
     */
    public function single(string $key, ?string $default = null): ?string
    {
        $key = trim($key);

        // Return If Empty $key
        if (empty($key)) {
            return $default;
        }

        // One Query Loads Every Option, Rather Than One Round Trip Per Key
        $this->warm();

        // Check Already Cached. A Cached null Means The Key is Missing
        if (array_key_exists($key, self::$cached[$this->connection] ?? [])) {
            return self::$cached[$this->connection][$key] ?? $default;
        }

        // Warmed Means Every Stored Key is Already Cached, So This One is Absent
        if (self::$warmed[$this->connection] ?? false) {
            self::$cached[$this->connection][$key] = null;
            return $default;
        }

        try {
            $opt = $this->model()->table($this->table)->where([$this->key => $key])->first();
            // Cache The Stored Value, Not The Default: The Next Caller May Pass a Different One
            self::$cached[$this->connection][$key] = $this->column($opt, $this->value);
        } catch (\Throwable $th) {
            return $default;
        }
        return self::$cached[$this->connection][$key] ?? $default;
    }

    /**
     * Load Every Option Into The Process Cache
     *
     * Once per process per connection. Options are read on nearly every page and
     * the table is small, so one SELECT replaces a round trip per key. A failure
     * leaves the connection unwarmed and single() falls back to per-key queries.
     *
     * @return void
     */
    public function warm(): void
    {
        if (self::$warmed[$this->connection] ?? false) {
            return;
        }

        try {
            $rows = $this->model()->table($this->table)->select([$this->key, $this->value])->get();
        } catch (\Throwable) {
            return;
        }

        foreach ($rows as $row) {
            $k = $this->column($row, $this->key);

            if ($k !== null) {
                // A value written earlier this process is newer than the table read
                self::$cached[$this->connection][$k] ??= $this->column($row, $this->value);
            }
        }

        self::$warmed[$this->connection] = true;
    }

    /**
     * Drop The Process Cache
     *
     * Under FPM a process is one request and this is never needed. A worker that
     * runs many jobs in one process must call it between them, or an option
     * changed elsewhere is never seen for the life of the worker.
     *
     * @param ?string $connection Null clears every connection
     * @return void
     */
    public static function flush(?string $connection = null): void
    {
        if ($connection === null) {
            self::$cached = [];
            self::$warmed = [];
            return;
        }

        unset(self::$cached[$connection], self::$warmed[$connection]);
    }

    /**
     * Insert Option
     * @param string $key
     * @param mixed $value
     * @return bool False When The Key is Empty or Already Exists
     */
    public function insert(string $key, mixed $value): bool
    {
        $key = trim($key);

        // Return if Empty Key or Already Exists. A Stored '' or '0' Still Exists
        if (empty($key) || ($this->single($key) !== null)) {
            return false;
        }

        try {
            // Make String
            $str = convert_to_string($value);
            $this->model()->transaction(function (Model $m) use ($key, $str) {
                $m->table($this->table)->insert([$this->key => $key, $this->value => $str]);
            });
            // Only Once Committed: Cached Inside The Transaction, a Rollback Left a
            // Value in The Cache That Was Never Stored
            self::$cached[$this->connection][$key] = $str;
            return true;
        } catch (\Throwable $e) {
            if (DEBUG) {
                throw new OptionException("Option Insert Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
            }
        }
        return false;
    }

    /**
     * Update Option
     * @param string $key
     * @param mixed $value
     * @return bool False When The Key is Empty or Doesn't Exist
     */
    public function update(string $key, mixed $value): bool
    {
        $key = trim($key);

        // Return if Key is Empty or Doesn't Exists
        if (empty($key) || empty($this->model()->table($this->table)->where([$this->key => $key])->first())) {
            return false;
        }

        try {
            // Make String
            $str = convert_to_string($value);
            $this->model()->transaction(function (Model $m) use ($key, $str) {
                $m->table($this->table)->where([$this->key => $key])->update([$this->value => $str]);
            });
            // Only Once Committed, as in insert()
            self::$cached[$this->connection][$key] = $str;
            return true;
        } catch (\Throwable $e) {
            if (DEBUG) {
                throw new OptionException("Option Update Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
            }
        }
        return false;
    }

    /**
     * Check if Property is Set
     * @param string $prop Property Name
     * @return bool
     */
    public function __isset($prop): bool
    {
        return isset($this->$prop);
    }

    /**
     * Get Property Value
     * @param string $prop Property Name
     * @return mixed
     */
    public function __get($prop): mixed
    {
        return $this->$prop;
    }

    ##############################################################################
    /*============================== INTERNAL API ==============================*/
    ##############################################################################

    /**
     * Model For This Connection
     * @return Model
     */
    private function model(): Model
    {
        return self::$models[$this->connection];
    }

    /**
     * Read a Column From a Row of Either Shape
     *
     * Rows are arrays or stdClass depending on the connection's
     * PDO::ATTR_DEFAULT_FETCH_MODE. Reading $row[$column] off an object quietly
     * gave null, which cached every option on that connection as missing.
     *
     * @param mixed $row
     * @param string $column
     * @return ?string
     */
    private function column(mixed $row, string $column): ?string
    {
        $value = match (true) {
            is_array($row) => $row[$column] ?? null,
            is_object($row) => $row->$column ?? null,
            default => null,
        };

        return $value === null ? null : (string) $value;
    }
}
