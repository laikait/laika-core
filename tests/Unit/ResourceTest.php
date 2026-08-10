<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Laika\Core\App\Resource;
use Laika\Core\App\ResourceDefinition;
use Laika\Core\Exceptions\ResourceException;
use Laika\Tests\Fixtures\Resource\WidgetInterface;

final class ResourceTest extends TestCase
{
    private const NS = 'Laika\\Tests\\Fixtures\\Resource';

    private string $fixtures;

    protected function setUp(): void
    {
        // Every test starts from an empty registry, with declared resources suppressed
        Resource::isolate();
        $this->fixtures = realpath(__DIR__ . '/../Fixtures/Resource');
        $this->assertNotFalse($this->fixtures, 'Resource fixtures are missing.');
    }

    protected function tearDown(): void
    {
        Resource::flush();
    }

    public function testRegisterMapsFilesToClassNames(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');

        $this->assertSame(
            [self::NS . '\\Widget\\GoodWidget'],
            Resource::getResources('widgets')
        );
    }

    public function testRegisterWithoutNamespaceCollectsFilePaths(): void
    {
        Resource::register('samples', $this->fixtures . '/Files');
        $files = Resource::getFiles('samples');

        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertFileExists($file);
        }
    }

    /**
     * Nested directories must produce namespace separators on every platform.
     * The previous implementation left the directory separator in place, so on
     * Linux a two-level nesting produced "Ns\Nested/Deep\GammaModel".
     */
    public function testNestedDirectoriesProduceValidClassNames(): void
    {
        Resource::register('models', $this->fixtures . '/Model', self::NS . '\\Model');
        $classes = Resource::getResources('models');

        $this->assertContains(self::NS . '\\Model\\AlphaModel', $classes);
        $this->assertContains(self::NS . '\\Model\\Nested\\BetaModel', $classes);
        $this->assertContains(self::NS . '\\Model\\Nested\\Deep\\GammaModel', $classes);

        foreach ($classes as $class) {
            $this->assertStringNotContainsString('/', $class, "[{$class}] contains a directory separator.");
            $this->assertTrue(class_exists($class), "[{$class}] is not loadable.");
        }
    }

    public function testRegisteringTheSameLocationTwiceDoesNotDuplicate(): void
    {
        Resource::register('models', $this->fixtures . '/Model', self::NS . '\\Model');
        $first = Resource::getResources('models');

        Resource::register('models', $this->fixtures . '/Model', self::NS . '\\Model');
        $second = Resource::getResources('models');

        $this->assertSame($first, $second);
        $this->assertSame(count($first), count(array_unique($first)));
    }

    public function testSeparateLocationsForOneNameAreMerged(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        Resource::register('widgets', $this->fixtures . '/Broken', self::NS . '\\Broken');

        $classes = Resource::getResources('widgets');

        $this->assertCount(2, $classes);
        $this->assertContains(self::NS . '\\Widget\\GoodWidget', $classes);
        $this->assertContains(self::NS . '\\Broken\\BadWidget', $classes);
    }

    public function testMissingDirectoryResolvesToNothingInsteadOfThrowing(): void
    {
        Resource::register('ghosts', $this->fixtures . '/DoesNotExist', self::NS . '\\Ghost');

        $this->assertTrue(Resource::has('ghosts'));
        $this->assertSame([], Resource::getResources('ghosts'));

        $definition = Resource::definitions('ghosts')[0];
        $this->assertFalse($definition->exists());
    }

    public function testGetClassesEnforcesTheDeclaredContract(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget', WidgetInterface::class);

        $this->assertSame(
            [self::NS . '\\Widget\\GoodWidget'],
            Resource::getClasses('widgets')
        );
    }

    public function testGetClassesRejectsAClassThatFailsTheContract(): void
    {
        Resource::register('widgets', $this->fixtures . '/Broken', self::NS . '\\Broken', WidgetInterface::class);

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('BadWidget');
        Resource::getClasses('widgets');
    }

    public function testGetClassesReportsAFileWhoseClassNameDoesNotMatch(): void
    {
        Resource::register('mismatched', $this->fixtures . '/Mismatch', self::NS . '\\Mismatch');

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('WrongName');
        Resource::getClasses('mismatched');
    }

    public function testGetClassesRejectsFileResources(): void
    {
        Resource::register('samples', $this->fixtures . '/Files');

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('file paths');
        Resource::getClasses('samples');
    }

    public function testInvalidNameIsRejected(): void
    {
        $this->expectException(ResourceException::class);
        Resource::register('not a name', $this->fixtures . '/Files');
    }

    public function testNameMayContainDigitsAndUnderscores(): void
    {
        Resource::register('view_files2', $this->fixtures . '/Files');
        $this->assertTrue(Resource::has('view_files2'));
    }

    public function testNamespaceMayContainDigits(): void
    {
        Resource::register('versioned', $this->fixtures . '/Widget', 'App\\V2\\Widget');

        $this->assertSame(['App\\V2\\Widget\\GoodWidget'], Resource::getResources('versioned'));
    }

    public function testInvalidNamespaceIsRejected(): void
    {
        $this->expectException(ResourceException::class);
        Resource::register('widgets', $this->fixtures . '/Widget', 'App/Widget');
    }

    public function testLegacyControllerAliasResolvesToControllers(): void
    {
        Resource::register('controller', $this->fixtures . '/Widget', self::NS . '\\Widget');

        $this->assertSame(
            Resource::getResources('controllers'),
            Resource::getResources('controller')
        );
    }

    public function testNamesAndDefinitionsDescribeTheRegistry(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        Resource::register('samples', $this->fixtures . '/Files');

        $this->assertSame(['samples', 'widgets'], Resource::names());
        $this->assertTrue(Resource::isClassMap('widgets'));
        $this->assertFalse(Resource::isClassMap('samples'));

        $definition = Resource::definitions('widgets')[0];
        $this->assertInstanceOf(ResourceDefinition::class, $definition);
        $this->assertSame('widgets', $definition->name);
        $this->assertSame('runtime', $definition->source);
    }

    public function testEntriesReportWhatOneLocationContributes(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        Resource::register('widgets', $this->fixtures . '/Broken', self::NS . '\\Broken');

        foreach (Resource::definitions('widgets') as $definition) {
            $this->assertCount(1, Resource::entries($definition));
        }
    }

    public function testFlushForgetsASingleResource(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        Resource::register('samples', $this->fixtures . '/Files');

        Resource::flush('widgets');

        $this->assertFalse(Resource::has('widgets'));
        $this->assertTrue(Resource::has('samples'));
    }

    public function testRegisteringAfterReadingInvalidatesTheMemo(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        $this->assertCount(1, Resource::getResources('widgets'));

        Resource::register('widgets', $this->fixtures . '/Broken', self::NS . '\\Broken');
        $this->assertCount(2, Resource::getResources('widgets'));
    }

    public function testManifestRoundTrip(): void
    {
        Resource::register('widgets', $this->fixtures . '/Widget', self::NS . '\\Widget');
        Resource::register('samples', $this->fixtures . '/Files');

        $expected = Resource::getResources();
        $file = sys_get_temp_dir() . '/laika-resource-manifest-' . getmypid() . '.php';

        try {
            Resource::cache($file);
            $this->assertFileExists($file);

            Resource::isolate();
            $this->assertTrue(Resource::loadManifest($file));

            $this->assertSame($expected, Resource::getResources());
            $this->assertSame('widgets', Resource::definitions('widgets')[0]->name);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testLoadManifestReportsAMissingFile(): void
    {
        $this->assertFalse(Resource::loadManifest(sys_get_temp_dir() . '/laika-no-such-manifest.php'));
    }
}
