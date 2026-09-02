<?php

declare(strict_types=1);

namespace Laika\Tests\Unit;

use Throwable;
use RuntimeException;
use Laika\Relay\Relay;
use Laika\Route\Handler;
use Laika\Core\Nav\Builder;
use InvalidArgumentException;
use Laika\Relay\RelayRegistry;
use Laika\Core\Nav\Helper\Item;
use PHPUnit\Framework\TestCase;
use Laika\Core\Helper\Url as UrlHelper;

final class NavTest extends TestCase
{
    /**
     * The suite runs against a subdirectory install on purpose. A document-root
     * install hides the whole class of base-path bugs, because there the
     * resolved URL and the raw path happen to be identical.
     */
    private const BASE = 'http://localhost/laika-project/';

    private Builder $nav;

    /** @var RelayRegistry|null Registry in place before this class ran. */
    private static ?RelayRegistry $previousRegistry = null;

    public static function setUpBeforeClass(): void
    {
        $_SERVER['HTTP_HOST']    = 'localhost';
        $_SERVER['SCRIPT_NAME']  = '/laika-project/index.php';
        $_SERVER['REQUEST_URI']  = '/laika-project/';
        $_SERVER['QUERY_STRING'] = '';

        // helpers/loader.php bootstraps a registry during autoload, so hand it
        // back untouched when this class is done.
        try {
            self::$previousRegistry = Relay::getRegistry();
        } catch (Throwable) {
            self::$previousRegistry = null;
        }

        self::registry();

        // Names are prefixed and registration is idempotent: the route table is
        // static, shared with every other test class, and never reset.
        $routes = [
            'nav.home'      => '/',
            'nav.about'     => '/about',
            'nav.services'  => '/services',
            'nav.help'      => '/services/help',
            'nav.other'     => '/services/other',
            'nav.etc1'      => '/services/other/etc1',
            'nav.etc2'      => '/services/other/etc2',
            'nav.admin'     => '/admin',
            'nav.staff'     => '/admin/staff',
            'nav.logs'      => '/admin/logs',
            'nav.old'       => '/services-old/help',
            'nav.post'      => '/post/{id}',
            'nav.q'         => '/a',
        ];

        foreach ($routes as $name => $uri) {
            if (isset(Handler::getNamedRoutes()[$name])) {
                continue;
            }

            Handler::get($uri, static fn (): mixed => null);
            Handler::name($name, 'GET', $uri);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$previousRegistry !== null) {
            Relay::swapRegistry(self::$previousRegistry);
        }
    }

    protected function setUp(): void
    {
        self::registry();

        $this->nav = new Builder();

        // Active detection off by default. Tests that exercise it opt in.
        $this->nav->current(null);
    }

    /** Bind a live Url helper for the current $_SERVER into a fresh registry. */
    private static function registry(): void
    {
        $registry = new RelayRegistry();
        $registry->instance('url', new UrlHelper());

        Relay::swapRegistry($registry);
    }

    /** Absolute URL a named route resolves to. */
    private function url(string $path = ''): string
    {
        return self::BASE . ltrim($path, '/');
    }

    // -----------------------------------------------------------------------
    // Named route resolution
    // -----------------------------------------------------------------------

    public function testNamedRouteResolvesToAnAbsoluteUrl(): void
    {
        $this->nav->add('Services', 'nav.services');

        $this->assertSame($this->url('services'), $this->nav->items()[0]->getUrl());
    }

    public function testLeadingSlashesAreTolerated(): void
    {
        $this->nav->add('Services', '/nav.services/');

        $this->assertSame($this->url('services'), $this->nav->items()[0]->getUrl());
    }

    public function testRouteParametersAreSubstituted(): void
    {
        $this->nav->add('Post', 'nav.post', ['id' => 7]);

        $this->assertSame($this->url('post/7'), $this->nav->items()[0]->getUrl());
    }

    public function testQueryStringSurvivesResolution(): void
    {
        $this->nav->add('Query', 'nav.q?x=1&y=2');

        $this->assertSame($this->url('a?x=1&y=2'), $this->nav->items()[0]->getUrl());
    }

