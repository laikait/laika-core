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

use Laika\Service\Directory;
use RuntimeException;

/**
 * Local Disk Storage
 *
 * Uploads Land Under lf-storage. The Root Never Moves, So The Same Instance
 * Can Upload More Than Once. name(), path(), url() & mime() Describe The Most
 * Recent Upload.
 */
class LocalStorage
{
    /**
     * Absolute Base Directory. Never Mutated By an Upload
     * @var string $root
     */
    protected string $root;

    /**
     * Public Base Url of Uploaded Files
     * @var ?string $baseUrl
     */
    protected ?string $baseUrl;

    /**
     * Last Uploaded File Name
     * @var string $name
     */
    protected string $name = '';

    /**
     * Last Uploaded Absolute Path
     * @var string $path
     */
    protected string $path = '';

    /**
     * Last Uploaded Mime Type
     * @var string $mime
     */
    protected string $mime = '';

    /**
     * @param ?string $root Base Directory. Defaults to APP_PATH/lf-storage
     * @param ?string $publicBaseUrl Public Url The Root Maps to. Defaults to The App Host
     */
    public function __construct(?string $root = null, ?string $publicBaseUrl = null)
    {
        $root = $root ?: APP_PATH . '/lf-storage/files';

        // Make The Directory Before Resolving it. realpath() Returns false For a Missing Path
        Directory::make($root);
        $resolved = \realpath($root);

        if ($resolved === false) {
            throw new RuntimeException("Unable to Resolve Storage Root: [{$root}]");
        }

        $this->root    = \str_replace('\\', '/', $resolved);
        $this->baseUrl = $publicBaseUrl ? \rtrim($publicBaseUrl, '/') . '/' : null;
    }

    ###################################################################
    /*------------------------- PUBLIC API --------------------------*/
    ###################################################################

    /**
     * Upload a File From $_FILES or a Local Path
     * @param array|string $file $_FILES['file'] or a Readable File Path
     * @param ?string $destination Sub Folder. Example: images. Defaults to a Y/m/d Folder
     * @return string Public Url of The Stored File
     * @throws RuntimeException
     */
    public function upload(array|string $file, ?string $destination = null): string
    {
        [$tmpFile, $original] = $this->source($file);

        $this->mime = \mime_content_type($tmpFile) ?: 'application/octet-stream';
        $this->name = $this->version($original);

        // Built Fresh Every Call, So Uploading Twice Doesn't Compound The Path
        $folder = $destination !== null ? \trim($destination, '/') : \date('Y/m/d');
        $folder = $this->contain($folder);

        Directory::make($folder === '' ? $this->root : "{$this->root}/{$folder}");

        $relative   = $folder === '' ? $this->name : "{$folder}/{$this->name}";
        $this->path = "{$this->root}/{$relative}";

        // Move an Actual Upload, Copy Anything Else
        if (\is_uploaded_file($tmpFile)) {
            if (!\move_uploaded_file($tmpFile, $this->path)) {
                throw new RuntimeException("Failed to move uploaded file to [{$this->path}]");
            }
        } elseif (!\copy($tmpFile, $this->path)) {
            throw new RuntimeException("Failed to copy file to [{$this->path}]");
        }

        return $this->url($relative);
    }

    /**
     * Delete a Stored File
     * @param string $file Path Relative to The Storage Root. Example: images/sample.png
     * @return bool False When The File Does Not Exist
     * @throws RuntimeException
     */
    public function delete(string $file): bool
    {
        $target = "{$this->root}/{$this->contain($file)}";

        return \is_file($target) ? \unlink($target) : false;
    }

    /**
     * Public Url For a Stored File
     * @param string $file Path Relative to The Storage Root
     * @return string
     */
    public function url(string $file): string
    {
        $file = \ltrim(\str_replace('\\', '/', $file), '/');

        if ($this->baseUrl !== null) {
            return $this->baseUrl . $file;
        }

        // Root is Usually Inside The App, So Serve it Relative to The App Host
        $appPath  = \str_replace('\\', '/', (string) \realpath(APP_PATH));
        $rootPath = \str_starts_with($this->root, $appPath)
            ? \trim(\substr($this->root, \strlen($appPath)), '/')
            : \basename($this->root);

        return \rtrim(\app_host(), '/') . '/' . \ltrim("{$rootPath}/{$file}", '/');
    }

    /**
     * Storage Root
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Last Uploaded File Name
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Absolute Path of The Last Upload
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Mime Type of The Last Upload
     * @return string
     * @throws RuntimeException
     */
    public function mime(): string
    {
        if ($this->mime === '') {
            throw new RuntimeException("Please Upload The File First!");
        }

        return \strtolower($this->mime);
    }

    ###################################################################
    /*------------------------- INTERNAL API ------------------------*/
    ###################################################################

    /**
     * Resolve The Input to [temp path, original name]
     * @param array|string $file
     * @return array{0:string,1:string}
     * @throws RuntimeException
     */
    protected function source(array|string $file): array
    {
        if (\is_array($file) && isset($file['tmp_name'])) {
            return [$file['tmp_name'], \basename((string) ($file['name'] ?? $file['tmp_name']))];
        }

        if (\is_string($file) && \is_file($file)) {
            return [$file, \basename($file)];
        }

        throw new RuntimeException("Invalid file input. Must be \$_FILES or valid file path.");
    }

    /**
     * Add a Version Suffix So an Upload Never Overwrites an Existing File
     * @param string $name
     * @return string
     */
    protected function version(string $name): string
    {
        $ext  = \pathinfo($name, PATHINFO_EXTENSION);
        $base = \pathinfo($name, PATHINFO_FILENAME);

        return $base . '-' . \uniqid() . '-' . \time() . ($ext ? ".{$ext}" : '');
    }

    /**
     * Keep a Caller Supplied Path Inside The Storage Root
     * @param string $path
     * @return string
     * @throws RuntimeException
     */
    protected function contain(string $path): string
    {
        $path = \trim(\str_replace('\\', '/', $path), '/');

        if ($path !== '' && (\str_contains($path, '../') || \str_starts_with($path, '..'))) {
            throw new RuntimeException("Invalid Path: [{$path}]");
        }

        return $path;
    }
}
