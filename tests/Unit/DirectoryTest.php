<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\Helper\Directory;
use RuntimeException;

final class DirectoryTest extends TestCase
{
    private Directory $dir;

    /** Fixture root, recreated per test. */
    private string $root;

    protected function setUp(): void
    {
        $this->dir  = new Directory();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laika_dir_' . bin2hex(random_bytes(6));

        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        // Native teardown only - never the class under test.
        $this->rmTree($this->root);
    }

    // -----------------------------------------------------------------------
    // pop() / empty()
    // -----------------------------------------------------------------------

    /**
     * empty() used to recurse into a subdirectory and delete its contents
     * without removing the directory, so the caller's rmdir() then failed on a
     * parent that still held empty children. That aborted `php laika app:sync`,
     * which pops the compiled-template cache parent-first.
     */
    public function testPopRemovesANestedTree(): void
    {
        mkdir($this->root . '/template/92', 0777, true);
        file_put_contents($this->root . '/template/92/compiled.php', '<?php');
        file_put_contents($this->root . '/template/root.php', '<?php');

        $this->assertTrue($this->dir->pop($this->root . '/template'));
        $this->assertDirectoryDoesNotExist($this->root . '/template');
    }

    public function testEmptyClearsChildrenButKeepsTheDirectory(): void
    {
        mkdir($this->root . '/cache/sub', 0777, true);
        file_put_contents($this->root . '/cache/a.php', '<?php');
        file_put_contents($this->root . '/cache/sub/b.php', '<?php');

        $this->assertTrue($this->dir->empty($this->root . '/cache'));

        $this->assertDirectoryExists($this->root . '/cache');
        $this->assertDirectoryDoesNotExist($this->root . '/cache/sub');
        $this->assertFileDoesNotExist($this->root . '/cache/a.php');
    }

