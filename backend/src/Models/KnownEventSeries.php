<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Models;

use Carbon\Carbon;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\JsonHelper;
use HypnoseStammtisch\Utils\RRuleProcessor;

/**
 * Editorial card for the "Bekannte Event-Reihen" section on the home page.
 *
 * Not to be confused with `event_series`: that table holds the recurring
 * definitions of our own events. A row here describes a series — ours or
 * somebody else's — for the home page. When we do run it ourselves,
 * `linkedSeriesId` points at the `event_series` row and the next date is
 * computed from the RRULE instead of typed by hand.
 */
class KnownEventSeries
{
    /** Next-date modes: computed from a linked series, typed by hand, or hidden. */
    public const NEXT_EVENT_SOURCES = ['auto', 'manual', 'none'];

    public const STATUSES = ['draft', 'published', 'archived'];

    /** How far ahead a linked series is expanded when looking for the next date. */
    private const NEXT_OCCURRENCE_LOOKAHEAD_MONTHS = 18;

    /**
     * Which instance overrides may change a public date, mirroring
     * `EventsController::PUBLIC_OVERRIDE_TYPES` / `PUBLIC_OVERRIDE_STATUSES`.
     * Drafts stay invisible here exactly as they do in the calendar.
     */
    private const PUBLIC_OVERRIDE_TYPES = ['changed', 'cancelled'];
    private const PUBLIC_OVERRIDE_STATUSES = ['published', 'cancelled'];

    public function __construct(
        public ?string $id = null,
        public string $title = '',
        public string $slug = '',
        public string $location = '',
        public string $frequency = '',
        public string $description = '',
        public array $formats = [],
        public ?string $price = null,
        public array $tags = [],
        public string $detailUrl = '/events',
        public string $nextEventSource = 'none',
        public ?string $nextEventText = null,
        public ?string $linkedSeriesId = null,
        public int $sortOrder = 0,
        public string $status = 'draft',
        public bool $isActive = true,
        public ?int $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $createdByUsername = null,
        public ?string $linkedSeriesTitle = null
    ) {}

