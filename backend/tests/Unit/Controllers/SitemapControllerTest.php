<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

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
}
