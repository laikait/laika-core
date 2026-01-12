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
use ZipArchive;

class Zip
{
    /**
     * Path of ZIP Archive
     * @var string $path
     */
    protected string $path;

    ########################################################################
    ## --------------------------- PUBLIC API --------------------------- ##
    ########################################################################
    public function __construct(string $path)
    {
        $dir = \dirname($path);

        // Check Valid File if File Exists
        if (\file_exists($path) && !\is_file($path)) {
            throw new InvalidArgumentException("Path [{$path}] is not a valid file!");
        }

        if (!\is_dir($dir) || !\is_writable($dir)) {
            throw new InvalidArgumentException("Directory [{$dir}] is not writable!");
        }

        $this->path = $path;
    }

    /**
     * Create ZIP Archive
     * @param string|array<int|string> $files Directory or list of files to include in the archive.
     */
    public function create(string|array $files): bool
    {
        // Get Base Directory
        $baseDir = null;

        // If a directory was passed, expand to full file list
        if (\is_string($files) && \is_dir($files)) {
            $baseDir = \realpath($files);
            $files = $this->scanRecursive($files);
        }

        $zip = new ZipArchive();
        // Open ZIP Archive
        if ($zip->open($this->path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($files as $file) {
            // Throw Exception if File Doesn't Exists
            if (!\file_exists($file)) {
                throw new InvalidArgumentException("Invalid Path '{$file}' Detected!");
            }

            $localName = $baseDir ? \substr($file, \strlen($baseDir) + 1) : \basename($file);

            // Add File in Archive
            $zip->addFile($file, $localName);
        }

        // Close ZIP Archive
        $zip->close();

        return true;
    }

    /**
     * Extracts the archive to the given directory.
     * @param string $to Archive File Path.
     * @return bool
     */
    public function extract(string $to): bool
    {
        if (!Directory::make($to)) {
            throw new InvalidArgumentException("Unable to create extract directory [{$to}]!");
        }

        $zip = new ZipArchive();
        if ($zip->open($this->path) === true) {
            $zip->extractTo($to);
            $zip->close();
            return true;
        }
        return false;
    }

    ########################################################################
    ## -------------------------- INTERNAL API -------------------------- ##
    ########################################################################

    /**
     * Fallback recursive scanner (in case CBM\Core\Directory is not available).
     * @param string $dir
     * @return array
     */
    protected function scanRecursive(string $dir): array
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
