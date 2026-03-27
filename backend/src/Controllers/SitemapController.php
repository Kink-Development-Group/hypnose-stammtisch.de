<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

/**
 * Generates an XML sitemap for search engines.
 *
 * The frontend is a hash-based SPA (svelte-spa-router), so every public URL
 * uses the /#/… fragment notation. While Google historically ignores hash
 * fragments, modern Googlebot renders JavaScript and can discover
 * hash-routed pages. Including them in the sitemap is the most accurate
 * representation of the site.
 */
class SitemapController
{
    /**
     * Static public pages with SEO metadata.
     * Mirrors the routes defined in src/App.svelte.
     *
     * @var array<int, array{path: string, priority: string, changefreq: string}>
     */
    private const STATIC_PAGES = [
        ['path' => '/',                       'priority' => '1.0', 'changefreq' => 'daily'],
        ['path' => '/events',                 'priority' => '0.9', 'changefreq' => 'daily'],
        ['path' => '/map',                    'priority' => '0.7', 'changefreq' => 'weekly'],
        ['path' => '/about',                  'priority' => '0.6', 'changefreq' => 'monthly'],
        ['path' => '/learning-resources',     'priority' => '0.6', 'changefreq' => 'monthly'],
        ['path' => '/ressourcen',             'priority' => '0.6', 'changefreq' => 'monthly'],
        ['path' => '/ressourcen/safety-guide', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/ressourcen/faq',         'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/code-of-conduct',        'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/contact',                'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/submit-event',           'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/privacy',                'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/imprint',                'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/terms',                  'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    /**
     * Serve the XML sitemap.
     */
    public function index(): void
    {
        $baseUrl = rtrim((string) Config::get('app.frontend_url', 'https://hypnose-stammtisch.de'), '/');

        $urls = [];

        // Static pages
        foreach (self::STATIC_PAGES as $page) {
            $urls[] = [
                'loc'        => $baseUrl . '/#' . $page['path'],
                'changefreq' => $page['changefreq'],
                'priority'   => $page['priority'],
            ];
        }

        // Published event series
        $urls = array_merge($urls, $this->fetchEventSeriesUrls($baseUrl));

        // Published standalone events (not part of a series)
        $urls = array_merge($urls, $this->fetchStandaloneEventUrls($baseUrl));

        $this->outputXml($urls);
    }

    /**
     * Build URL entries for published event series.
     *
     * @return array<int, array<string, string>>
     */
    private function fetchEventSeriesUrls(string $baseUrl): array
    {
        $urls = [];

        try {
            $series = Database::fetchAll(
                "SELECT slug, updated_at FROM event_series WHERE status = 'published' ORDER BY updated_at DESC"
            );

            foreach ($series as $row) {
                $entry = [
                    'loc'        => $baseUrl . '/#/events/' . rawurlencode($row['slug']),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];

                if (!empty($row['updated_at'])) {
                    $entry['lastmod'] = date('Y-m-d', strtotime($row['updated_at']));
                }

                $urls[] = $entry;
            }
        } catch (\Exception $e) {
            error_log('Sitemap: Failed to fetch event series – ' . $e->getMessage());
        }

        return $urls;
    }

    /**
     * Build URL entries for published standalone events.
     *
     * @return array<int, array<string, string>>
     */
    private function fetchStandaloneEventUrls(string $baseUrl): array
    {
        $urls = [];

        try {
            $events = Database::fetchAll(
                "SELECT slug, updated_at FROM events WHERE status = 'published' AND series_id IS NULL ORDER BY updated_at DESC"
            );

            foreach ($events as $row) {
                $entry = [
                    'loc'        => $baseUrl . '/#/events/' . rawurlencode($row['slug']),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];

                if (!empty($row['updated_at'])) {
                    $entry['lastmod'] = date('Y-m-d', strtotime($row['updated_at']));
                }

                $urls[] = $entry;
            }
        } catch (\Exception $e) {
            error_log('Sitemap: Failed to fetch standalone events – ' . $e->getMessage());
        }

        return $urls;
    }

    /**
     * Render the URL list as an XML sitemap and terminate.
     *
     * @param array<int, array<string, string>> $urls
     */
    private function outputXml(array $urls): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('X-Robots-Tag: noindex');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";

            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            if (!empty($url['changefreq'])) {
                $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            }
            if (!empty($url['priority'])) {
                $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }

    /**
     * Return the list of static page definitions (useful for testing).
     *
     * @return array<int, array{path: string, priority: string, changefreq: string}>
     */
    public static function getStaticPages(): array
    {
        return self::STATIC_PAGES;
    }
}
