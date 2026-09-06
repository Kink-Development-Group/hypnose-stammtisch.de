<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Models;

use Carbon\Carbon;
use HypnoseStammtisch\Models\KnownEventSeries;
use PHPUnit\Framework\TestCase;

/**
 * Selection of the next date and normalization of the editorial list fields.
 */
class KnownEventSeriesTest extends TestCase
{
    private function instances(array $starts): array
    {
        return array_map(fn(string $start) => ['start_datetime' => $start], $starts);
    }

    /**
     * A published `event_series` row as `resolveNextOccurrences()` loads it:
     * first Friday of every month, 19:00.
     *
     * @return array<string, mixed>
     */
    private function series(): array
    {
        return [
            'id' => 'series-1',
            'title' => 'Hamburger Hypnose Munch',
            'start_date' => '2026-01-02',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'end_date' => null,
            'rrule' => 'FREQ=MONTHLY;BYDAY=1FR',
            'exdates' => '[]',
        ];
    }

    public function testPicksTheFirstUpcomingOccurrence(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00', '2026-10-02 19:00:00']),
            [],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-09-04T19:00:00', $next);
    }

    public function testSkipsOccurrencesInThePast(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00', '2026-10-02 19:00:00']),
            [],
            Carbon::parse('2026-09-10 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-10-02T19:00:00', $next);
    }

    /**
     * An instance that starts later the same day still counts as upcoming.
     */
    public function testAnOccurrenceLaterTodayIsStillUpcoming(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00']),
            [],
            Carbon::parse('2026-09-04 08:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-09-04T19:00:00', $next);
    }

    public function testCancelledInstancesAreSkipped(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00', '2026-10-02 19:00:00']),
            [
                '2026-09-04' => [
                    'override_type' => 'cancelled',
                    'start_datetime' => '2026-09-04 19:00:00',
                ],
            ],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-10-02T19:00:00', $next);
    }

    /**
     * A moved instance supplies its own start time — the card must show the new
     * one, not the date the RRULE would have produced.
     */
    public function testChangedInstancesUseTheirOverriddenStart(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00']),
            [
                '2026-09-04' => [
                    'override_type' => 'changed',
                    'start_datetime' => '2026-09-04 20:30:00',
                ],
            ],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-09-04T20:30:00', $next);
    }

    /**
     * An instance moved to a later date must not jump the queue ahead of one
     * that now happens earlier.
     */
    public function testMovedInstancesAreReorderedByTheirNewStart(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00', '2026-09-20 19:00:00']),
            [
                '2026-09-04' => [
                    'override_type' => 'changed',
                    'start_datetime' => '2026-09-25 19:00:00',
                ],
            ],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-09-20T19:00:00', $next);
    }

    public function testReturnsNullWhenEveryOccurrenceIsInThePast(): void
    {
        $next = KnownEventSeries::selectNextOccurrence(
            $this->instances(['2026-09-04 19:00:00']),
            [],
            Carbon::parse('2026-12-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNull($next);
    }

    public function testReturnsNullWithoutInstances(): void
    {
        $this->assertNull(KnownEventSeries::selectNextOccurrence(
            [],
            [],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        ));
    }

    /**
     * The batch lookup resolves each series through this pure step, so the
     * window and expansion rules are covered without touching the database.
     */
    public function testNextOccurrenceOfSeriesExpandsTheRrule(): void
    {
        $next = KnownEventSeries::nextOccurrenceOfSeries(
            $this->series(),
            [],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-09-04T19:00:00', $next);
    }

    public function testNextOccurrenceOfSeriesHonoursOverrides(): void
    {
        $next = KnownEventSeries::nextOccurrenceOfSeries(
            $this->series(),
            [
                '2026-09-04' => [
                    'override_type' => 'cancelled',
                    'start_datetime' => null,
                ],
            ],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        );

        $this->assertNotNull($next);
        $this->assertStringStartsWith('2026-10-02T19:00:00', $next);
    }

    /**
     * A series that ended before the window starts has no next date at all —
     * the expansion must not run past `end_date`.
     */
    public function testNextOccurrenceOfSeriesStopsAfterItsEndDate(): void
    {
        $series = $this->series();
        $series['end_date'] = '2026-08-31';

        $this->assertNull(KnownEventSeries::nextOccurrenceOfSeries(
            $series,
            [],
            Carbon::parse('2026-09-01 12:00:00', 'Europe/Berlin')
        ));
    }

    public function testSanitizeStringListTrimsAndDeduplicates(): void
    {
        $list = KnownEventSeries::sanitizeStringList([
            '  Hypnose 101  ',
            'Hypnose 101',
            '',
            'Koalabox',
        ]);

        $this->assertSame(['Hypnose 101', 'Koalabox'], $list);
    }

    public function testSanitizeStringListDecodesStoredJson(): void
    {
        $list = KnownEventSeries::sanitizeStringList('["Erotisch", "BDSM"]');

        $this->assertSame(['Erotisch', 'BDSM'], $list);
    }

    public function testSanitizeStringListDropsNonScalarEntries(): void
    {
        $list = KnownEventSeries::sanitizeStringList(['ok', ['nested'], null]);

        $this->assertSame(['ok'], $list);
    }

    public function testSanitizeStringListHandlesGarbage(): void
    {
        $this->assertSame([], KnownEventSeries::sanitizeStringList('not json'));
        $this->assertSame([], KnownEventSeries::sanitizeStringList(null));
    }

    /**
     * Umlauts have to survive as readable text — a naive slugifier turns
     * "Bekannte Reihen für Geübte" into a run of dashes.
     */
    public function testSlugifyTransliteratesUmlauts(): void
    {
        $this->assertSame(
            'hypnose-stammtisch-rhein-main',
            KnownEventSeries::slugify('Hypnose-Stammtisch Rhein-Main')
        );
        $this->assertSame(
            'reihe-fuer-gruesse',
            KnownEventSeries::slugify('Reihe für Grüße')
        );
    }

    public function testSlugifyCollapsesSeparators(): void
    {
        $this->assertSame(
            'hamburg-club-catonium',
            KnownEventSeries::slugify('Hamburg • Club Catonium')
        );
    }
}
