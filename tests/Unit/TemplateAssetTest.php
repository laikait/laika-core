<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use Laika\Core\Template\Asset;
use PHPUnit\Framework\TestCase;

/**
 * A URL carrying "?v=" is served immutable for a year, so the version has to
 * track the file it points at. A version that does not -- the literal '1.0.0'
 * this used to emit -- pins the stale copy for that long.
 */
final class TemplateAssetTest extends TestCase
{
    /** @var string Fixture path relative to APP_PATH */
    private string $relative;

    /** @var string Absolute path of the same file */
    private string $absolute;

    protected function setUp(): void
    {
        $dir = APP_PATH . '/lf-storage';

        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        $name = 'asset-test-' . bin2hex(random_bytes(6)) . '.css';

        $this->relative = 'lf-storage/' . $name;
        $this->absolute = $dir . '/' . $name;

        file_put_contents($this->absolute, 'body{color:#000}');
    }

    protected function tearDown(): void
    {
        if (is_file($this->absolute)) {
            unlink($this->absolute);
        }
    }

    /*============================== version() ==============================*/

    public function testVersionIsTheHexModificationTime(): void
    {
        self::assertSame(dechex(filemtime($this->absolute)), Asset::version($this->relative));
    }

    public function testVersionChangesWhenTheFileChanges(): void
    {
        $before = Asset::version($this->relative);

        touch($this->absolute, time() + 60);
        clearstatcache(true, $this->absolute);

        self::assertNotSame($before, Asset::version($this->relative));
    }

    public function testVersionIsEmptyForAFileThatIsNotThere(): void
    {
        // Empty, not a made-up value: the caller then omits ?v= rather than
        // pinning a wrong version for a year
        self::assertSame('', Asset::version('lf-storage/no-such-file.css'));
    }

    public function testVersionIsEmptyForAnExternalUrl(): void
    {
        self::assertSame('', Asset::version('https://cdn.example.com/app.css'));
    }

    public function testVersionIsEmptyForAnEmptyPath(): void
    {
        self::assertSame('', Asset::version(''));
    }

    /** @dataProvider equivalentPaths */
    public function testVersionIgnoresLeadingSlashesDotsAndQueries(string $suffix, string $prefix): void
    {
        $expected = Asset::version($this->relative);

        self::assertNotSame('', $expected);
        self::assertSame($expected, Asset::version($prefix . $this->relative . $suffix));
    }

    public static function equivalentPaths(): array
    {
        return [
            'leading slash' => ['', '/'],
            'dot relative'  => ['', './'],
            'with a query'  => ['?x=1', ''],
            'both'          => ['?x=1', '/'],
        ];
    }

    /*=========================== appendVersion() ===========================*/

    public function testAppendVersionUsesAQuestionMarkOnAPlainUrl(): void
    {
        self::assertSame('https://x.test/a.css?v=abc', Asset::appendVersion('https://x.test/a.css', 'abc'));
    }

    public function testAppendVersionUsesAnAmpersandWhenAQueryIsAlreadyThere(): void
    {
        // Concatenating "?v=" unconditionally produced "...?a=1?v=abc"
        self::assertSame(
            'https://x.test/a.css?a=1&v=abc',
            Asset::appendVersion('https://x.test/a.css?a=1', 'abc')
        );
    }

    public function testAppendVersionLeavesTheUrlAloneWhenThereIsNoVersion(): void
    {
        self::assertSame('https://x.test/a.css', Asset::appendVersion('https://x.test/a.css', ''));
    }

    /*============================ enqueue path =============================*/

    public function testAnOmittedVersionIsDerivedFromTheFile(): void
    {
        $handle = 'derived' . bin2hex(random_bytes(4));

        Asset::addStyle($handle, $this->relative);

        self::assertStringContainsString(
            '?v=' . Asset::version($this->relative) . '"',
            $this->render()
        );
    }

    public function testAnExplicitVersionStillWins(): void
    {
        $handle = 'explicit' . bin2hex(random_bytes(4));

        Asset::addStyle($handle, $this->relative, '2.1.0');

        self::assertStringContainsString('?v=2.1.0"', $this->render());
    }

    public function testAMissingFileFallsBackRatherThanDroppingTheVersion(): void
    {
        $handle = 'missing' . bin2hex(random_bytes(4));

        Asset::addStyle($handle, 'lf-storage/no-such-file.css');

        // The tag has always carried a version; keep its shape stable
        self::assertStringContainsString('?v=1.0.0"', $this->render());
    }

    public function testAnExternalSourceWithAQueryIsNotMalformed(): void
    {
        $handle = 'external' . bin2hex(random_bytes(4));

        Asset::addScript($handle, 'https://cdn.example.com/lib.js?a=1');

        $out = $this->renderScripts();

        self::assertStringContainsString('lib.js?a=1&amp;v=', $out);
        self::assertStringNotContainsString('?a=1?v=', $out);
    }

    /** Styles accumulate across tests, so assertions look for their own handle */
    private function render(): string
    {
        ob_start();
        Asset::printStyles();

        return (string) ob_get_clean();
    }

    private function renderScripts(): string
    {
        ob_start();
        Asset::printScripts();

        return (string) ob_get_clean();
    }
}
