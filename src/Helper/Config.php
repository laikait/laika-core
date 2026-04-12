<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
use RuntimeException;

class Config
{
    /** @var array{string:mixed} $config Contains Config Vars */
    private static array $config = [];

    /** @var string $path */
    private static string $path = __DIR__ . '/../../../../lf-config';

    ######################################################################################
    ## --------------------------------- PUBLIC API ----------------------------------- ##
    ######################################################################################

    /**
     * Get Config Value
     * @param string $name Config file name (without extension)
     * @param ?string $key Config key (optional)
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $name, ?string $key = null, mixed $default = null): mixed
    {
        // Initiate
        self::init();
        $name = strtolower(trim($name));

        // Get Value
        if ($key !== null) {
            return self::$config[$name][$key] ?? $default;
        }
        return self::$config[$name] ?? $default;
    }

    /**
     * Modify a Config Value
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @param null|int|string|bool $value Value to Set
     * @return void
     * @throws RuntimeException
     */
    public static function set(string $name, string $key, null|int|string|bool|array $value): void
    {
        // Initiate
        self::init();
        $name = strtolower(trim($name));
        $key = strtolower(trim($key));

        $file = self::$path . "/{$name}.php";

        if (!File::exists($file)) {
            throw new RuntimeException("Config File [$name}] Does Not Exist.");
        }

        // Ensure config exists in memory
        if (!isset(self::$config[$name]) || !is_array(self::$config[$name])) {
            self::$config[$name] = [];
        }

        // Update in memory
        self::$config[$name][$key] = $value;

        // Rebuild file content with short array syntax
        $content = self::make(self::$config[$name]);

        if (!File::write($content, $file)) {
            throw new RuntimeException("Config Write Failed: [$name}]");
        }
    }

    // Check Name & Key Config Exists
    /**
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @return bool
     * @throws RuntimeException
     */
    public static function has(string $name, ?string $key = null): bool
    {
        // Initiate
        self::init();
        $name = strtolower(trim($name));

        if ($key !== null) {
            $key = strtolower($key);
            return array_key_exists($key, self::$config[$name]);
        }
        return array_key_exists($name, self::$config);
    }

    /**
     * Delete a Config Key
     * @param string $name Config file name (without extension)
     * @param string $key Config key (optional)
     * @return bool
     * @throws RuntimeException
     */
    public static function pop(string $name, string $key): bool
    {
        // Initiate
        self::init();
        $name = strtolower(trim($name));
        $key = strtolower(trim($key));

        $file = self::$path . "/{$name}.php";

        if (!File::exists($file)) {
            throw new RuntimeException("Config File [$name}] Does Not Exist.");
        }

        // Ensure config exists in memory
        if (!isset(self::$config[$name]) || !is_array(self::$config[$name])) {
            return false;
        }

        // Remove From memory
        unset(self::$config[$name][$key]);

        // Rebuild file content with short array syntax
        $content = self::make(self::$config[$name]);

        if (!File::write($content, $file)) {
            throw new RuntimeException("Config Write Failed: [$name}]");
        }
        return true;
    }

    /**
     * Create A New Config File
     * @param string $name Name of the Config to Make Config File
     * @param array $data Data to insert in Config File
     * @return bool
     * @throws RuntimeException
     */
    public static function create(string $name, array $data): bool
    {
        // Initiate
        self::init();
        $name = trim(strtolower($name));

        $file = self::$path . DIRECTORY_SEPARATOR . "{$name}.php";

        // Check File Already Exist
        if (File::exists($file)) {
            throw new RuntimeException("Config File [$name}] Already Exists.");
        }

        self::$config[$name] = $data;

        // Make Array Values
        $content = self::make($data);
        // Create Config File
        if (!File::write($content, $file)) {
            throw new RuntimeException("Config [{$name}] Write Failed!");
        }
        return true;
    }

    ####################################################################################
    ## ------------------------------- INTERNAL API --------------------------------- ##
    ####################################################################################

    /**
     * Initiate Config
     * @return void
     */
    private static function init(): void
    {
        if (!empty(self::$config)) {
            return;
        }
        Directory::make(self::$path);
        self::$path = realpath(self::$path);
        $files = Directory::files(self::$path, 'php');

        foreach ($files as $file) {
            if (is_file($file)) {
                $basename = strtolower(basename($file, '.php'));

                if ($basename != 'providers') {
                    self::$config[$basename] = require $file;
                }
            }
        }
    }

    /**
     * Export a value into short array-friendly PHP syntax
     * @param null|int|string|bool $value Value to Export
     * @return string
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

    /**
     * Allign Key Values From Array
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

    /**
     * Default Content
     */
    private static function defaultContent(): string
    {
        return "<?php\n/**\n* Laika PHP MVC Framework\n* Author: Showket Ahmed\n* Email: riyadhtayf@gmail.com\n* License: MIT\n* This file is part of the Laika PHP MVC Framework.\n* For the full copyright and license information, please view the LICENSE file that was distributed with this source code.\n*/\n\ndeclare(strict_types=1);\n\n// Deny Direct Access\ndefined('APP_PATH') || http_response_code(403).die('403 Direct Access Denied!');\n\nreturn ";
    }

    /**
     * Make Config File Contens
     * @param array $array Key Value Pairs to Make Content
     * @param int $spaces Howq Many Spaces Before Array Values
     * @return string
     */
    private static function make(array $array, int $spaces = 4): string
    {
        return self::defaultContent() . self::allign($array, $spaces);
    }
}
