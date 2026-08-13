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

namespace Laika\Core\Storage;

use Laika\Service\{Directory, File};
use RuntimeException;

/**
 * JSON Storage
 *
 * Read & Write Failures Return false. An Invalid Json Name Throws RuntimeException.
 * Writes Are Locked, But set() is a Read-Modify-Write. Concurrent Calls Can Still Lose an Update.
 * Use mutate() When The New Contents Depend on The Current Contents. It Holds One Lock Across Both.
 */
class JsonStorage
{
    /**
     * @var string
     */
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: APP_PATH . '/lf-storage/json';
        // Make Path if Doesn't Exists
        Directory::make($this->path);
    }

    /**
     * Set Json Key Value
     * @param string $name Json File Name
     * @param array $array Json Value
     * @param bool $merge Merge With Existing Contents. Pass false to Replace The Whole Document
     * @return bool
     * @throws RuntimeException
     */
    public function set(string $name, array $array, bool $merge = true): bool
    {
        $file = $this->file($name);

        // Merge Keeps Existing Keys. Replace Writes Only What Was Given
        $contents = $merge ? \array_merge($this->get($name) ?? [], $array) : $array;

        $json = $this->encode($contents);

        // Encode Can Fail on Malformed UTF-8 or Recursion
        if ($json === false) {
            return false;
        }

        return File::write($json, $file, LOCK_EX);
    }

    /**
     * Get Json Value
     * @param string $name Json File Name
     * @param ?string $key Json Key Name. Default is Null
     * @return mixed Null When The File is Missing, Unreadable or Not Valid Json
     * @throws RuntimeException
     */
    public function get(string $name, ?string $key = null): mixed
    {
        $file = $this->file($name);

        if (!File::exists($file)) {
            return null;
        }

        $str = File::read($file);
        if (!\is_string($str) || $str === '') {
            return null;
        }

        $array = \json_decode($str, true);
        if (!\is_array($array)) {
            return null;
        }

        return $key === null ? $array : ($array[$key] ?? null);
    }

    /**
     * Pop A Value
     * @param string $name Json File Name
     * @param string $key Json Key Name
     * @return bool False When The File or The Key Does Not Exist
     * @throws RuntimeException
     */
    public function pop(string $name, string $key): bool
    {
        $array = $this->get($name);

        // Nothing Stored or Key Not Present. Leave The File Untouched
        if (!\is_array($array) || !\array_key_exists($key, $array)) {
            return false;
        }

        unset($array[$key]);

        // Replace, Otherwise The Merge Would Bring The Popped Key Back
        return $this->set($name, $array, false);
    }

    /**
     * Read, Modify & Write Under a Single Exclusive Lock
     *
     * This is The Atomic Path. set() Locks Only The Write, So a Read-Modify-Write
     * Built From get() + set() Can Lose an Update. Use This When The New Contents
     * Depend on The Current Contents.
     *
     * The Callback Receives The Decoded Contents & Returns an Array That May Hold:
     *   'records' => New Contents to Write. Omit it Entirely to Skip The Write
     *   'return'  => Value Handed Back to The Caller
     *
     * @param string $name Json File Name
     * @param callable $fn function(array $records): array
     * @return mixed The Callback's 'return' Value, or Null
     * @throws RuntimeException
     */
    public function mutate(string $name, callable $fn): mixed
    {
        $file = $this->file($name);
        Directory::make(\dirname($file));

        // 'c+' Creates The File if Needed & Never Truncates on Open
        $handle = @\fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException("Unable to Open Json File: [{$file}]");
        }

        try {
            \flock($handle, LOCK_EX);

            $raw = \stream_get_contents($handle);
            $records = \is_string($raw) && $raw !== '' ? \json_decode($raw, true) : [];

            // Corrupt or Non Array Contents Start Over Rather Than Breaking The Callback
            if (!\is_array($records)) {
                $records = [];
            }

            $result = $fn($records);
            $result = \is_array($result) ? $result : [];

            // No 'records' Key Means Nothing Changed. Leave The File Alone
            if (\array_key_exists('records', $result)) {
                $json = $this->encode((array) $result['records']);

                if ($json === false) {
                    throw new RuntimeException("Unable to Encode Json File: [{$file}]");
                }

                \ftruncate($handle, 0);
                \rewind($handle);
                \fwrite($handle, $json);
                \fflush($handle);
            }

            return $result['return'] ?? null;
        } finally {
            \flock($handle, LOCK_UN);
            \fclose($handle);
        }
    }

    /**
     * Encode Contents For Storage
     * Lists Stay Json Arrays. Only Associative Data is Forced to an Object
     * @param array $contents
     * @return string|false
     */
    protected function encode(array $contents): string|false
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if (!\array_is_list($contents)) {
            $flags |= JSON_FORCE_OBJECT;
        }

        return \json_encode($contents, $flags);
    }

    /**
     * Resolve a Json Name to an Absolute Path Inside The Storage Directory
     * @param string $name Json File Name
     * @return string
     * @throws RuntimeException
     */
    protected function file(string $name): string
    {
        $name = \trim(\str_replace('\\', '/', $name), '/');

        if ($name === '') {
            throw new RuntimeException("Invalid Json Name: [{$name}]");
        }

        $file = "{$this->path}/{$name}.json";

        // The File May Not Exist Yet, So Contain The Parent Directory Instead
        $root   = \realpath($this->path);
        $parent = \realpath(\dirname($file));

        // An Unresolvable Parent is a Directory That Does Not Exist Yet. Fall Back to a Textual Check
        $inside = ($root !== false && $parent !== false)
            ? ($parent === $root || \str_starts_with($parent, $root . DS))
            : !\str_contains($name, '..');

        if (!$inside) {
            throw new RuntimeException("Invalid Json Name: [{$name}]");
        }

        return $file;
    }
}
