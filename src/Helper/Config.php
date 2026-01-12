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

class Config
{
    /**
     * @var ?Config $instance
     */
    private static ?Config $instance = null;

    /**
     * @var array $config Contains Config Vars
     */
    private array $config = [];

    /**
     * @var string $path
     */
    private string $path;

    // Create Object
    private function __construct() // Prevent External Instantiation
    {
        $this->path = APP_PATH . '/lf-config';

        $files = Directory::files($this->path, 'php');

        foreach ($files as $file) {
            if (\is_file($file)) {
                $basename = \strtolower(\basename($file, '.php'));
                $this->config[$basename] = require $file;
            }
        }
    }

    ######################################################################################
    ## --------------------------------- PUBLIC API ----------------------------------- ##
    ######################################################################################

    // Get Value From Config
    /**
     * @param string $name Config file name (without extension)
     * @param ?string $key Config key (optional)
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $name, ?string $key = null, mixed $default = null): mixed
    {
        $obj = self::getInstance();
        $name = strtolower($name);

        // Get Value
        if ($key !== null) {
            return $obj->config[$name][$key] ?? $default;
        }
        return $obj->config[$name] ?? $default;
    }

    // Set/Modify A Value in Config
    /**
     * Modify a key value inside a config file
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @param null|int|string|bool $value Value to Set
     * @return null|int|string|bool
     */
    public static function set(string $name, string $key, null|int|string|bool|array $value): null|int|string|bool|array
    {
        $obj    =   $obj = self::getInstance();
        $name   =   strtolower($name);
        $key    =   strtolower($key);

        $file = new File("{$obj->path}/{$name}.php");

        if (!$file->exists()) {
            throw new \RuntimeException("Config File '{$name}' Does Not Exist.");
        }

        // Ensure config exists in memory
        if (!isset($obj->config[$name]) || !is_array($obj->config[$name])) {
            $obj->config[$name] = [];
        }

        // Update in memory
        $obj->config[$name][$key] = $value;

        // Rebuild file content with short array syntax
        $content = self::make($obj->config[$name]);

        if (!$file->write($content)) {
            throw new \RuntimeException("Config Write Failed: '{$name}'");
        }
        return true;
    }

    // Check Name & Key Config Exists
    /**
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @return bool
     */
    public static function has(string $name, ?string $key = null): bool
    {
        $obj = $obj = self::getInstance();
        $name = strtolower($name);
        if ($key !== null) {
            $key = strtolower($key);
            return isset($obj->config[$name][$key]) && $obj->config[$name][$key];
        }
        return isset($obj->config[$name]);
    }

    // Delete a Config Key
    /**
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @return bool
     */
    public static function pop(string $name, string $key): bool
    {
        $obj    =   $obj = self::getInstance();
        $name   =   strtolower($name);
        $key    =   strtolower($key);

        $file = new File("{$obj->path}/{$name}.php");

        if (!$file->exists()) {
            throw new \RuntimeException("Config File '{$name}' Does Not Exist.");
        }

        // Ensure config exists in memory
        if (!isset($obj->config[$name]) || !is_array($obj->config[$name])) {
            return false;
        }

        // Remove From memory
        unset($obj->config[$name][$key]);

        // Rebuild file content with short array syntax
        $content = self::make($obj->config[$name]);

        if (!$file->write($content)) {
            throw new \RuntimeException("Config Write Failed: '{$name}'");
        }
        return true;
    }

    // Create A New Config File
    /**
     * @param string $name Name of the Config to Make Config File
     * @param array $data Data to insert in Config File
     * @return bool
     */
    public static function create(string $name, array $data): bool
    {
        $obj = self::getInstance();
        $name = trim(strtolower($name));

        $file = $obj->path . "/{$name}.php";

        // Check File Already Exist
        $fileObj = new File($file);
        if ($fileObj->exists()) {
            throw new \RuntimeException("Config File '{$name}' Already Exists.");
        }

        $obj->config[$name] = $data;

        // Make Array Values
        $content = self::make($data);
        // Create Config File
        if (!$fileObj->write($content)) {
            throw new \RuntimeException("Config Write Failed: {$name}");
        }
        return true;
    }

    ########################################################################################
    ## ------------------------------- INTERNAL METHODS --------------------------------- ##
    ########################################################################################

    // Load Configs
    private static function getInstance(): self // Run this method in the beginning of php codes
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    /**
     * Export a value into short array-friendly PHP syntax
     */
    private static function exportValue(null|int|string|bool $value): string
    {
        return match (true) {
            is_null($value)   => 'null',
            is_bool($value)   => $value ? 'true' : 'false',
            is_int($value)    => (string)$value,
            is_float($value)  => (string)$value,
            is_string($value) => "'" . str_replace("'", "\\'", $value) . "'",
            default           => 'null',
        };
    }

    // Allign Key Values From Array
    /**
     * @param array $array Key Value Pairs to Make Content
     * @param int $spaces Howq Many Spaces Before Array Values
     * @return string
     */
    private static function allign(array $array, int $spaces = 4): string
    {
        $content = "[\n";
        foreach ($array as $key => $value) {
            $comment = ucwords((string) str_replace('.', ' ', $key));
            if (is_array($value)) {
                $content .= str_repeat(' ', $spaces) . "// {$comment}\n" . str_repeat(' ', $spaces) . "'{$key}' => " . trim(self::allign($value, $spaces + 4), ';') . ",\n\n";
            } else {
                $value = self::exportValue($value);
                $content .= str_repeat(' ', $spaces) . "// {$comment}\n" . str_repeat(' ', $spaces) . "'{$key}' => {$value},\n\n";
            }
        }
        return "{$content}" . str_repeat(' ', $spaces) . "];";
    }

    ##################################################################################
    ## ----------------------------- PRIVATE API ---------------------------------- ##
    ##################################################################################

    // Default Content
    private static function defaultContent(): string
    {
        return "<?php\n/**\n* Laika PHP MVC Framework\n* Author: Showket Ahmed\n* Email: riyadhtayf@gmail.com\n* License: MIT\n* This file is part of the Laika PHP MVC Framework.\n* For the full copyright and license information, please view the LICENSE file that was distributed with this source code.\n*/\n\ndeclare(strict_types=1);\n\n// Deny Direct Access\ndefined('APP_PATH') || http_response_code(403).die('403 Direct Access Denied!');\n\nreturn ";
    }

    // Make Config File Contens
    /**
     * @param array $array Key Value Pairs to Make Content
     * @param int $spaces Howq Many Spaces Before Array Values
     * @return string
     */
    private static function make(array $array, int $spaces = 4): string
    {
        return self::defaultContent() . self::allign($array, $spaces);
    }
}
