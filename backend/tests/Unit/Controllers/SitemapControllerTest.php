<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use Carbon\Carbon;
use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Controllers\SitemapController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SitemapController.
 *
 * These tests validate the static configuration and structure without
 * requiring a database connection.
 */
class SitemapControllerTest extends TestCase
{
    private ?string $originalFrontendUrl = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalFrontendUrl = $_ENV['FRONTEND_URL'] ?? null;
        Config::reset();
    }

    protected function tearDown(): void
    {
        if ($this->originalFrontendUrl === null) {
            unset($_ENV['FRONTEND_URL']);
        } else {
            $_ENV['FRONTEND_URL'] = $this->originalFrontendUrl;
        }

        Config::reset();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testStaticPagesAreNotEmpty(): void
    {
        $pages = SitemapController::getStaticPages();

        $this->assertNotEmpty($pages, 'Static pages list must not be empty');
    }

    public function testStaticPagesContainHomepage(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        $this->assertContains('/', $paths, 'Homepage must be included in static pages');
    }

    public function testStaticPagesContainEventsPage(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        $this->assertContains('/events', $paths, 'Events page must be included in static pages');
    }

    public function testStaticPagesContainLegalPages(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        $this->assertContains('/cookies', $paths, 'Cookie policy page must be included');
        $this->assertContains('/privacy', $paths, 'Privacy page must be included');
        $this->assertContains('/imprint', $paths, 'Imprint page must be included');
        $this->assertContains('/terms', $paths, 'Terms page must be included');
    }

    public function testStaticPagesContainResourcesPages(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        $this->assertContains('/learning-resources', $paths, 'Learning resources page must be included');
        $this->assertContains('/ressourcen', $paths, 'German learning resources page must be included');
        $this->assertContains('/resources', $paths, 'Resources page must be included');
    }

    public function testStaticPagesDoNotContainAdminRoutes(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        foreach ($paths as $path) {
            $this->assertStringNotContainsString(
                'admin',
                $path,
                "Admin routes must not appear in the sitemap: {$path}"
            );
        }
    }

    public function testStaticPagesDoNotContainLoginRoute(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        foreach ($paths as $path) {
            $this->assertStringNotContainsString(
                'login',
                $path,
                "Login route must not appear in the sitemap: {$path}"
            );
        }
    }

    public function testStaticPagesHaveRequiredFields(): void
    {
        $pages = SitemapController::getStaticPages();

        foreach ($pages as $i => $page) {
            $this->assertArrayHasKey('path', $page, "Page #{$i} missing 'path'");
            $this->assertArrayHasKey('priority', $page, "Page #{$i} missing 'priority'");
            $this->assertArrayHasKey('changefreq', $page, "Page #{$i} missing 'changefreq'");
        }
    }

    public function testStaticPagesPriorityIsValid(): void
    {
        $pages = SitemapController::getStaticPages();

        foreach ($pages as $page) {
            $priority = (float) $page['priority'];
            $this->assertGreaterThanOrEqual(0.0, $priority, "Priority for {$page['path']} too low");
            $this->assertLessThanOrEqual(1.0, $priority, "Priority for {$page['path']} too high");
        }
    }

    public function testStaticPagesChangefreqIsValid(): void
    {
        $validFrequencies = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        $pages = SitemapController::getStaticPages();

        foreach ($pages as $page) {
            $this->assertContains(
                $page['changefreq'],
                $validFrequencies,
                "Invalid changefreq '{$page['changefreq']}' for {$page['path']}"
            );
        }
    }

    public function testStaticPagesPathsStartWithSlash(): void
    {
        $pages = SitemapController::getStaticPages();

        foreach ($pages as $page) {
            $this->assertStringStartsWith(
                '/',
                $page['path'],
                "Path must start with /: {$page['path']}"
            );
        }
    }

    public function testHomepageHasHighestPriority(): void
    {
        $pages = SitemapController::getStaticPages();
        $homePage = null;

        foreach ($pages as $page) {
            if ($page['path'] === '/') {
                $homePage = $page;
                break;
            }
        }

        $this->assertNotNull($homePage, 'Homepage should exist in static pages');
        $this->assertEquals('1.0', $homePage['priority'], 'Homepage should have priority 1.0');
    }

    public function testNoDuplicatePaths(): void
    {
        $pages = SitemapController::getStaticPages();
        $paths = array_column($pages, 'path');

        $this->assertCount(
            count(array_unique($paths)),
            $paths,
            'Static pages must not contain duplicate paths'
        );
    }

    public function testBuildNextSeriesInstanceIdentifierReturnsNextUpcomingInstance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 09:00:00', 'Europe/Berlin'));

        $identifier = $this->invokeBuildNextSeriesInstanceIdentifier([
            'id' => 'series-123',
            'start_date' => '2020-01-01',
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'rrule' => 'FREQ=WEEKLY;BYDAY=WE',
            'exdates' => '[]',
        ]);

        $this->assertSame('series_series-123_2026-04-01', $identifier);
    }

    public function testBuildNextSeriesInstanceIdentifierSkipsExcludedNextOccurrence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 09:00:00', 'Europe/Berlin'));

        $identifier = $this->invokeBuildNextSeriesInstanceIdentifier([
            'id' => 'series-456',
            'start_date' => '2020-01-01',
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'rrule' => 'FREQ=WEEKLY;BYDAY=WE',
            'exdates' => json_encode(['2026-04-01']),
        ]);

        $this->assertSame('series_series-456_2026-04-08', $identifier);
    }

    public function testBuildNextSeriesInstanceIdentifierWorksWithoutTimeColumns(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 20:00:00', 'Europe/Berlin'));

        $identifier = $this->invokeBuildNextSeriesInstanceIdentifier([
            'id' => 'series-789',
            'start_date' => '2026-03-02',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
            'exdates' => '[]',
        ]);

        $this->assertSame('series_series-789_2026-03-30', $identifier);
    }

    public function testBuildNextSeriesInstanceIdentifierSkipsPastSameDayOccurrenceAfterDefaultDuration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 22:30:00', 'Europe/Berlin'));

        $identifier = $this->invokeBuildNextSeriesInstanceIdentifier([
            'id' => 'series-790',
            'start_date' => '2026-03-02',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
            'exdates' => '[]',
        ]);

        $this->assertSame('series_series-790_2026-04-06', $identifier);
    }

    public function testBuildNextSeriesInstanceIdentifierReturnsNullForEndedSeries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 09:00:00', 'Europe/Berlin'));

        $identifier = $this->invokeBuildNextSeriesInstanceIdentifier([
            'id' => 'series-ended',
            'start_date' => '2020-01-01',
            'end_date' => '2026-03-29',
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'rrule' => 'FREQ=WEEKLY;BYDAY=WE',
            'exdates' => '[]',
        ]);

        $this->assertNull($identifier);
    }

    public function testBuildPublicUrlUsesServerVisiblePaths(): void
    {
        $url = $this->invokeBuildPublicUrl('https://hypnose-stammtisch.de', '/#/events/series_123_2026-04-01');

        $this->assertSame('https://hypnose-stammtisch.de/events/series_123_2026-04-01', $url);
    }

    public function testBuildPublicUrlHandlesRootLikeEdgeCases(): void
    {
        $this->assertSame(
            'https://hypnose-stammtisch.de/',
            $this->invokeBuildPublicUrl('https://hypnose-stammtisch.de', '')
        );
        $this->assertSame(
            'https://hypnose-stammtisch.de/',
            $this->invokeBuildPublicUrl('https://hypnose-stammtisch.de', '#')
        );
        $this->assertSame(
            'https://hypnose-stammtisch.de/',
            $this->invokeBuildPublicUrl('https://hypnose-stammtisch.de', '/')
        );
    }

    public function testBuildPublicUrlPreservesAlreadyNormalizedPath(): void
    {
        $url = $this->invokeBuildPublicUrl('https://hypnose-stammtisch.de', '/events/abc');

        $this->assertSame('https://hypnose-stammtisch.de/events/abc', $url);
    }

    public function testRobotsFallsBackToDefaultSitemapOriginForInvalidFrontendUrl(): void
    {
        $_ENV['FRONTEND_URL'] = 'ftp://example.org/somewhere';
        Config::reset();

        $controller = new SitemapController();

        ob_start();
        $controller->robots();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "Sitemap: https://hypnose-stammtisch.de/sitemap.xml\n",
            $output
        );
    }

    public function testRobotsAcceptsConfiguredOriginWithPortAndTrailingSlash(): void
    {
        $_ENV['FRONTEND_URL'] = 'https://localhost:5173/';
        Config::reset();

        $controller = new SitemapController();

        ob_start();
        $controller->robots();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "Sitemap: https://localhost:5173/sitemap.xml\n",
            $output
        );
    }

    public function testRobotsUsesExactAndDirectoryDisallowRules(): void
    {
        $_ENV['FRONTEND_URL'] = 'https://localhost:5173';
        Config::reset();

        $controller = new SitemapController();

        ob_start();
        $controller->robots();
        $output = ob_get_clean();

        $expected = <<<ROBOTS
User-agent: *
Disallow: /api$
Disallow: /api/
Disallow: /admin$
Disallow: /admin/

Sitemap: https://localhost:5173/sitemap.xml
ROBOTS;

        $this->assertSame(
            $expected,
            $output
        );
    }

    /**
     * @param array<string, mixed> $series
     */
    private function invokeBuildNextSeriesInstanceIdentifier(array $series): ?string
    {
        $controller = new SitemapController();
        $method = new \ReflectionMethod($controller, 'buildNextSeriesInstanceIdentifier');
        $method->setAccessible(true);

        return $method->invoke($controller, $series);
    }

    private function invokeBuildPublicUrl(string $baseUrl, string $path): string
    {
        $controller = new SitemapController();
        $method = new \ReflectionMethod($controller, 'buildPublicUrl');
        $method->setAccessible(true);

        return $method->invoke($controller, $baseUrl, $path);
    }
}
