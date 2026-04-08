<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use Carbon\Carbon;
use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\JsonHelper;
use HypnoseStammtisch\Utils\RRuleProcessor;

/**
 * Generates an XML sitemap for search engines.
 *
 * The frontend uses hash-based routing, but the sitemap emits server-visible
 * paths so crawlers can request canonical URLs directly and rely on the
 * existing SPA fallback/redirect behavior.
 */
class SitemapController
{
    private const DEFAULT_APP_TIMEZONE = 'Europe/Berlin';
    private const SERIES_EXPANSION_YEARS = 3;
    private const SERIES_EXPANSION_CHUNK_MONTHS = 1;
    private const DEFAULT_EVENT_DURATION_MINUTES = 120;
    private const DEFAULT_SERIES_START_TIME = '19:00:00';
    private const STANDALONE_EVENT_LOOKBACK_YEARS = 2;
    private const MAX_STANDALONE_EVENT_URLS = 20000;
    private const SERIES_SELECT_COLUMNS_PREFIX = 'id, start_date, end_date';
    private const SERIES_SELECT_COLUMNS_SUFFIX = ', default_duration_minutes, rrule, exdates, updated_at';
    private const SERIES_TIME_SELECT_COLUMNS = ', start_time, end_time';

    /**
     * Static public pages with SEO metadata.
     * Selected public routes from src/App.svelte that should be included in the sitemap.
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
        ['path' => '/resources',              'priority' => '0.6', 'changefreq' => 'monthly'],
        ['path' => '/ressourcen/safety-guide', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/ressourcen/faq',         'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/code-of-conduct',        'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/contact',                'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/submit-event',           'priority' => '0.5', 'changefreq' => 'monthly'],
        ['path' => '/cookies',                'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/privacy',                'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/imprint',                'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/terms',                  'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    /**
     * Serve the XML sitemap.
     */
    public function index(): void
    {
        $baseUrl = $this->getFrontendBaseUrl();

        $urls = [];

        // Static pages
        foreach (self::STATIC_PAGES as $page) {
            $urls[] = [
                'loc'        => $this->buildPublicUrl($baseUrl, $page['path']),
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
     * Serve robots.txt with an absolute sitemap URL for the active frontend.
     *
     * Uses the configured frontend base URL so crawlers always receive a
     * fully-qualified Sitemap directive for the current deployment.
     */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $baseUrl = str_replace(["\r", "\n"], '', $this->getFrontendBaseUrl());

        // Use exact-path "$" rules plus directory rules so crawlers block
        // /api and /admin without accidentally matching /apiary or /administrator.
        echo "User-agent: *\nDisallow: /api$\nDisallow: /api/\nDisallow: /admin$\nDisallow: /admin/\n\n";
        echo 'Sitemap: ' . $baseUrl . "/sitemap.xml\n";
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
            $series = $this->fetchPublishedSeriesRows();

            foreach ($series as $row) {
                try {
                    $identifier = $this->buildNextSeriesInstanceIdentifier($row);
                    if ($identifier === null) {
                        continue;
                    }

                    $entry = [
                        'loc'        => $this->buildPublicUrl($baseUrl, '/events/' . rawurlencode($identifier)),
                        'changefreq' => 'weekly',
                        'priority'   => '0.8',
                    ];

                    if (!empty($row['updated_at'])) {
                        $lastmod = $this->formatLastmodDate((string) $row['updated_at']);
                        if ($lastmod !== null) {
                            $entry['lastmod'] = $lastmod;
                        }
                    }

                    $urls[] = $entry;
                } catch (\Throwable $e) {
                    $seriesId = isset($row['id']) ? (string) $row['id'] : 'unknown';
                    error_log('Sitemap: Skipping invalid event series ' . $seriesId . ' – ' . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            error_log('Sitemap: Failed to fetch event series – ' . $e->getMessage());
        }

        return $urls;
    }

    /**
     * Fetch published series rows, choosing the compatible column set for the
     * current schema so missing series time columns do not trigger query errors.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchPublishedSeriesRows(): array
    {
        $activeSeriesCutoff = Carbon::now(Config::get('app.timezone', self::DEFAULT_APP_TIMEZONE))
            ->toDateString();

        try {
            return $this->fetchPublishedSeriesRowsForColumns(
                self::SERIES_SELECT_COLUMNS_PREFIX
                    . self::SERIES_TIME_SELECT_COLUMNS
                    . self::SERIES_SELECT_COLUMNS_SUFFIX,
                $activeSeriesCutoff
            );
        } catch (\Throwable $e) {
            if (!$this->isMissingSeriesTimeColumnsQueryError($e)) {
                throw $e;
            }
        }

        return $this->fetchPublishedSeriesRowsForColumns(
            self::SERIES_SELECT_COLUMNS_PREFIX . self::SERIES_SELECT_COLUMNS_SUFFIX,
            $activeSeriesCutoff
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPublishedSeriesRowsForColumns(string $selectColumns, string $activeSeriesCutoff): array
    {
        return Database::fetchAll(
            "SELECT {$selectColumns}
              FROM event_series
              WHERE status = 'published'
                AND (end_date IS NULL OR end_date >= ?)
              ORDER BY updated_at DESC",
            [$activeSeriesCutoff]
        );
    }

    private function isMissingSeriesTimeColumnsQueryError(\Throwable $exception): bool
    {
        return preg_match("/Unknown column '?(start_time|end_time)'?/i", $exception->getMessage()) === 1;
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
            $updatedSince = Carbon::now(Config::get('app.timezone', self::DEFAULT_APP_TIMEZONE))
                ->subYears(self::STANDALONE_EVENT_LOOKBACK_YEARS)
                ->toDateTimeString();
            $events = Database::fetchAll(
                "SELECT id, updated_at
                 FROM events
                 WHERE status = 'published'
                   AND series_id IS NULL
                   AND updated_at >= ?
                 ORDER BY updated_at DESC
                 LIMIT " . self::MAX_STANDALONE_EVENT_URLS,
                [$updatedSince]
            );

            foreach ($events as $row) {
                $entry = [
                    'loc'        => $this->buildPublicUrl($baseUrl, '/events/' . rawurlencode((string) $row['id'])),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];

                if (!empty($row['updated_at'])) {
                    $lastmod = $this->formatLastmodDate((string) $row['updated_at']);
                    if ($lastmod !== null) {
                        $entry['lastmod'] = $lastmod;
                    }
                }

                $urls[] = $entry;
            }
        } catch (\Exception $e) {
            error_log('Sitemap: Failed to fetch standalone events – ' . $e->getMessage());
        }

        return $urls;
    }

    private function formatLastmodDate(string $updatedAt): ?string
    {
        try {
            return Carbon::parse(
                $updatedAt,
                Config::get('app.timezone', self::DEFAULT_APP_TIMEZONE)
            )->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build a frontend identifier for the next upcoming series instance.
     *
     * @param array<string, mixed> $series
     */
    private function buildNextSeriesInstanceIdentifier(array $series): ?string
    {
        if (empty($series['id']) || empty($series['start_date']) || empty($series['rrule'])) {
            return null;
        }

        $timezone = Config::get('app.timezone', self::DEFAULT_APP_TIMEZONE);
        $now = Carbon::now($timezone);
        $maxExpansionEnd = $now->copy()->addYears(self::SERIES_EXPANSION_YEARS)->endOfDay();
        $seriesEnd = !empty($series['end_date'])
            ? Carbon::parse((string) $series['end_date'], $timezone)->endOfDay()
            : $maxExpansionEnd->copy();
        $startTime = !empty($series['start_time']) ? (string) $series['start_time'] : self::DEFAULT_SERIES_START_TIME;
        $defaultDurationMinutes = !empty($series['default_duration_minutes'])
            ? (int) $series['default_duration_minutes']
            : self::DEFAULT_EVENT_DURATION_MINUTES;

        $eventStart = Carbon::parse((string) $series['start_date'] . ' ' . $startTime, $timezone);
        $eventEnd = $eventStart->copy()->addMinutes($defaultDurationMinutes);

        if (!empty($series['end_time'])) {
            $eventEnd = Carbon::parse((string) $series['start_date'] . ' ' . (string) $series['end_time'], $timezone);
            if ($eventEnd->lte($eventStart)) {
                $eventEnd->addDay();
            }
        }

        // Guard against zero-length or malformed time ranges so the sitemap
        // still advances its search window by at least one minute.
        $durationMinutes = max(1, $eventEnd->diffInMinutes($eventStart));
        // Search from "now minus duration" so same-day events remain listed
        // while they are still in progress instead of disappearing at start.
        $searchStart = $now->copy()->subMinutes($durationMinutes);

        if ($seriesEnd->lt($searchStart->copy()->startOfDay())) {
            return null;
        }

        $expansionEnd = $seriesEnd->lt($maxExpansionEnd)
            ? $seriesEnd
            : $maxExpansionEnd;

        $pseudoEvent = [
            'id' => 'series_' . $series['id'],
            'start_datetime' => $eventStart->toDateTimeString(),
            'end_datetime' => $eventEnd->toDateTimeString(),
            'timezone' => $timezone,
            'rrule' => (string) $series['rrule'],
        ];
        $instanceDate = $this->findFirstRecurringInstanceDate(
            $pseudoEvent,
            $searchStart,
            $expansionEnd,
            JsonHelper::decodeArray($series['exdates'] ?? '[]')
        );

        return $instanceDate !== null
            ? 'series_' . $series['id'] . '_' . $instanceDate
            : null;
    }

    private function buildPublicUrl(string $baseUrl, string $path): string
    {
        $normalizedPath = $path;

        if (str_starts_with($normalizedPath, '/#/')) {
            $normalizedPath = substr($normalizedPath, 2);
        } elseif (str_starts_with($normalizedPath, '#/')) {
            $normalizedPath = substr($normalizedPath, 1);
        } elseif ($normalizedPath === '/#' || $normalizedPath === '#') {
            $normalizedPath = '/';
        }

        if ($normalizedPath === '') {
            $normalizedPath = '/';
        } elseif (!str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/' . $normalizedPath;
        }

        return $baseUrl . $normalizedPath;
    }

    /**
     * Search recurrence instances in bounded chunks until the next visible date is found.
     *
     * @param array<string, mixed> $pseudoEvent
     * @param array<int, mixed> $exdates
     */
    private function findFirstRecurringInstanceDate(
        array $pseudoEvent,
        Carbon $searchStart,
        Carbon $expansionEnd,
        array $exdates
    ): ?string {
        $chunkStart = $searchStart->copy();

        while ($chunkStart->lte($expansionEnd)) {
            $chunkEnd = $chunkStart->copy()
                ->addMonths(self::SERIES_EXPANSION_CHUNK_MONTHS);

            if ($chunkEnd->gt($expansionEnd)) {
                $chunkEnd = $expansionEnd->copy();
            }

            $instances = RRuleProcessor::expandRecurringEvent(
                $pseudoEvent,
                $chunkStart,
                $chunkEnd,
                $exdates
            );

            if (!empty($instances)) {
                $firstInstanceDate = null;
                $fallbackTimezone = !empty($pseudoEvent['timezone'])
                    ? (string) $pseudoEvent['timezone']
                    : Config::get('app.timezone', self::DEFAULT_APP_TIMEZONE);

                foreach ($instances as $instance) {
                    $instanceDate = $this->resolveInstanceDate($instance, $fallbackTimezone);

                    if ($instanceDate !== null && ($firstInstanceDate === null || $instanceDate < $firstInstanceDate)) {
                        $firstInstanceDate = $instanceDate;
                    }
                }

                if ($firstInstanceDate !== null) {
                    return $firstInstanceDate;
                }
            }

            $chunkStart = $chunkEnd->copy()->addSecond();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $instance
     */
    private function resolveInstanceDate(array $instance, string $fallbackTimezone): ?string
    {
        if (!empty($instance['instance_date'])) {
            return (string) $instance['instance_date'];
        }

        if (empty($instance['start_datetime'])) {
            return null;
        }

        $instanceTimezone = !empty($instance['timezone'])
            ? (string) $instance['timezone']
            : $fallbackTimezone;

        try {
            $instanceStart = Carbon::parse((string) $instance['start_datetime'], $instanceTimezone);

            if ($instanceStart->getTimezone()->getName() !== $instanceTimezone) {
                $instanceStart = $instanceStart->setTimezone($instanceTimezone);
            }

            return $instanceStart->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve and validate the frontend base URL used for sitemap/robots output.
     */
    private function getFrontendBaseUrl(): string
    {
        $configuredBaseUrl = (string) Config::get('app.frontend_url', 'https://hypnose-stammtisch.de');
        $trimmedBaseUrl = trim($configuredBaseUrl);
        $parsedUrl = parse_url($trimmedBaseUrl);
        $baseUrl = rtrim($trimmedBaseUrl, '/');

        if (
            $baseUrl === ''
            || preg_match('/\s/', $trimmedBaseUrl) === 1
            || $parsedUrl === false
            || !in_array(strtolower((string) ($parsedUrl['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parsedUrl['host'])
            // Treat a lone "/" as equivalent to no path so origins like
            // "https://example.org/" remain valid after normalization.
            || (!empty($parsedUrl['path']) && $parsedUrl['path'] !== '/')
            || isset($parsedUrl['query'])
            || isset($parsedUrl['fragment'])
            || isset($parsedUrl['user'])
            || isset($parsedUrl['pass'])
        ) {
            return 'https://hypnose-stammtisch.de';
        }

        return $baseUrl;
    }

    /**
     * Render the URL list as an XML sitemap.
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
