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

use InvalidArgumentException;

class Upload
{
    /** @var array $fields */
    protected array $fields = [];

    /*##########################################################################*/
    /*############################### PUBLIC API ###############################*/
    /*##########################################################################*/
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

        $error = $this->validate($this->fields, $options);
        if ($error !== null) {
            $this->fields = [];
            return false;        // or throw, depending on your preference
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $originalName = basename($this->fields['name']);
        $extension    = pathinfo($originalName, PATHINFO_EXTENSION);
        $name         = slugify($name ? "{$name}.{$extension}" : $originalName);
        $destination  = rtrim($directory, '/') . "/{$name}";

        if (move_uploaded_file($this->fields['tmp_name'], $destination))
        {
            $this->fields = [];
            if (isset($options['processimage']) && $options['processimage']) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); 
                $mime  = strtolower(finfo_file($finfo, $destination));
                finfo_close($finfo);

                if (str_starts_with($mime, 'image/')) {
                    $img = new Image($destination);
                    $img->save($destination);
                    $img->destroy();
                }
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
            $slug  = slugify(pathinfo($name, PATHINFO_FILENAME));

            if (empty($tmp) || $error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $results['errors'][$name] = "Upload error: {$error}";
                continue;
            }

            $validationError = $this->validate($file, $options);
            if ($validationError !== null) {
                $results['errors'][$name] = $validationError;
                continue;
            }

            $finalName   = $baseName ? "{$baseName}_{$index}"  : $slug;
            $destination = rtrim($destinationDir, '/') . '/' . time() . "-{$finalName}.{$ext}";

            if (move_uploaded_file($tmp, $destination)) {
                if ($processImage) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);      // safe here — file already moved
                    $mime  = strtolower(finfo_file($finfo, $destination));
                    finfo_close($finfo);

                    if (str_starts_with($mime, 'image/')) {
                        $img = new Image($destination);
                        $img->save($destination);
                        $img->destroy();
                    }
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
     * @return string|null Error message or null if valid
     */
    protected function validate(array $file, array $options): ?string
    {
        $maxSize     = $options['maxsize'] ?? null;
        $allowedExt  = isset($options['extensions']) ? array_map('strtolower', $options['extensions']) : null;
        $allowedMime = isset($options['mimetypes']) ? array_map('strtolower', $options['mimetypes']) : null;

        $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $size = $file['size'] ?? 0;
        $tmp  = $file['tmp_name'] ?? '';

        if ($maxSize && ($size > (int) $maxSize)) {
            $maxSizeMB = (int) $maxSize / 1024 / 1024;
            return "File exceeds max size ({$maxSizeMB} MB)";
        }

        if ($allowedExt && !in_array($ext, $allowedExt)) {
            return "Extension .{$ext} not allowed";
        }

        if ($allowedMime && $tmp && is_file($tmp)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = strtolower(finfo_file($finfo, $tmp));
            finfo_close($finfo);

            if (!in_array($mime, $allowedMime)) {
                return "MIME type {$mime} not allowed";
            }
        }

        return null;
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