    /**
     * Published and active series in the order the home page shows them.
     *
     * @return self[]
     */
    public static function getAllPublished(): array
    {
        try {
            $sql = "SELECT s.*, es.title AS linked_series_title
                    FROM known_event_series s
                    LEFT JOIN event_series es ON s.linked_series_id = es.id
                    WHERE s.status = ? AND s.is_active = ?
                    ORDER BY s.sort_order ASC, s.title ASC";
            $results = Database::fetchAll($sql, ['published', true]);

            return array_map([self::class, 'fromArray'], $results);
        } catch (\Exception $e) {
            error_log('Error fetching known event series: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Every series including drafts and archived ones, for the admin table.
     *
     * @return self[]
     */
    public static function getAllForAdmin(): array
    {
        try {
            $sql = "SELECT s.*, u.username AS created_by_username, es.title AS linked_series_title
                    FROM known_event_series s
                    LEFT JOIN users u ON s.created_by = u.id
                    LEFT JOIN event_series es ON s.linked_series_id = es.id
                    ORDER BY s.sort_order ASC, s.created_at DESC";
            $results = Database::fetchAll($sql);

            return array_map(function ($row) {
                $series = self::fromArray($row);
                $series->createdByUsername = $row['created_by_username'] ?? null;
                return $series;
            }, $results);
        } catch (\Exception $e) {
            error_log('Error fetching all known event series: ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(string $id): ?self
    {
        try {
            $sql = "SELECT s.*, es.title AS linked_series_title
                    FROM known_event_series s
                    LEFT JOIN event_series es ON s.linked_series_id = es.id
                    WHERE s.id = ?";
            $data = Database::fetchOne($sql, [$id]);
            return $data ? self::fromArray($data) : null;
        } catch (\Exception $e) {
            error_log('Error fetching known event series: ' . $e->getMessage());
            return null;
        }
    }

    public static function getBySlug(string $slug): ?self
    {
        try {
            $sql = "SELECT * FROM known_event_series WHERE slug = ?";
            $data = Database::fetchOne($sql, [$slug]);
            return $data ? self::fromArray($data) : null;
        } catch (\Exception $e) {
            error_log('Error fetching known event series by slug: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Highest sort order currently in use, so a new series lands at the end.
     */
    public static function getMaxSortOrder(): int
    {
        try {
            $row = Database::fetchOne("SELECT MAX(sort_order) AS max_order FROM known_event_series");
            return is_array($row) ? (int)($row['max_order'] ?? 0) : 0;
        } catch (\Exception $e) {
            error_log('Error reading max sort order: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Whether a linked series exists at all — the controller rejects unknown ids
     * rather than storing a dangling reference.
     */
    public static function linkedSeriesExists(string $seriesId): bool
    {
        try {
            $row = Database::fetchOne("SELECT id FROM event_series WHERE id = ?", [$seriesId]);
            return $row !== false;
        } catch (\Exception $e) {
            error_log('Error checking linked series: ' . $e->getMessage());
            return false;
        }
    }

    public function create(): ?string
    {
        try {
            if (empty($this->id)) {
                $this->id = $this->generateId();
            }

            if (empty($this->slug)) {
                $this->slug = $this->generateSlug();
            }

            $sql = "INSERT INTO known_event_series (
                id, title, slug, location, frequency, description, formats, price, tags,
                detail_url, next_event_source, next_event_text, linked_series_id,
                sort_order, status, is_active, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            Database::execute($sql, [
                $this->id,
                $this->title,
                $this->slug,
                $this->location,
                $this->frequency,
                $this->description,
                json_encode($this->formats),
                $this->price,
                json_encode($this->tags),
                $this->detailUrl,
                $this->nextEventSource,
                $this->nextEventText,
                $this->linkedSeriesId,
                $this->sortOrder,
                $this->status,
                $this->isActive,
                $this->createdBy
            ]);

            return $this->id;
        } catch (\Exception $e) {
            error_log('Error creating known event series: ' . $e->getMessage());
            return null;
        }
    }

    public function update(): bool
    {
        try {
            $sql = "UPDATE known_event_series SET
                title = ?, slug = ?, location = ?, frequency = ?, description = ?,
                formats = ?, price = ?, tags = ?, detail_url = ?,
                next_event_source = ?, next_event_text = ?, linked_series_id = ?,
                sort_order = ?, status = ?, is_active = ?
                WHERE id = ?";

            Database::execute($sql, [
                $this->title,
                $this->slug,
                $this->location,
                $this->frequency,
                $this->description,
                json_encode($this->formats),
                $this->price,
                json_encode($this->tags),
                $this->detailUrl,
                $this->nextEventSource,
                $this->nextEventText,
                $this->linkedSeriesId,
                $this->sortOrder,
                $this->status,
                $this->isActive,
                $this->id
            ]);

            return true;
        } catch (\Exception $e) {
            error_log('Error updating known event series: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(): bool
    {
        try {
            Database::execute("DELETE FROM known_event_series WHERE id = ?", [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log('Error deleting known event series: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Persist a new order. `$orderedIds` is the full list of ids, first to last.
     *
     * All or nothing: a renumbering that stops half way would leave duplicate
     * sort orders behind and let the title tiebreaker decide the rest.
     */
    public static function reorder(array $orderedIds): bool
    {
        $startedTransaction = false;

        try {
            if (!Database::inTransaction()) {
                Database::beginTransaction();
                $startedTransaction = true;
            }

            $position = 1;
            foreach ($orderedIds as $id) {
                Database::execute(
                    "UPDATE known_event_series SET sort_order = ? WHERE id = ?",
                    [$position, (string)$id]
                );
                $position++;
            }

            if ($startedTransaction) {
                Database::commit();
            }

            return true;
        } catch (\Exception $e) {
            if ($startedTransaction && Database::inTransaction()) {
                Database::rollback();
            }

            error_log('Error reordering known event series: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * The next date to show on the card.
     *
     * `manual` returns the typed text, `auto` the next occurrence of the linked
     * series as an ISO datetime, `none` nothing. The frontend decides how to
     * render either — the backend never formats a date for display.
     *
     * Pass `$resolvedOccurrences` (linked series id => ISO datetime or null) to
     * reuse a batch lookup instead of querying per card; `toPublicPayload()`
     * does exactly that. Without it the linked series is resolved on the spot.
     *
     * @param array<string, ?string>|null $resolvedOccurrences
     * @return array{source: string, text: ?string, datetime: ?string}
     */
    public function resolveNextEvent(?array $resolvedOccurrences = null): array
    {
        $resolved = [
            'source' => $this->nextEventSource,
            'text' => null,
            'datetime' => null,
        ];

        if ($this->nextEventSource === 'manual') {
            $resolved['text'] = $this->nextEventText;
            return $resolved;
        }

        if ($this->nextEventSource === 'auto' && $this->linkedSeriesId) {
            $resolved['datetime'] = $resolvedOccurrences !== null
                ? ($resolvedOccurrences[$this->linkedSeriesId] ?? null)
                : self::resolveNextOccurrence($this->linkedSeriesId);
        }

        return $resolved;
    }

    /**
     * Public payload for a whole list of cards, with every linked series
     * resolved in one go.
     *
     * The home page is the most visited page of the site and calls this on every
     * request. Resolving card by card would cost two queries per linked series;
     * here it is two queries in total, no matter how many cards are linked. The
     * RRULE expansion still runs per series, but it is pure computation —
     * `RRuleProcessor::initializeIterationCursor()` jumps to the start of the
     * window instead of iterating from DTSTART.
     *
     * @param self[] $series
     * @return array<int, array<string, mixed>>
     */
    public static function toPublicPayload(array $series): array
    {
        $linkedIds = [];
        foreach ($series as $entry) {
            if ($entry->nextEventSource === 'auto' && $entry->linkedSeriesId) {
                $linkedIds[] = $entry->linkedSeriesId;
            }
        }

        $occurrences = self::resolveNextOccurrences($linkedIds);

        return array_map(
            static fn(self $entry): array => $entry->toPublicArray($occurrences),
            array_values($series)
        );
    }

    /**
     * Next upcoming occurrence of a published `event_series`, as an ISO datetime.
     *
     * Mirrors what the public events endpoint does for the calendar: expand the
     * RRULE, drop EXDATEs, then let stored overrides win — a moved instance
     * supplies its own start, a cancelled one is skipped entirely.
     */
    public static function resolveNextOccurrence(string $seriesId, ?Carbon $from = null): ?string
    {
        return self::resolveNextOccurrences([$seriesId], $from)[$seriesId] ?? null;
    }

    /**
     * The same for several series at once — two queries in total instead of two
     * per series.
     *
     * Ids that name no published series are present in the result with `null`,
     * so a caller can look up every id it asked for.
     *
     * @param string[] $seriesIds
     * @return array<string, ?string>
     */
    public static function resolveNextOccurrences(array $seriesIds, ?Carbon $from = null): array
    {
        $ids = array_values(array_unique(array_filter(
            $seriesIds,
            static fn($id): bool => is_string($id) && $id !== ''
        )));

        $resolved = array_fill_keys($ids, null);

        if ($ids === []) {
            return $resolved;
        }

        $from = $from ? $from->copy() : Carbon::now('Europe/Berlin');

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = Database::fetchAll(
                "SELECT * FROM event_series WHERE id IN ($placeholders) AND status = 'published'",
                $ids
            );

            if ($rows === []) {
                return $resolved;
            }

            $overrides = self::getOverridesFrom(array_column($rows, 'id'), $from);

            foreach ($rows as $series) {
                $seriesId = (string)$series['id'];

                // Per series, so one unparsable row does not blank out the
                // dates of every other card on the page.
                try {
                    $resolved[$seriesId] = self::nextOccurrenceOfSeries(
                        $series,
                        $overrides[$seriesId] ?? [],
                        $from
                    );
                } catch (\Exception $e) {
                    error_log(
                        'Error resolving next occurrence for series ' . $seriesId
                        . ': ' . $e->getMessage()
                    );
                }
            }

            return $resolved;
        } catch (\Exception $e) {
            error_log('Error resolving next occurrences: ' . $e->getMessage());
            return $resolved;
        }
    }

    /**
     * Next occurrence of one already loaded `event_series` row.
     *
     * Like `selectNextOccurrence()` this touches no database, so the window and
     * expansion rules stay unit-testable — and so the batch lookup above can run
     * it per series without paying for another query.
     *
     * @param array<string, mixed> $series Row from `event_series`
     * @param array<string, array{override_type: ?string, start_datetime: ?string}> $overrides Keyed by instance date (Y-m-d)
     */
    public static function nextOccurrenceOfSeries(array $series, array $overrides, Carbon $from): ?string
    {
        $windowEnd = $from->copy()->addMonths(self::NEXT_OCCURRENCE_LOOKAHEAD_MONTHS);

        if (!empty($series['end_date'])) {
            $seriesEnd = Carbon::parse($series['end_date'])->endOfDay();
            if ($seriesEnd->lt($from)) {
                return null;
            }
            if ($seriesEnd->lt($windowEnd)) {
                $windowEnd = $seriesEnd;
            }
        }

        $startTime = $series['start_time'] ?? '00:00:00';
        $endTime = $series['end_time'] ?? date('H:i:s', strtotime($startTime) + 7200);

        $pseudoEvent = [
            'id' => 'series_' . $series['id'],
            'title' => $series['title'],
            'start_datetime' => Carbon::parse(
                $series['start_date'] . ' ' . $startTime,
                'Europe/Berlin'
            )->toDateTimeString(),
            'end_datetime' => Carbon::parse(
                $series['start_date'] . ' ' . $endTime,
                'Europe/Berlin'
            )->toDateTimeString(),
            'timezone' => 'Europe/Berlin',
            'rrule' => $series['rrule'],
            'is_recurring' => true,
        ];

        $exdates = JsonHelper::decodeArray($series['exdates'] ?? '[]');
        $instances = RRuleProcessor::expandRecurringEvent($pseudoEvent, $from, $windowEnd, $exdates);

        return self::selectNextOccurrence($instances, $overrides, $from);
    }

    /**
     * Pick the first occurrence at or after `$from`, honouring instance overrides.
     *
     * Kept free of database access so the selection rules stay unit-testable.
     *
     * Every datetime handed in — instances and override starts alike — must be an
     * Europe/Berlin wall-clock string; `getOverridesFrom()` converts the UTC values
     * stored in `events` before they get here, so the list sorts and compares in a
     * single timezone.
     *
     * @param array<int, array<string, mixed>> $instances Expanded RRULE instances
     * @param array<string, array{override_type: ?string, start_datetime: ?string}> $overrides Keyed by instance date (Y-m-d)
     */
    public static function selectNextOccurrence(array $instances, array $overrides, Carbon $from): ?string
    {
        $candidates = [];

        foreach ($instances as $instance) {
            $start = $instance['start_datetime'] ?? null;
            if (!is_string($start) || $start === '') {
                continue;
            }

            $dateKey = substr($start, 0, 10);
            $override = $overrides[$dateKey] ?? null;

            if ($override !== null) {
                if (($override['override_type'] ?? null) === 'cancelled') {
                    continue;
                }

                if (!empty($override['start_datetime'])) {
                    $start = (string)$override['start_datetime'];
                }
            }

            $candidates[] = $start;
        }

        sort($candidates);

        foreach ($candidates as $candidate) {
            if (Carbon::parse($candidate, 'Europe/Berlin')->gte($from)) {
                return Carbon::parse($candidate, 'Europe/Berlin')->toIso8601String();
            }
        }

        return null;
    }

    /**
     * Stored overrides of the given series from `$from` onwards, grouped by
     * series id and keyed by instance date.
     *
     * Mirrors the public calendar (`EventsController::getPublicSeriesOverrideConstraint`):
     * only overrides that are actually public may move or drop a date on the home
     * page — a freshly created override defaults to `status = 'draft'` and must not
     * change what visitors see before it is published.
     *
     * `events.start_datetime` is stored in UTC, while the expanded instances carry
     * Europe/Berlin wall-clock strings, so the override start is converted here.
     * That keeps `selectNextOccurrence()` a pure comparison over one timezone.
     *
     * @param string[] $seriesIds
     * @return array<string, array<string, array{override_type: ?string, start_datetime: ?string}>>
     */
    private static function getOverridesFrom(array $seriesIds, Carbon $from): array
    {
        if ($seriesIds === []) {
            return [];
        }

        try {
            $seriesPlaceholders = implode(',', array_fill(0, count($seriesIds), '?'));
            $typePlaceholders = implode(',', array_fill(0, count(self::PUBLIC_OVERRIDE_TYPES), '?'));
            $statusPlaceholders = implode(',', array_fill(0, count(self::PUBLIC_OVERRIDE_STATUSES), '?'));

            $rows = Database::fetchAll(
                "SELECT series_id, instance_date, start_datetime, override_type
                 FROM events
                 WHERE series_id IN ($seriesPlaceholders) AND instance_date >= ?
                   AND override_type IN ($typePlaceholders)
                   AND status IN ($statusPlaceholders)",
                [
                    ...array_values($seriesIds),
                    $from->toDateString(),
                    ...self::PUBLIC_OVERRIDE_TYPES,
                    ...self::PUBLIC_OVERRIDE_STATUSES,
                ]
            );

            $overrides = [];
            foreach ($rows as $row) {
                $overrides[(string)$row['series_id']][(string)$row['instance_date']] = [
                    'override_type' => $row['override_type'] ?? null,
                    'start_datetime' => self::toBerlinWallClock($row['start_datetime'] ?? null),
                ];
            }

            return $overrides;
        } catch (\Exception $e) {
            error_log('Error fetching series overrides: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Read a UTC datetime as stored in `events` and return it as an
     * Europe/Berlin wall-clock string, so it can be compared and sorted against
     * the instances RRuleProcessor generates.
     */
    private static function toBerlinWallClock(mixed $utcDatetime): ?string
    {
        if (!is_string($utcDatetime) || trim($utcDatetime) === '') {
            return null;
        }

        try {
            return Carbon::parse($utcDatetime, 'UTC')
                ->setTimezone('Europe/Berlin')
                ->toDateTimeString();
        } catch (\Exception $e) {
            error_log('Error normalizing override start: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize a list field (formats, tags) coming from JSON input or storage.
     *
     * @return string[]
     */
    public static function sanitizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return [];
        }

        $list = [];
        foreach ($value as $entry) {
            if (!is_scalar($entry)) {
                continue;
            }

            $trimmed = trim((string)$entry);
            if ($trimmed !== '' && !in_array($trimmed, $list, true)) {
                $list[] = $trimmed;
            }
        }

        return $list;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? '',
            slug: $data['slug'] ?? '',
            location: $data['location'] ?? '',
            frequency: $data['frequency'] ?? '',
            description: $data['description'] ?? '',
            formats: self::sanitizeStringList($data['formats'] ?? '[]'),
            price: $data['price'] ?? null,
            tags: self::sanitizeStringList($data['tags'] ?? '[]'),
            detailUrl: $data['detail_url'] ?? '/events',
            nextEventSource: $data['next_event_source'] ?? 'none',
            nextEventText: $data['next_event_text'] ?? null,
            linkedSeriesId: $data['linked_series_id'] ?? null,
            sortOrder: (int)($data['sort_order'] ?? 0),
            status: $data['status'] ?? 'draft',
            isActive: (bool)($data['is_active'] ?? true),
            createdBy: isset($data['created_by']) ? (int)$data['created_by'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            createdByUsername: $data['created_by_username'] ?? null,
            linkedSeriesTitle: $data['linked_series_title'] ?? null
        );
    }

    /**
     * Public shape: what the home page needs, with the next date already resolved.
     *
     * @param array<string, ?string>|null $resolvedOccurrences See `resolveNextEvent()`
     */
    public function toPublicArray(?array $resolvedOccurrences = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'location' => $this->location,
            'frequency' => $this->frequency,
            'description' => $this->description,
            'formats' => $this->formats,
            'price' => $this->price,
            'tags' => $this->tags,
            'detailUrl' => $this->detailUrl,
            'nextEvent' => $this->resolveNextEvent($resolvedOccurrences),
        ];
    }

    /**
     * Admin shape: everything the management UI edits.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'location' => $this->location,
            'frequency' => $this->frequency,
            'description' => $this->description,
            'formats' => $this->formats,
            'price' => $this->price,
            'tags' => $this->tags,
            'detailUrl' => $this->detailUrl,
            'nextEventSource' => $this->nextEventSource,
            'nextEventText' => $this->nextEventText,
            'linkedSeriesId' => $this->linkedSeriesId,
            'linkedSeriesTitle' => $this->linkedSeriesTitle,
            'sortOrder' => $this->sortOrder,
            'status' => $this->status,
            'isActive' => $this->isActive,
            'createdBy' => $this->createdBy,
            'createdByUsername' => $this->createdByUsername,
            'createdAt' => $this->createdAt,
            'lastUpdated' => $this->updatedAt,
        ];
    }

    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function generateSlug(): string
    {
        $slug = self::slugify($this->title);

        if ($slug === '') {
            $slug = 'event-reihe';
        }

        $originalSlug = $slug;
        $counter = 1;

        while (self::getBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Lowercase ASCII slug; umlauts are transliterated so "Hypnose-Stammtisch
     * Rhein-Main" does not collapse into a run of dashes.
     */
    public static function slugify(string $text): string
    {
        $slug = strtr(mb_strtolower($text, 'UTF-8'), [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
        ]);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
