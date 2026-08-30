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

use Laika\Service\File;
use RuntimeException;
use InvalidArgumentException;

class Upload
{
    /** @var array $fields */
    protected array $fields = [];

    /**
     * @var string[] Refused whatever the caller passes in $options['extensions'].
     * Anything the web server might hand to an interpreter belongs here.
     */
    protected array $blockedExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'htaccess', 'htpasswd', 'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'so', 'dll',
    ];

    /**
     * @var string[] Applied when the caller names no extensions of its own.
     * Deny by default: an upload helper whose default is allow-everything puts
     * the burden on every call site to remember.
     *
     * 'svg' is deliberately absent - served inline it executes script. Add it
     * per call where the serving path forces a download or sanitises.
     */
    protected array $defaultExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        'zip', 'gz', 'tar', 'mp3', 'wav', 'ogg', 'mp4', 'webm',
    ];

    ########################################################################
    /*=========================== EXTERNAL API ===========================*/
    ########################################################################
    /**
     * Initialize Fields
     * @return static
     */
    public function init(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Upload Single File
     * @param string $directory Destination Directory
     * @param ?string $name File Name Without Extension
     * @param array{basename?:string,maxsize?:int,extensions?:string[],mimetypes?:string[],processimage?:bool} $options Optional Options.
     * @return string|false
     */
    public function single(string $directory, ?string $name = null, array $options = []): string|false
    {
        // Check Init
        $this->checkInit();

        if (!isset($this->fields['tmp_name']) || !is_uploaded_file($this->fields['tmp_name'])) {
            $this->fields = [];
            return false;
        }

        try {
            $this->validate($this->fields, $options);
        } catch (RuntimeException $e) {
            $this->fields = [];
            return false;
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $originalName = basename((string) $this->fields['name']);
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // slugify() truncates at the first dot, so it must only ever see a
        // dot-free stem. Passing it "name.ext" silently dropped the extension,
        // and "archive.tar.gz" would still lose the inner ".tar" - turning it
        // into a differently-typed "archive.gz" - without the replace.
        $stem = slugify(str_replace('.', '-', ($name !== null && $name !== '')
            ? $name
            : pathinfo($originalName, PATHINFO_FILENAME)));
        $filename    = $extension !== '' ? "{$stem}.{$extension}" : $stem;
        $destination = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($this->fields['tmp_name'], $destination))
        {
            $this->fields = [];

            if (isset($options['processimage']) && $options['processimage']) {
                $this->processImage($destination);
            }

            return $destination;
        }
        $this->fields = [];
        return false;
    }

    /**
     * Upload Multiple File
     * @param string $destinationDir Destination Directory
     * @param array{basename?:string,maxsize?:int,extensions?:string[],mimetypes?:string[],processimage?:bool} $options Optional Options.
     * @return array{errors:array,success:array}
     */
    public function multiple(string $destinationDir, array $options = []): array
    {
        // Check Init
        $this->checkInit();

        $results = ['success' => [], 'errors' => []];
        $files = $this->normalize();

        $baseName     = $options['basename'] ?? null;
        $processImage = $options['processimage'] ?? false;

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        foreach ($files as $index => $file) {
            $name  = $file['name'] ?? 'file_' . $index;
            $tmp   = $file['tmp_name'] ?? '';
            $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            // Same dot handling as single(): slugify() truncates at the first
            // dot, so "archive.tar.gz" would otherwise lose its ".tar".
            $slug  = slugify(str_replace('.', '-', pathinfo($name, PATHINFO_FILENAME)));

            if (empty($tmp) || $error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $results['errors'][$name] = "Upload error: {$error}";
                continue;
            }

            try {
                $this->validate($file, $options);
            } catch (RuntimeException $e) {
                $results['errors'][$name] = $e->getMessage();
            }

            $finalName   = $baseName ? "{$baseName}_{$index}"  : $slug;
            $destination = rtrim($destinationDir, '/') . '/' . time() . "-{$finalName}.{$ext}";

            if (move_uploaded_file($tmp, $destination)) {
                if ($processImage) {
                    $this->processImage($destination);
                }
                $results['success'][$name] = ['slug' => basename($destination), 'path' => $destination];
            } else {
                $results['errors'][$name] = "{$name} Uploaded File!";
            }
        }

        $this->fields = [];
        return $results;
    }

    /*#########################################################################*/
    /*############################## PRIVATE API ##############################*/
    /*#########################################################################*/
    /**
     * Validate a Single File Against Options
     * @param array $file Normalized File Array
     * @param array $options Options: maxsize, extensions, mimetypes
     * @return void
     * @throws RuntimeException
     */
    protected function validate(array $file, array $options): void
    {
        $maxSize     = $options['maxsize'] ?? null;
        $allowedMime = isset($options['mimetypes']) ? array_map('strtolower', $options['mimetypes']) : null;

        $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $size = $file['size'] ?? 0;
        $tmp  = $file['tmp_name'] ?? '';

        if ($maxSize && ($size > (int) $maxSize)) {
            $maxSizeMB = (int) $maxSize / 1024 / 1024;
            throw new RuntimeException("File exceeds max size ({$maxSizeMB} MB)", 500);
        }

        // Unconditional: a caller cannot re-enable these through $options.
        if ($ext === '' || in_array($ext, $this->blockedExtensions, true)) {
            throw new RuntimeException("Extension .{$ext} is not permitted", 500);
        }

        $allowedExt = isset($options['extensions'])
            ? array_map('strtolower', $options['extensions'])
            : $this->defaultExtensions;

        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException("Extension .{$ext} not allowed", 500);
        }

        if ($allowedMime && $tmp && is_file($tmp)) {
            // File::mime() is already "MIME type of a path", so this does not
            // open its own finfo handle.
            $mime = File::mime($tmp);

            if ($mime === false) {
                throw new RuntimeException('Unable to determine the file type');
            }

            $mime = strtolower($mime);

            if (!in_array($mime, $allowedMime, true)) {
                throw new RuntimeException("MIME type {$mime} not allowed", 500);
            }
        }
    }

    /**
     * Re-encode an Uploaded Image in Place
     *
     * No-op for anything that is not an image. Image has no constructor, so the
     * path must go through path() - `new Image($file)` discards the argument and
     * every later call then throws.
     * @param string $destination Path of the already moved file
     * @return void
     */
    protected function processImage(string $destination): void
    {
        $mime = File::mime($destination);

        if ($mime === false || !str_starts_with(strtolower($mime), 'image/')) {
            return;
        }

        $img = new Image();
        $img->path($destination)->save($destination, 85);
        $img->destroy();
    }
    /**
     * Normalize Multiple Uploaded File
     * @return array
     */
    protected function normalize(): array
    {
        if (!isset($this->fields['name'])) {
            return [];
        }

        if (!is_array($this->fields['name'])) {
            return [$this->fields];
        }

        $files = [];
        foreach ($this->fields['name'] as $i => $name) {
            $files[] = [
                'name'     => $this->fields['name'][$i],
                'type'     => $this->fields['type'][$i],
                'tmp_name' => $this->fields['tmp_name'][$i],
                'error'    => $this->fields['error'][$i],
                'size'     => $this->fields['size'][$i],
            ];
        }
        return $files;
    }

    /**
     * Check Instance init() Called
     * @return void
     * @throws InvalidArgumentException
     */
    protected function checkInit(): void
    {
        if (empty($this->fields)) {
            throw new InvalidArgumentException("Fields Missing! Run Upload::init() First.");
        }
    }
}
