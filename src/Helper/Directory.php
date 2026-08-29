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

use RuntimeException;
use Throwable;

class Directory
{
    /**
     * Directories List From Directory
     * @param string $path Directory path
     * @return string[] Absolute paths, sorted
     * @throws RuntimeException
     */
    public function folders(string $path): array
    {
        $real  = $this->realDirectory($path);
        $found = [];

        foreach ($this->entries($real) as $entry) {
            $full = $real . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $found[] = $full;
            }
        }

        sort($found);
        return $found;
    }

    /**
     * Files List From Directory
     *
     * Not recursive; use scan() for that. A directory is never returned, even
     * when its name carries a matching extension.
     * @param string $path Directory path
     * @param string|array $ext Extension(s) to keep, e.g. 'php' or ['php','json'].
     *                          '*' (the default) means every file, including one
     *                          with no extension at all.
     * @return string[] Absolute paths, sorted
     * @throws RuntimeException
     */
    public function files(string $path, string|array $ext = '*'): array
    {
        $real    = $this->realDirectory($path);
        $extList = $this->extensions($ext);
        $found   = [];

        foreach ($this->entries($real) as $entry) {
            $full = $real . DIRECTORY_SEPARATOR . $entry;

            if (!is_file($full)) {
                continue;
            }

            if ($extList === [] || $this->hasExtension($entry, $extList)) {
                $found[] = $full;
            }
        }

        sort($found);
        return $found;
    }

    /**
     * Check Directory Exists
     * @param string $path Directory Path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Make Directory
     * @param string $path Directory Path
     * @param int $permissions Directory Permission. Default is 0755
     * @param bool $recursive Make Recursive Paths. Default is true
     * @return bool
     * @throws RuntimeException
     */
    public function make(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        try {
            $made = mkdir($path, $permissions, $recursive);
        } catch (Throwable $th) {
            // A parallel process may have created it between the check and the
            // call. The directory existing is the outcome that was asked for.
            if (is_dir($path)) {
                return true;
            }
            throw new RuntimeException("Unable To Make Directory: [{$path}]. {$th->getMessage()}", (int) $th->getCode(), $th);
        }

        return $made || is_dir($path);
    }

    /**
     * Delete a Directory And Everything Inside It
     * @param string $path
     * @return bool
     * @throws RuntimeException
     */
    public function pop(string $path): bool
    {
        // Remove a link as a link. Following it would delete the contents of the
        // target, which lives outside the tree the caller asked to remove.
        if ($this->isLinkEntry($path)) {
            return $this->unlinkLink($path);
        }

        if (!$this->exists($path)) {
            throw new RuntimeException("Invalid Directory: [{$path}]");
        }

        $this->empty($path);

        try {
            $removed = rmdir($path);
        } catch (Throwable $th) {
            throw new RuntimeException("Unable To Remove Directory: [{$path}]. {$th->getMessage()}", (int) $th->getCode(), $th);
        }

        if (!$removed) {
            throw new RuntimeException("Unable To Remove Directory: [{$path}]");
        }

        return true;
    }

    /**
     * Remove Everything Inside a Directory, Keeping The Directory Itself
     * @param string $path
     * @return bool
     * @throws RuntimeException
     */
    public function empty(string $path): bool
    {
        if (!$this->exists($path)) {
            throw new RuntimeException("Invalid Directory: [{$path}]");
        }

        foreach ($this->entries($path) as $entry) {
            $full = $path . DIRECTORY_SEPARATOR . $entry;

            try {
                if ($this->isLinkEntry($full)) {
                    $this->unlinkLink($full);
                } elseif (is_dir($full)) {
                    // pop(), not empty(): emptying alone leaves the subdirectory
                    // behind, and the caller's rmdir() then fails on a parent
                    // that still holds empty children.
                    $this->pop($full);
                } elseif (!unlink($full)) {
                    throw new RuntimeException("Unable To Delete File: [{$full}]");
                }
            } catch (Throwable $th) {
                throw new RuntimeException("Unable To Empty [{$full}]. {$th->getMessage()}", (int) $th->getCode(), $th);
            }
        }

        return true;
    }

    /**
     * Recursively scans a directory.
     *
     * The extension filter applies to files only. With $includeDirs = true every
     * directory is returned regardless of $ext, and a parent is yielded before
     * its children.
     * @param string $path Directory path
     * @param bool $includeDirs Whether to include directories in the result
     * @param string|array $ext Extension(s) to keep, e.g. 'php' or ['php','json'].
     *                          '*' (the default) means every file.
     * @return string[]
     * @throws RuntimeException
     */
    public function scan(string $path, bool $includeDirs = true, string|array $ext = '*'): array
    {
        $real    = $this->realDirectory($path);
        $extList = $this->extensions($ext);
        $result  = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if ($includeDirs) {
                    $result[] = $item->getPathname();
                }
                continue;
            }

            if ($extList === [] || $this->hasExtension($item->getFilename(), $extList)) {
                $result[] = $item->getPathname();
            }
        }

        return $result;
    }

    ##########################################################################
    /*============================ INTERNAL API ============================*/
    ##########################################################################

    /**
     * Resolve a path, reporting the string the caller actually passed.
     *
     * realpath() returns false for a bad path, so assigning it over $path first
     * left the message reading "Invalid Directory: []".
     * @param string $path
     * @return string
     * @throws RuntimeException
     */
    private function realDirectory(string $path): string
    {
        $real = realpath($path);

        if ($real === false || !is_dir($real)) {
            throw new RuntimeException("Invalid Directory: [{$path}]");
        }

        return $real;
    }

    /**
     * Directory entries without the dot entries.
     * @param string $path
     * @return string[]
     * @throws RuntimeException
     */
    private function entries(string $path): array
    {
        $entries = scandir($path);

        if ($entries === false) {
            throw new RuntimeException("Unable To Read Directory: [{$path}]");
        }

        return array_diff($entries, ['.', '..']);
    }

    /**
     * Normalise an extension filter.
     *
     * Accepts a string or an array, trims, strips a leading dot and lower-cases,
     * so 'PHP', '.php' and 'php' all behave alike. An empty list or '*' means
     * "no filter" and comes back as [].
     * @param string|array $ext
     * @return string[]
     */
    private function extensions(string|array $ext): array
    {
        $list = [];

        foreach ((array) $ext as $item) {
            $item = strtolower(ltrim(trim((string) $item), '.'));

            if ($item === '') {
                continue;
            }

            if ($item === '*') {
                return [];
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Whether a filename carries one of the given extensions.
     * @param string $name
     * @param string[] $extList Already normalised by extensions()
     * @return bool
     */
    private function hasExtension(string $name, array $extList): bool
    {
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extList, true);
    }

    /**
     * Whether an entry must be removed as a link rather than descended into.
     *
     * is_link() covers a POSIX symlink. A Windows directory junction stats as
     * neither link, dir nor file on some builds - all three return false - so
     * anything that exists while being neither a real directory nor a real file
     * is treated the same way. Descending into either would delete the target's
     * contents, which sit outside the tree being removed.
     * @param string $path
     * @return bool
     */
    private function isLinkEntry(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }

        return file_exists($path) && !is_dir($path) && !is_file($path);
    }

    /**
     * Remove a link itself, never what it points at.
     *
     * unlink() covers a POSIX symlink, including one aimed at a directory;
     * Windows needs rmdir() for a directory symlink or junction. Each is tried
     * in turn because an error handler that promotes warnings to exceptions
     * would otherwise abort on the first attempt.
     * @param string $path
     * @return bool
     * @throws RuntimeException
     */
    private function unlinkLink(string $path): bool
    {
        foreach (['unlink', 'rmdir'] as $remove) {
            try {
                if (@$remove($path)) {
                    return true;
                }
            } catch (Throwable) {
                // Wrong call for this platform or link type; try the other.
            }
        }

        throw new RuntimeException("Unable To Remove Link: [{$path}]");
    }
}
