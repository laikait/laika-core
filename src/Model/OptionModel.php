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
defined('APP_PATH') || http_response_code(403).die('403 Direct Access Denied!');

use Laika\Model\Model;
use Laika\Core\Schema\OptionSchema;
use Laika\Core\Exceptions\OptionException;

class OptionModel
{
    /** @var string Table Name */
    protected string $table = 'options';

    /** @var ?Model Model */
    private static ?Model $model = null;

    /** @var string Option Key Column */
    private string $key = 'op_key';

    /** @var string Option Value Column */
    private string $value = 'op_value';

    /** @var string Database Connection Name */
    protected string $connection = 'default';

    /** @var bool Schema Ready */
    private static bool $schemaReady = false;

    /** @var array cached */
    private static array $cached = [];

    public function __construct(?string $connection = null)
    {
        // Set Connection Name
        if (($connection !== null) && ($connection !== '')) {
            $this->connection = $connection();
        }
        try {
            self::$model ??= new Model($this->connection);
        } catch (OptionException $e) {
            throw new OptionException("Option Model Initialization Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }

    /**
     * Option Schema Install
     * @param ?string $connection Defaault is null
     * @return void
     */
    public function install(?string $connection = null): void
    {
        try {
            self::$model ??= new Model($this->connection);
            if (!self::$schemaReady) {
                (new OptionSchema($this->connection))->up();
                self::$schemaReady = true;
            }
        } catch (OptionException $e) {
            throw new OptionException("Option Schema Install Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
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
        if (empty($key)) return $default;

        // Check Already Cached
        if (isset(self::$cached[$key])) return self::$cached[$key];

        try {
            $opt = self::$model->table($this->table)->where([$this->key => $key])->first();
            self::$cached[$key] = $opt[$this->value] ?? $default;
        } catch (\Throwable $th) {
            return $default;
        }
        return self::$cached[$key];
    }

    /**
     * Insert Option
     * @param string $ksy
     * @param mixed $value
     * @return bool
     */
    public function insert(string $key, mixed $value): bool
    {
        $key = trim($key);

        // Return if Empty Key or Already Exists
        if (empty($key) || $this->single($key)) return false;

        try {
            self::$model->transaction(function (Model $m) use ($key, $value) {
                // Make String
                $str = convert_to_string($value);
                $m->table($this->table)->insert([$this->key => $key, $this->value => $str]);
                self::$cached[$key] = $str;
            });
            return true;
        } catch (\Throwable $e) {
            if (DEBUG) throw new OptionException("Option Insert Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
        }
        return false;
    }

    /**
     * Update Option
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function update(string $key, mixed $value): bool
    {
        $key = trim($key);

        // Return if Key is Empty or Doesn't Exists
        if (empty($key) || empty(self::$model->table($this->table)->where([$this->key => $key])->first())) return false;

        try {
            self::$model->transaction(function (Model $m) use ($key, $value) {
                // Make String
                $str = convert_to_string($value);
                $m->table($this->table)->where([$this->key => $key])->update([$this->value => $str]);
                self::$cached[$key] = $str;
            });
            return true;
        } catch (\Throwable $e) {
            if (DEBUG) throw new OptionException("Option Update Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
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
}
