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

class Upload
{
    // Fields
    /**
     * @var array<int,string> $fields
     */
    protected array $fields;

    /**
     * Initialize Fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Normalize Multiple Uploaded File
     * @return array
     */
    public function normalize(): array
    {
        if (!isset($this->fields['name'])) {
            return [];
        }

        if (!\is_array($this->fields['name'])) {
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
     * Upload Single File
     * @param string $directory Destination Directory
     * @param ?string $name File Name Without Extension
     * @return string|false
     */
    public function single(string $directory, ?string $name = null): string|false
    {
        if (!isset($this->fields['tmp_name']) || !\is_uploaded_file($this->fields['tmp_name'])) {
            return false;
        }

        // Make Directory if Not Exists
        Directory::make($directory);

        $originalName = \basename($this->fields['name']);
        $extension = \pathinfo($originalName, PATHINFO_EXTENSION);
        $name = $name ? "{$name}.{$extension}" : $originalName;

        $destination = \rtrim($directory, '/') . "/{$name}";

        return \move_uploaded_file($this->fields['tmp_name'], $destination) ? $destination : false;
    }

    /**
     * Upload Multiple File
     * @param string $destinationDir Destination Directory
     * @param array $options Optional Options. Example ['basename','maxsize','extensions','mimetypes']
     * @return array{'errors:array,success:array}
     */
    public function multiple(string $destinationDir, array $options = []): array
    {
        $results = ['success' => [], 'errors' => []];
        $files = $this->normalize();

        $baseName     = $options['basename'] ?? null;
        $maxSize      = $options['maxsize'] ?? null;
        $allowedExt   = isset($options['extensions']) ? \array_map('strtolower', $options['extensions']) : null;
        $allowedMime  = isset($options['mimetypes']) ? \array_map('strtolower', $options['mimetypes']) : null;
        $processImage = $options['image'] ?? false;

        if (!\is_dir($destinationDir)) {
            \mkdir($destinationDir, 0755, true);
        }

        foreach ($files as $index => $file) {
            $name = $file['name'] ?? 'file_' . $index;
            $size = $file['size'] ?? 0;
            $tmp  = $file['tmp_name'] ?? '';
            $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            // Get Extension
            $ext = \strtolower(\pathinfo($name, PATHINFO_EXTENSION));
            // Get Mime Info
            $mime = '';
            if ($tmp && \is_file($tmp)) {
                $finfo = \finfo_open(FILEINFO_MIME_TYPE);
                $mime  = \strtolower(\finfo_file($finfo, $tmp));
                \finfo_close($finfo);
            }
            // Get Slug
            $slug = $this->slugify(pathinfo($name, PATHINFO_FILENAME));

            if (empty($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip Blank/Empty Inputs
            }

            if (($error !== UPLOAD_ERR_OK) && $name) {
                $results['errors'][$name] = "Upload error: {$error}";
                continue;
            }

            if ($maxSize && ($size > (int) $maxSize)) {
                $maxSizeMB = (int) $maxSize / 1024 / 1024;
                $results['errors'][$name] = "File Exceeds Max Size ({$maxSizeMB} MB)";
                continue;
            }

            if ($allowedExt && !in_array($ext, $allowedExt)) {
                $results['errors'][$name] = "Extension .$ext not allowed";
                continue;
            }

            if ($allowedMime && !\in_array($mime, $allowedMime)) {
                $results['errors'][$name] = "MIME type $mime not allowed";
                continue;
            }

            $finalName = $baseName ? "{$baseName}_{$index}" : $slug;
            $destination = \rtrim($destinationDir, '/') . '/' . \time() ."-{$finalName}.{$ext}";

            if (move_uploaded_file($tmp, $destination)) {
                if ($processImage && \str_starts_with($mime, 'image/')) {
                    $img = new Image($destination);
                    $img->save($destination, 70);
                    $img->destroy();
                }

                $results['success'][$name] = ['slug' => \basename($destination), 'path' => $destination];
            } else {
                $results['errors'][$name] = "Could Not Move Uploaded File!";
            }
        }

        return $results;
    }

    /**
     * Make File Name as Slug
     * @param string $text Text to Make Slug
     * @return string
     */
    public function slugify(string $name): string
    {
        $parts = \explode('.', $name);
        $name = $parts[0];
        $name = \preg_replace('~[^\pL\d]+~u', '-', $name);
        $name = \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = \preg_replace('~[^-\w]+~', '', $name);
        $name = \trim($name, '-');
        $name = \preg_replace('~-+~', '-', $name);
        return \strtolower($name) ?: 'file-' . \uniqid() . '-' . \time();
    }
}
