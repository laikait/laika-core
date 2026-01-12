<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Laika\Core\Helper;

class File
{
    // Path
    /**
     * @var string $path
     */
    protected string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    ##################################################################
    /* ----------------------- EXTERNAL API ----------------------- */
    ##################################################################
    /**
     * Check Path Exist
     * @return bool
     */
    public function exists(): bool
    {
        return \file_exists($this->file);
    }

    /**
     * Check Path is Readable
     * @return bool
     */
    public function readable(): bool
    {
        return \is_readable($this->file);
    }

    /**
     * Check Path is Writable
     * @return bool
     */
    public function writable(): bool
    {
        return \is_writable($this->file);
    }

    /**
     * Get File Size
     * @return int|false Output will be in byte
     */
    public function size(): int|false
    {
        return $this->exists() ? \filesize($this->file) : false;
    }

    /**
     * Get File Info
     * @return string
     */
    public function info(): array
    {
        return \pathinfo($this->file);
    }

    /**
     * Get Mime Type
     * @return string|false Mime Type of File on Success and false on Fail
     */
    public function mime(): string|false
    {
        $finfo = \finfo_open(FILEINFO_MIME_TYPE);
        $mime = \finfo_file($finfo, $this->file);
        \finfo_close($finfo);
        return $mime;
    }

    /**
     * Get Mime Extension
     * @return string
     */
    public function extension(): string
    {
        return \pathinfo($this->file, PATHINFO_EXTENSION);
    }

    /**
     * Get File Name
     * @return string
     */
    public function name(): string
    {
        return \pathinfo($this->file, PATHINFO_FILENAME);
    }

    /**
     * Get File Base Name
     * @return string
     */
    public function base(): string
    {
        return \pathinfo($this->file, PATHINFO_BASENAME);
    }

    /**
     * Get Path
     * @return string Path
     */
    public function path(): string
    {
        return \pathinfo($this->file, PATHINFO_DIRNAME);
    }

    /**
     * Get File Content
     * @return string|false
     */
    public function read(): string|false
    {
        return \file_get_contents($this->file);
    }

    /**
     * Write Content in File
     * @param string $str Required Argument
     * @return bool
     */
    public function write(string $str): bool
    {
        // Make Directory if Not Exists
        Directory::make($this->path());
        // Write Contents
        return \file_put_contents($this->file, $str) !== false;
    }

    /**
     * Add New Content in File
     * @param string $str Content to append
     * @return bool
     */
    public function append(string $str): bool
    {
        return $this->writable() ? (\file_put_contents($this->file, $str, FILE_APPEND) !== false) : false;
    }

    /**
     * Delete File
     * @return bool
     */
    public function pop(): bool
    {
        return \unlink($this->file);
    }

    /**
     * Move File
     * @param string $to New file name to move
     * @return bool
     */
    public function move(string $to): bool
    {
        $result = \rename($this->file, $to);
        if ($result) {
            $this->file = $to;
        }
        return $result;
    }

    /**
     * Copy File
     * @param string $to Destination File Name
     * @return bool
     */
    public function copy(string $to): bool
    {
        return \copy($this->file, $to);
    }

    /**
     * Sets Access & Modification Time of File
     * @param ?int $mtime Modefied Time. Default is null
     * @param ?int $atime Access Time. Default is null
     * @return bool
     */
    public function touch(?int $mtime = null, ?int $atime = null): bool
    {
        return \touch($this->file, $mtime, $atime);
    }

    /**
     * Require File
     * @param bool $once Require Once if true
     * @return void
     */
    public function require(bool $require_once = false): void
    {
        $require_once ? require_once $this->file : require $this->file;
        return;
    }

    #########################################################################
    ## -------------------------- File Download -------------------------- ##
    #########################################################################
    /**
     * @param ?string $as Download As for Content Disposition
     * @return void
     */
    public function download(?string $as = null): void
    {
        $filename = $as ?? $this->name();
        $mime = $this->mime() ?: 'application/octet-stream';

        \header("Content-Type: {$mime}");
        \header("Content-Disposition: attachment; filename=\"{$filename}\"");
        \header("Content-Length: {$this->size()}");
        \readfile($this->file);
        return;
    }
}