    public function testChildrenResolveNamedRoutesToo(): void
    {
        $this->nav->add('Services', 'nav.services')->child('Help', 'nav.help');

        $child = $this->nav->items()[0]->getChildren()[0];

        $this->assertSame($this->url('services/help'), $child->getUrl());
    }

    public function testUnknownRouteNameThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Named route 'nope' not found.");

        $this->nav->add('Nope', 'nope');
    }

    /**
     * @dataProvider finalTargets
     */
    public function testAlreadyFinalTargetsPassThroughUntouched(string $target): void
    {
        $this->nav->add('Link', $target);

        $this->assertSame($target, $this->nav->items()[0]->getUrl());
    }

    public static function finalTargets(): array
    {
        return [
            'absolute'          => ['https://example.com/docs'],
            'protocol relative' => ['//cdn.example.com/app.js'],
            'mailto'            => ['mailto:someone@example.com'],
            'tel'               => ['tel:+15550100'],
            'fragment'          => ['#top'],
        ];
    }

    // -----------------------------------------------------------------------
    // Structure
    // -----------------------------------------------------------------------

    public function testRendersWrapperAndTopLevelItems(): void
    {
        $this->nav->add('Home', 'nav.home');
        $this->nav->add('About', 'nav.about');

        $this->assertSame(
            '<nav class="navbar" aria-label="Main">'
            . '<ul class="nav-menu">'
            . '<li class="nav-item"><a href="' . $this->url() . '" class="nav-link">Home</a></li>'
            . '<li class="nav-item"><a href="' . $this->url('about') . '" class="nav-link">About</a></li>'
            . '</ul>'
            . '</nav>',
            $this->nav->render()
        );
    }

    public function testEmptyNavRendersWrapperWithoutList(): void
    {
        $this->assertSame('<nav class="navbar" aria-label="Main"></nav>', $this->nav->render());
    }

    public function testNoIdIsEmittedUnlessConfigured(): void
    {
        $this->nav->add('Home', 'nav.home');

        $this->assertStringNotContainsString('id=', $this->nav->render());

        $this->nav->configure(['id' => 'primary']);

        $this->assertStringContainsString('<nav id="primary" class="navbar"', $this->nav->render());
    }

    public function testNestsToArbitraryDepth(): void
    {
        $this->nav->add('Services', 'nav.services')
            ->child('Other', 'nav.other')
                ->child('Deep', 'nav.etc1');

        $html = $this->nav->render();

        $this->assertStringContainsString('<li class="nav-item has-children">', $html);
        $this->assertSame(3, substr_count($html, '<ul'));
        $this->assertSame(2, substr_count($html, 'class="nav-submenu"'));
        $this->assertStringContainsString('href="' . $this->url('services/other/etc1') . '"', $html);
    }

    public function testEndClimbsOneLevelPerCall(): void
    {
        $services = $this->nav->add('Services', 'nav.services');
        $other    = $services->child('Other', 'nav.other');
        $deep     = $other->child('Deep', 'nav.etc1');

        $this->assertSame($other, $deep->end());
        $this->assertSame($services, $other->end());
        $this->assertSame($this->nav, $services->end());
    }

    public function testChildrenAddedAfterEndLandOnTheRightParent(): void
    {
        $this->nav->add('Services', 'nav.services')
            ->child('Help', 'nav.help')->end()
            ->child('Other', 'nav.other');

        $services = $this->nav->items()[0];

        $this->assertCount(2, $services->getChildren());
        $this->assertSame('Help', $services->getChildren()[0]->getTitle());
        $this->assertSame('Other', $services->getChildren()[1]->getTitle());
    }

    public function testItemsReturnsRawObjects(): void
    {
        $this->nav->add('Home', 'nav.home');

        $items = $this->nav->items();

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Item::class, $items[0]);
        $this->assertSame('Home', $items[0]->getTitle());
        $this->assertSame($this->url(), $items[0]->getUrl());
        $this->assertFalse($items[0]->hasChildren());
    }

    public function testFlushEmptiesTheTree(): void
    {
        $this->nav->add('Home', 'nav.home');
        $this->nav->flush();

        $this->assertSame([], $this->nav->items());
        $this->assertSame('<nav class="navbar" aria-label="Main"></nav>', $this->nav->render());
    }

    // -----------------------------------------------------------------------
    // Naming and lookup
    // -----------------------------------------------------------------------

    public function testNameRoundTripsAndStaysChainable(): void
    {
        $item = $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertInstanceOf(Item::class, $item);
        $this->assertSame('services', $item->getName());
    }

    public function testAnUnnamedItemHasANullName(): void
    {
        $this->assertNull($this->nav->add('Home', 'nav.home')->getName());
    }

    public function testBlankNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->nav->add('Services', 'nav.services')->name('   ');
    }

    public function testFindLocatesATopLevelItem(): void
    {
        $this->nav->add('Home', 'nav.home');
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertSame('Services', $this->nav->find('services')?->getTitle());
    }

    public function testFindDescendsIntoTheTree(): void
    {
        $this->nav->add('Services', 'nav.services')
            ->child('Other', 'nav.other')
            ->child('Etc', 'nav.etc1')->name('etc');

        $this->assertSame('Etc', $this->nav->find('etc')?->getTitle());
    }

    public function testFindReturnsNullForAnUnknownName(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertNull($this->nav->find('nothing'));
    }

    public function testFindDoesNotSeeItemsUnderAHiddenParent(): void
    {
        $this->nav->add('Admin', 'nav.admin', [], false)
            ->child('Staff', 'nav.staff')->name('staff');

        $this->assertNull($this->nav->find('staff'));
    }

    public function testItemFindScopesTheSearchToItsOwnSubtree(): void
    {
        $services = $this->nav->add('Services', 'nav.services');
        $services->child('Help', 'nav.help')->name('help');

        $this->nav->add('Admin', 'nav.admin')
            ->child('Staff', 'nav.staff')->name('staff');

        $this->assertSame('Help', $services->find('help')?->getTitle());
        $this->assertNull($services->find('staff'));
    }

    // -----------------------------------------------------------------------
    // Deferred injection
    // -----------------------------------------------------------------------

    public function testExtendInjectsWhenRegisteredBeforeTheParentExists(): void
    {
        // The ordering that motivates extend(): a pipeline runs before the
        // controller that builds the tree.
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $this->nav->add('Services', 'nav.services')->name('services');

        $html = $this->nav->render();

        $this->assertStringContainsString('nav-submenu', $html);
        $this->assertStringContainsString($this->url('services/help'), $html);
    }

    public function testExtendInjectsWhenRegisteredAfterTheParentExists(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $this->assertStringContainsString($this->url('services/help'), $this->nav->render());
    }

    public function testExtendReturnsTheBuilderForChaining(): void
    {
        $this->assertSame(
            $this->nav,
            $this->nav->extend('services', static fn (Item $item): Item => $item)
        );
    }

    public function testExtendOnAnAbsentNameIsSilentlyDropped(): void
    {
        $this->nav->add('Home', 'nav.home');
        $this->nav->extend('nothing', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $html = $this->nav->render();

        $this->assertStringNotContainsString('nav-submenu', $html);
        $this->assertStringContainsString($this->url(), $html);
    }

    public function testExtendOnAHiddenParentIsSilentlyDropped(): void
    {
        $this->nav->add('Admin', 'nav.admin', [], false)->name('admin');
        $this->nav->extend('admin', static function (Item $item): void {
            $item->child('Staff', 'nav.staff');
        });

        $this->assertStringNotContainsString('/admin', $this->nav->render());
    }

    public function testSeveralExtendsOnOneNameApplyInRegistrationOrder(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Other', 'nav.other');
        });

        // find() does not drain; items() is the drain point.
        $this->nav->items();

        $children = $this->nav->find('services')?->getChildren() ?? [];

        $this->assertCount(2, $children);
        $this->assertSame('Help', $children[0]->getTitle());
        $this->assertSame('Other', $children[1]->getTitle());
    }

    public function testAnExtendCanTargetAnItemAnEarlierExtendAdded(): void
    {
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Other', 'nav.other')->name('other');
        });
        $this->nav->extend('other', static function (Item $item): void {
            $item->child('Etc', 'nav.etc1');
        });

        $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertStringContainsString($this->url('services/other/etc1'), $this->nav->render());
    }

    public function testItemsDrainsTheQueueWithoutRendering(): void
    {
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $this->nav->add('Services', 'nav.services')->name('services');

        $items = $this->nav->items();

        $this->assertTrue($items[0]->hasChildren());
        $this->assertSame('Help', $items[0]->getChildren()[0]->getTitle());
    }

    public function testRenderingTwiceDoesNotDuplicateAnInjectedChild(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $this->nav->render();
        $this->nav->render();

        $this->assertCount(1, $this->nav->find('services')?->getChildren() ?? []);
    }

    public function testFindDoesNotConsumePendingInjections(): void
    {
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        // Draining here would discard the injection before its parent exists.
        $this->assertNull($this->nav->find('services'));

        $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertStringContainsString($this->url('services/help'), $this->nav->render());
    }

    public function testFlushDiscardsPendingInjections(): void
    {
        $this->nav->extend('services', static function (Item $item): void {
            $item->child('Help', 'nav.help');
        });

        $this->nav->flush();
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->assertStringNotContainsString('nav-submenu', $this->nav->render());
    }

    public function testACallbackMayQueueAFurtherExtend(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->nav->extend('services', function (Item $item): void {
            $item->child('Other', 'nav.other')->name('other');

            $this->nav->extend('other', static function (Item $child): void {
                $child->child('Etc', 'nav.etc2');
            });
        });

        $this->assertStringContainsString($this->url('services/other/etc2'), $this->nav->render());
    }

    public function testACallbackMayReadItemsWithoutReEntering(): void
    {
        $this->nav->add('Services', 'nav.services')->name('services');

        $this->nav->extend('services', function (Item $item): void {
            // Re-entrant read - the guard must stop this recursing.
            $this->assertNotEmpty($this->nav->items());

            $item->child('Help', 'nav.help');
        });

        $this->assertStringContainsString($this->url('services/help'), $this->nav->render());
    }

    // -----------------------------------------------------------------------
    // Conditional display
    // -----------------------------------------------------------------------

    public function testHiddenItemDropsItsWholeSubtree(): void
    {
        $this->nav->add('Home', 'nav.home');
        $this->nav->add('Admin', 'nav.admin', [], false)
            ->child('Staff', 'nav.staff')->end()
            ->child('Logs', 'nav.logs');

        $html = $this->nav->render();

        $this->assertCount(1, $this->nav->items());
        $this->assertStringNotContainsString('/admin', $html);
        $this->assertStringContainsString($this->url(), $html);
    }

    public function testHiddenItemStillReturnsAChainableItem(): void
    {
        $admin = $this->nav->add('Admin', 'nav.admin', [], false);

        $this->assertInstanceOf(Item::class, $admin);
        $this->assertInstanceOf(Item::class, $admin->child('Staff', 'nav.staff'));
    }

    // -----------------------------------------------------------------------
    // Escaping
    // -----------------------------------------------------------------------

    public function testWrapperClassIsEscaped(): void
    {
        $html = $this->nav->render('" onload="alert(1)');

        $this->assertStringNotContainsString('onload="alert(1)"', $html);
        $this->assertStringContainsString('class="&quot; onload=&quot;alert(1)"', $html);
    }

    public function testTitleIsEscaped(): void
    {
        $this->nav->add('Tom & "Jerry" <script>alert(1)</script>', 'nav.home');

        $html = $this->nav->render();

        $this->assertStringContainsString('Tom &amp; &quot;Jerry&quot; &lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testUrlIsEscaped(): void
    {
        $this->nav->add('Query', 'nav.q?x=1&y=2');
        $this->nav->add('External', 'https://example.com/a?x=1&y=2"');

        $html = $this->nav->render();

        $this->assertStringContainsString('href="' . $this->url('a?x=1&amp;y=2') . '"', $html);
        $this->assertStringContainsString('href="https://example.com/a?x=1&amp;y=2&quot;"', $html);
    }

    public function testAttributeAndIconValuesAreEscaped(): void
    {
        $this->nav->add('Home', 'nav.home')
            ->attr('data-tip', '" onmouseover="x')
            ->icon('fa " onerror="x')
            ->addClass('a" onclick="x');

        $html = $this->nav->render();

        $this->assertStringNotContainsString('onmouseover="x"', $html);
        $this->assertStringNotContainsString('onerror="x"', $html);
        $this->assertStringNotContainsString('onclick="x"', $html);
    }

    // -----------------------------------------------------------------------
    // Item attributes
    // -----------------------------------------------------------------------

    public function testClassesLandOnTheListItem(): void
    {
        $this->nav->add('Home', 'nav.home')->addClass('featured', 'first extra');

        $this->assertStringContainsString('<li class="nav-item featured first extra">', $this->nav->render());
    }

    public function testDuplicateClassesAreCollapsed(): void
    {
        $this->nav->add('Home', 'nav.home')->addClass('featured')->addClass('featured');

        $this->assertSame(['featured'], $this->nav->items()[0]->getClasses());
    }

    public function testAttributesAndIdLandOnTheAnchor(): void
    {
        $this->nav->add('Home', 'nav.home')->setId('home-link')->attr('data-turbo', 'false');

        $html = $this->nav->render();

        $this->assertStringContainsString('id="home-link"', $html);
        $this->assertStringContainsString('data-turbo="false"', $html);
    }

    public function testBlankTargetGetsNoopenerRel(): void
    {
        $this->nav->add('Docs', 'https://example.com/docs')->target('_blank');

        $html = $this->nav->render();

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testExplicitRelIsNotOverwrittenByBlankTarget(): void
    {
        $this->nav->add('Docs', 'https://example.com/docs')->rel('external')->target('_blank');

        $this->assertStringContainsString('rel="external"', $this->nav->render());
    }

    public function testNonBlankTargetGetsNoRel(): void
    {
        $this->nav->add('Docs', 'nav.about')->target('_self');

        $this->assertStringNotContainsString('rel=', $this->nav->render());
    }

    public function testIconIsRenderedBeforeTheTitle(): void
    {
        $this->nav->add('Home', 'nav.home')->icon('fa fa-home');

        $this->assertStringContainsString(
            '<i class="fa fa-home" aria-hidden="true"></i>Home',
            $this->nav->render()
        );
    }

    public function testInvalidAttributeNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->nav->add('Home', 'nav.home')->attr('bad name', 'x');
    }

    public function testReservedAttributeNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->nav->add('Home', 'nav.home')->attr('href', '/elsewhere');
    }

    public function testClassAttributeIsReserved(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->nav->add('Home', 'nav.home')->attr('class', 'x');
    }

    // -----------------------------------------------------------------------
    // Active state
    // -----------------------------------------------------------------------

    public function testExactMatchMarksTheCurrentItem(): void
    {
        $this->nav->current($this->url('about'));
        $this->nav->add('Home', 'nav.home');
        $this->nav->add('About', 'nav.about');

        $html = $this->nav->render();

        $this->assertStringContainsString(
            '<li class="nav-item is-active"><a href="' . $this->url('about') . '" class="nav-link" aria-current="page">',
            $html
        );
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function testActiveDetectionFallsBackToTheLiveRequest(): void
    {
        // No current() call at all: this is the real request path, including
        // the /laika-project/ subdirectory the app is installed under.
        $_SERVER['REQUEST_URI'] = '/laika-project/services/help';
        self::registry();

        $nav = new Builder();
        $nav->add('Home', 'nav.home');
        $nav->add('Services', 'nav.services')->child('Help', 'nav.help');

        $html = $nav->render();

        $_SERVER['REQUEST_URI'] = '/laika-project/';

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('<li class="nav-item has-children has-active-child">', $html);
        // The root item must not light up just because it shares the subdirectory.
        $this->assertSame(1, substr_count($html, 'is-active'));
    }

    public function testAncestorOfTheCurrentItemGetsTheOpenClass(): void
    {
        $this->nav->current($this->url('services/help'));
        $this->nav->add('Services', 'nav.services')
            ->child('Help', 'nav.help');

        $html = $this->nav->render();

        $this->assertStringContainsString('<li class="nav-item has-children has-active-child">', $html);
        $this->assertStringNotContainsString('nav-item has-children is-active', $html);
    }

    public function testPrefixMatchAlsoLightsUpAncestors(): void
    {
        $this->nav->current($this->url('services/help'));
        $this->nav->configure(['match' => 'prefix']);
        $this->nav->add('Home', 'nav.home');
        $this->nav->add('Services', 'nav.services')
            ->child('Help', 'nav.help');

        $html = $this->nav->render();

        $this->assertSame(2, substr_count($html, 'is-active'));
        // The root item must not prefix-match every path, subdirectory or not.
        $this->assertStringContainsString(
            '<li class="nav-item"><a href="' . $this->url() . '" class="nav-link">Home</a></li>',
            $html
        );
    }

    public function testPrefixMatchDoesNotMatchPartialSegments(): void
    {
        $this->nav->current($this->url('services-old/help'));
        $this->nav->configure(['match' => 'prefix']);
        $this->nav->add('Services', 'nav.services');

        $this->assertStringNotContainsString('is-active', $this->nav->render());
    }

    public function testExternalLinkNeverMatchesAnInternalPath(): void
    {
        $this->nav->current($this->url('about'));
        $this->nav->add('External', 'https://example.com/about');

        $this->assertStringNotContainsString('is-active', $this->nav->render());
    }

    public function testARawPathStillMatchesAResolvedRoute(): void
    {
        // current() accepts a bare path as well as a full URL.
        $this->nav->current('/about');
        $this->nav->add('About', 'nav.about');

        $this->assertStringContainsString('aria-current="page"', $this->nav->render());
    }

    public function testTrailingSlashIsIgnoredWhenMatching(): void
    {
        $this->nav->current($this->url('about/'));
        $this->nav->add('About', 'nav.about');

        $this->assertStringContainsString('aria-current="page"', $this->nav->render());
    }

    public function testForcedActiveStateOverridesDetection(): void
    {
        $this->nav->current($this->url('about'));
        $this->nav->add('Home', 'nav.home')->active();
        $this->nav->add('About', 'nav.about')->active(false);

        $html = $this->nav->render();

        $this->assertStringContainsString(
            '<li class="nav-item is-active"><a href="' . $this->url() . '" class="nav-link" aria-current="page">Home</a></li>',
            $html
        );
        $this->assertStringContainsString(
            '<li class="nav-item"><a href="' . $this->url('about') . '" class="nav-link">About</a></li>',
            $html
        );
    }

    public function testNullCurrentDisablesActiveDetection(): void
    {
        $this->nav->current(null);
        $this->nav->add('About', 'nav.about');

        $this->assertStringNotContainsString('is-active', $this->nav->render());
    }

    public function testRenderSurvivesAMissingRegistry(): void
    {
        $this->nav->add('Home', 'nav.home');

        // Build first, then pull the registry out from under render(). The
        // Url lookups there are guarded so the menu still draws; only active
        // detection goes quiet.
        Relay::swapRegistry(new RelayRegistry());

        $html = $this->nav->render();

        self::registry();

        $this->assertStringContainsString('href="' . $this->url() . '"', $html);
        $this->assertStringNotContainsString('is-active', $html);
    }

    // -----------------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------------

    public function testConfigureOverridesReachTheOutput(): void
    {
        $this->nav->configure([
            'tag'        => 'div',
            'class'      => 'menu',
            'menu_class' => 'navbar-nav',
        ]);
        $this->nav->add('Home', 'nav.home');

        $html = $this->nav->render();

        $this->assertStringStartsWith('<div class="menu">', $html);
        $this->assertStringEndsWith('</div>', $html);
        $this->assertStringContainsString('<ul class="navbar-nav">', $html);
        // aria-label is a nav-only attribute.
        $this->assertStringNotContainsString('aria-label', $html);
    }

    public function testConfigureBeatsTheRenderArgument(): void
    {
        $this->nav->configure(['class' => 'from-config']);
        $this->nav->add('Home', 'nav.home');

        $this->assertStringContainsString('class="from-config"', $this->nav->render('from-argument'));
    }

    public function testBlankClassesAreOmittedEntirely(): void
    {
        $this->nav->configure([
            'class'      => '',
            'label'      => '',
            'menu_class' => '',
            'item_class' => '',
            'link_class' => '',
        ]);
        $this->nav->add('Home', 'nav.home');

        $this->assertSame(
            '<nav><ul><li><a href="' . $this->url() . '">Home</a></li></ul></nav>',
            $this->nav->render()
        );
    }
}
