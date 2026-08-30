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

use ZipArchive;
use Laika\Service\Directory;
use Laika\Core\Exceptions\{ExtensionException, PathException};

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
        // Check ZIP Extension Loaded
        if (!extension_loaded('zip')) {
            throw new ExtensionException('Extension Not Loaded: [php-zip]!', 500);
        }

        // Check Valid File if File Exists
        if (file_exists($path) && !is_file($path)) {
            throw new PathException("Path [{$path}] is not a valid file!");
        }

        // Writability is create()'s concern, not extract()'s.
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

        $dir = dirname($this->path);

        // Checked here rather than in the constructor, which demanded a
        // writable parent even for a read-only extract().
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new PathException("Directory [{$dir}] is not writable!");
        }

        // If a directory was passed, expand to full file list
        if (is_string($files) && is_dir($files)) {
            $baseDir = realpath($files);
            $files = Directory::scan($files, false);
        }

        $zip = new ZipArchive();
        // Open ZIP Archive
        if ($zip->open($this->path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $seen = [];

        foreach ((array) $files as $file) {
            // Throw Exception if File Doesn't Exists
            if (!is_file($file)) {
                $zip->close();
                throw new PathException("Invalid Path '{$file}' Detected!");
            }

            $localName = $baseDir
                ? ltrim(str_replace('\\', '/', substr((string) realpath($file), strlen($baseDir))), '/')
                : basename($file);

            // Without a base directory every entry flattens to its basename, so
            // two same-named files would silently overwrite each other.
            if (isset($seen[$localName])) {
                $zip->close();
                throw new PathException("Duplicate archive entry [{$localName}].");
            }
            $seen[$localName] = true;

            // Add File in Archive
            if (!$zip->addFile($file, $localName)) {
                $zip->close();
                throw new PathException("Unable to add [{$file}] to the archive.");
            }
        }

        // Close ZIP Archive. Its return value is the only signal that the
        // archive actually reached disk intact.
        return $zip->close();
    }

    /**
     * Extracts the archive to the given directory.
     * @param string $to Archive File Path.
     * @param int $maxBytes Max Allowed Bytes. Default is 536870912 [512MB]
     * @param int $maxEntries Max Allowed Files. Default is 10000
     * @return bool
     */
    public function extract(string $to, int $maxBytes = 536870912, int $maxEntries = 10000): bool
    {
        if (!Directory::make($to)) {
            throw new PathException("Unable to create extract directory [{$to}]!");
        }

        $zip = new ZipArchive();
        if ($zip->open($this->path) !== true) {
            return false;
        }

        if ($zip->numFiles > $maxEntries) {
            $zip->close();
            throw new PathException("Archive holds {$zip->numFiles} entries, over the {$maxEntries} limit.");
        }

        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            // extractTo() already refuses absolute paths and traversal; this is
            // belt and braces, and cheap.
            $name = str_replace('\\', '/', (string) $stat['name']);

            if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                $zip->close();
                throw new PathException("Refusing unsafe archive entry [{$stat['name']}].");
            }

            // The real gap: nothing bounded how far a small archive could expand.
            $total += (int) $stat['size'];

            if ($total > $maxBytes) {
                $zip->close();
                throw new PathException("Archive expands past the {$maxBytes} byte limit.");
            }
        }

        $zip->extractTo($to);
        $zip->close();

        return true;
    }
}
