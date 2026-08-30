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

use Laika\Service\Directory;
use RuntimeException;

class File
{
    ##################################################################
    /* ----------------------- EXTERNAL API ----------------------- */
    ##################################################################

    /**
     * Check Path Exist
     * @param string $file
     * @return bool
     */
    public function exists(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Check Path is Readable
     * @param string $file
     * @return bool
     */
    public function readable( string $file): bool
    {
        return is_readable($file);
    }

    /**
     * Check Path is Writable
     * @param string $file
     * @return bool
     */
    public function writable(string $file): bool
    {
        return is_writable($file);
    }

    /**
     * Get File Size
     * @param string $file
     * @return int|false Output will be in byte
     */
    public function size(string $file): int|false
    {
        return $this->exists($file) ? filesize($file) : false;
    }

    /**
     * Get File Info
     * @param string $file
     * @return array
     */
    public function info(string $file): array
    {
        return pathinfo($file);
    }

    /**
     * Get Mime Type
     * @param string $file
     * @return string|false Mime Type of File on Success and false on Fail
     */
    public function mime(string $file): string|false
    {
        if (!$this->exists($file)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return false;
        }

        // No finfo_close(): it has been a no-op since the ext/fileinfo object
        // migration in PHP 8.1 (this package's minimum), and PHP 8.5 deprecates
        // it. The finfo object is freed when it goes out of scope.
        return finfo_file($finfo, $file);
    }

    /**
     * Get Mime Extension
     * @param string $file
     * @return string
     */
    public function extension(string $file): string
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Get File Name
     * @param string $file
     * @return string
     */
    public function name(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Get File Base Name
     * @param string $file
     * @return string
     */
    public function base(string $file): string
    {
        return pathinfo($file, PATHINFO_BASENAME);
    }

    /**
     * Get Path
     * @param string $file
     * @return string Path
     */
    public function path(string $file): string
    {
        return pathinfo($file, PATHINFO_DIRNAME);
    }

    /**
     * Get File Content
     * @param string $file
     * @return string|false
     */
    public function read(string $file): string|false
    {
        return file_get_contents($file);
    }

    /**
     * Write Content in File
     * @param string $content Required Argument
     * @param string $file
     * @param int $flags Flags for file_put_contents. Example: LOCK_EX
     * @return bool
     */
    public function write(string $content, string $file, int $flags = 0): bool
    {
        // Make Directory if Not Exists
        Directory::make($this->path($file));
        // Write Contents
        return file_put_contents($file, $content, $flags) !== false;
    }

    /**
     * Add New Content in File
     * @param string $str Content to append
     * @param string $file
     * @return bool
     */
    public function append(string $str, string $file): bool
    {
        // is_writable() is false for a file that does not exist yet, so gating
        // on it alone meant append() could never create one - unlike write().
        if ($this->exists($file) && !$this->writable($file)) {
            return false;
        }

        Directory::make($this->path($file));

        return file_put_contents($file, $str, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Delete File
     * @param string $file
     * @return bool
     */
    public function pop(string $file): bool
    {
        return $this->exists($file) && unlink($file);
    }

    /**
     * Move File
     * @param string $from Old File Path
     * @param string $to New File Path
     * @return bool
     */
    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    /**
     * Copy File
     * @param string $from Old File Path
     * @param string $to Destination File Name
     * @return bool
     */
    public function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    /**
     * Sets Access & Modification Time of File
     * @param string $file
     * @param ?int $mtime Modefied Time. Default is null
     * @param ?int $atime Access Time. Default is null
     * @return bool
     */
    public function touch(string $file, ?int $mtime = null, ?int $atime = null): bool
    {
        return touch($file, $mtime, $atime);
    }

    /**
     * Require File
     * @param bool $once Require Once if true
     * @return mixed
     * @throws RuntimeException
     */
    public function require(string $file, bool $require_once = false): mixed
    {
        if (!$this->exists($file)) {
            throw new RuntimeException("Invalid File: [{$file}]");
        }
        return $require_once ? require_once $file : require $file;
    }

    #########################################################################
    ## -------------------------- File Download -------------------------- ##
    #########################################################################
    /**
     * Download File
     * @param string $file
     * @param ?string $as Download As for Content Disposition
     * @return void
     */
    public function download(string $file, ?string $as = null): void
    {
        if (!$this->exists($file) || !$this->readable($file)) {
            throw new RuntimeException("Invalid File: [{$file}]");
        }

        // base(), not name(): PATHINFO_FILENAME strips the extension, so every
        // download used to arrive as a bare name the OS could not open.
        $filename = $this->headerFilename($as ?? $this->base($file));
        $mime     = $this->mime($file) ?: 'application/octet-stream';
        $size     = $this->size($file);

        header("Content-Type: {$mime}");
        header('Content-Disposition: attachment; filename="' . $filename . '";'
            . " filename*=UTF-8''" . rawurlencode($filename));

        if ($size !== false) {
            header("Content-Length: {$size}");
        }

        header('X-Content-Type-Options: nosniff');

        readfile($file);
    }

    /*==============================================================================*/
    /*================================ INTERNAL API ================================*/
    /*==============================================================================*/
    /**
     * Strip Anything That Could Break Out of a Quoted Header Value
     *
     * $as reaches Content-Disposition directly, so a CR/LF in it would split
     * the response and a bare quote would end the filename early.
     * @param string $name
     * @return string
     */
    protected function headerFilename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $name) ?? '';

        return $name !== '' ? $name : 'download';
    }
}
