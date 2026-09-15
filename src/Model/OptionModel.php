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

        // Check Already Cached. A Cached null Means The Key is Missing
        if (array_key_exists($key, self::$cached[$this->connection] ?? [])) {
            return self::$cached[$this->connection][$key] ?? $default;
        }

        try {
            $opt = $this->model()->table($this->table)->where([$this->key => $key])->first();
            // Cache The Stored Value, Not The Default: The Next Caller May Pass a Different One
            self::$cached[$this->connection][$key] = isset($opt[$this->value]) ? (string) $opt[$this->value] : null;
        } catch (\Throwable $th) {
            return $default;
        }
        return self::$cached[$this->connection][$key] ?? $default;
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
            $this->model()->transaction(function (Model $m) use ($key, $value) {
                // Make String
                $str = convert_to_string($value);
                $m->table($this->table)->insert([$this->key => $key, $this->value => $str]);
                self::$cached[$this->connection][$key] = $str;
            });
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
            $this->model()->transaction(function (Model $m) use ($key, $value) {
                // Make String
                $str = convert_to_string($value);
                $m->table($this->table)->where([$this->key => $key])->update([$this->value => $str]);
                self::$cached[$this->connection][$key] = $str;
            });
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
}