    public function testPopRejectsAMissingDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->dir->pop($this->root . '/nope');
    }

    /**
     * A link inside the tree must be removed as a link. The original code took
     * the is_file() === false branch and recursed through it, deleting the
     * target's files - data loss outside the tree being cleared.
     */
    public function testEmptyRemovesALinkWithoutTouchingItsTarget(): void
    {
        $outside = $this->root . DIRECTORY_SEPARATOR . 'outside';
        $victim  = $this->root . DIRECTORY_SEPARATOR . 'victim';
        $link    = $victim . DIRECTORY_SEPARATOR . 'link';

        mkdir($outside, 0777, true);
        mkdir($victim, 0777, true);
        file_put_contents($outside . DIRECTORY_SEPARATOR . 'SENTINEL.txt', 'must survive');

        if (!$this->makeDirLink($outside, $link)) {
            $this->markTestSkipped('This platform cannot create a directory link without elevation.');
        }

        $this->dir->empty($victim);

        $this->assertFileExists($outside . DIRECTORY_SEPARATOR . 'SENTINEL.txt', 'Deleted outside the tree.');
        $this->assertDirectoryExists($outside);
        $this->assertFileDoesNotExist($link, 'The link itself should be gone.');
    }

    // -----------------------------------------------------------------------
    // files()
    // -----------------------------------------------------------------------

    /** join(',') produced "*.php,json"; brace expansion needs GLOB_BRACE, so this returned nothing. */
    public function testFilesAcceptsSeveralExtensions(): void
    {
        $this->write(['a.php', 'b.json', 'c.md', 'd.txt']);

        $this->assertSame(
            ['a.php', 'b.json'],
            $this->names($this->dir->files($this->root, ['php', 'json'])),
            'Multiple extensions must all match.'
        );
    }

    /** glob("*.*") only matched names containing a dot, contradicting the docblock. */
    public function testStarReturnsEveryFileIncludingExtensionless(): void
    {
        $this->write(['a.php', 'LICENSE', 'worker']);

        $this->assertSame(['LICENSE', 'a.php', 'worker'], $this->names($this->dir->files($this->root, '*')));
    }

    public function testFilesIsCaseInsensitiveAndToleratesALeadingDot(): void
    {
        $this->write(['a.php', 'B.PHP']);

        $this->assertCount(2, $this->dir->files($this->root, 'PHP'));
        $this->assertCount(2, $this->dir->files($this->root, '.php'));
    }

    /** glob() matched any name ending in the extension, directories included. */
    public function testFilesNeverReturnsADirectory(): void
    {
        mkdir($this->root . '/looks_like.php');
        $this->write(['real.php']);

        $this->assertSame(['real.php'], $this->names($this->dir->files($this->root, 'php')));
    }

    public function testFoldersReturnsOnlyDirectories(): void
    {
        mkdir($this->root . '/one');
        mkdir($this->root . '/two');
        $this->write(['file.php']);

        $this->assertSame(['one', 'two'], $this->names($this->dir->folders($this->root)));
    }

    // -----------------------------------------------------------------------
    // scan()
    // -----------------------------------------------------------------------

    /** Array extensions were lower-cased, string ones were not, so 'PHP' matched nothing. */
    public function testScanExtensionFilterIsCaseInsensitive(): void
    {
        mkdir($this->root . '/nested');
        $this->write(['a.php']);
        file_put_contents($this->root . '/nested/b.php', '<?php');

        $lower = $this->dir->scan($this->root, false, 'php');

        $this->assertCount(2, $lower);
        $this->assertCount(2, $this->dir->scan($this->root, false, 'PHP'));
        $this->assertCount(2, $this->dir->scan($this->root, false, ['PHP']));
    }

    /** An empty filter is "no filter", not "match nothing". */
    public function testScanTreatsAnEmptyExtensionListAsEverything(): void
    {
        $this->write(['a.php', 'b.txt']);

        $this->assertCount(2, $this->dir->scan($this->root, false, []));
        $this->assertCount(2, $this->dir->scan($this->root, false, '*'));
    }

    /** Directories bypass the extension filter by design; AppSyncCommand relies on it. */
    public function testScanReturnsDirectoriesRegardlessOfTheFilter(): void
    {
        mkdir($this->root . '/nested');
        $this->write(['a.php']);

        $this->assertCount(2, $this->dir->scan($this->root, true, 'php'));
        $this->assertCount(1, $this->dir->scan($this->root, false, 'php'));
    }

    // -----------------------------------------------------------------------
    // Diagnostics
    // -----------------------------------------------------------------------

    /**
     * realpath() was assigned over $path before the message was built, so a bad
     * path reported "Invalid Directory: []" - or "[}]", via a brace typo.
     */
    public function testErrorNamesThePathTheCallerPassed(): void
    {
        $missing = $this->root . DIRECTORY_SEPARATOR . 'not-here';

        foreach (['folders', 'files', 'scan'] as $method) {
            try {
                $this->dir->{$method}($missing);
                $this->fail("{$method}() should reject a missing directory.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($missing, $e->getMessage(), $method);
            }
        }
    }

    public function testMakeIsIdempotent(): void
    {
        $path = $this->root . '/a/b/c';

        $this->assertTrue($this->dir->make($path));
        $this->assertTrue($this->dir->make($path), 'An existing directory is success, not failure.');
        $this->assertDirectoryExists($path);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** @param string[] $names */
    private function write(array $names): void
    {
        foreach ($names as $name) {
            file_put_contents($this->root . DIRECTORY_SEPARATOR . $name, 'x');
        }
    }

    /**
     * Basenames, sorted, so assertions do not depend on the fixture path.
     * @param string[] $paths
     * @return string[]
     */
    private function names(array $paths): array
    {
        $names = array_map(static fn (string $p): string => basename($p), $paths);
        sort($names);
        return $names;
    }

    /**
     * A symlink needs elevation on Windows; a directory junction does not.
     * Returns false when neither is available.
     */
    private function makeDirLink(string $target, string $link): bool
    {
        try {
            if (@symlink($target, $link)) {
                return true;
            }
        } catch (\Throwable) {
            // Fall through to the Windows junction below.
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            return false;
        }

        exec('cmd /c mklink /J ' . escapeshellarg($link) . ' ' . escapeshellarg($target) . ' 2>&1', $out, $code);

        return $code === 0 && file_exists($link);
    }

    private function rmTree(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }

        if (is_link($path) || (!is_dir($path) && !is_file($path))) {
            if (!@unlink($path)) {
                @rmdir($path);
            }
            return;
        }

        if (is_file($path)) {
            @unlink($path);
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $this->rmTree($path . DIRECTORY_SEPARATOR . $entry);
        }

        @rmdir($path);
    }
}
