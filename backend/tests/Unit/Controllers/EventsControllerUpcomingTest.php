<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\EventsController;
use HypnoseStammtisch\Database\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Regression coverage for the upcoming-events endpoint.
 *
 * The homepage "Kommende Veranstaltungen" widget previously read the events table
 * directly, so recurring series whose start/base date lies in the past contributed
 * no upcoming occurrences at all. upcoming() now expands series + legacy recurring
 * events and keeps only the ones that have not ended.
 */
class EventsControllerUpcomingTest extends TestCase
{
    private EventsController $controller;
    private ReflectionMethod $getExpandedEventsMethod;
    private ReflectionMethod $selectUpcomingMethod;
    private PDO $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // Mirror production (backend/api/index.php) so wall-clock comparisons are
        // deterministic regardless of the host timezone.
        date_default_timezone_set('Europe/Berlin');

        $this->controller = new EventsController();

        $this->getExpandedEventsMethod = new ReflectionMethod(EventsController::class, 'getExpandedEvents');
        $this->getExpandedEventsMethod->setAccessible(true);
        $this->selectUpcomingMethod = new ReflectionMethod(EventsController::class, 'selectUpcomingFromExpanded');
        $this->selectUpcomingMethod->setAccessible(true);

        $this->connection = new PDO('sqlite::memory:');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $dbRef = new ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setValue(null, $this->connection);

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $dbRef = new ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setValue(null, null);

        parent::tearDown();
    }

    public function testSelectUpcomingDropsEndedAndCancelledThenSortsAndLimits(): void
    {
        $reference = strtotime('2026-06-11 12:00:00');

        $expanded = [
            ['id' => 'ended', 'start_datetime' => '2026-06-10 19:00:00', 'end_datetime' => '2026-06-10 21:00:00'],
            ['id' => 'ongoing', 'start_datetime' => '2026-06-11 11:00:00', 'end_datetime' => '2026-06-11 13:00:00'],
            ['id' => 'soon', 'start_datetime' => '2026-06-12 19:00:00', 'end_datetime' => '2026-06-12 21:00:00'],
            ['id' => 'later', 'start_datetime' => '2026-06-20 19:00:00', 'end_datetime' => '2026-06-20 21:00:00'],
            // Cancelled instances must never surface on the public upcoming list.
            [
                'id' => 'cancelled-type',
                'start_datetime' => '2026-06-13 19:00:00',
                'end_datetime' => '2026-06-13 21:00:00',
                'override_type' => 'cancelled',
            ],
            [
                'id' => 'cancelled-status',
                'start_datetime' => '2026-06-14 19:00:00',
                'end_datetime' => '2026-06-14 21:00:00',
                'status' => 'cancelled',
            ],
        ];

        $result = $this->selectUpcoming($expanded, $reference, 5);
        $ids = array_column($result, 'id');

        // Ongoing event (started, not yet ended) is still upcoming; ended/cancelled drop out.
        $this->assertSame(['ongoing', 'soon', 'later'], $ids);
    }

    public function testSelectUpcomingRespectsLimit(): void
    {
        $reference = strtotime('2026-06-11 12:00:00');

        $expanded = [
            ['id' => 'a', 'start_datetime' => '2026-06-20 19:00:00', 'end_datetime' => '2026-06-20 21:00:00'],
            ['id' => 'b', 'start_datetime' => '2026-06-12 19:00:00', 'end_datetime' => '2026-06-12 21:00:00'],
            ['id' => 'c', 'start_datetime' => '2026-06-15 19:00:00', 'end_datetime' => '2026-06-15 21:00:00'],
        ];

        $result = $this->selectUpcoming($expanded, $reference, 2);

        $this->assertSame(['b', 'c'], array_column($result, 'id'));
    }

    public function testPublishedSeriesWithPastStartStillYieldsUpcomingInstances(): void
    {
        // Series created/started in January still meets every Monday with no end date.
        $this->insertSeries([
            'id' => 'weekly-monday',
            'title' => 'Wöchentlicher Stammtisch',
            'slug' => 'woechentlicher-stammtisch',
            'start_date' => '2026-01-05', // a Monday, well in the past
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
            'status' => 'published',
        ]);

        [$expanded, $reference] = $this->expandForUpcoming('2026-06-11 12:00:00');
        $result = $this->selectUpcoming($expanded, $reference, 6);

        $this->assertNotEmpty(
            $result,
            'A published weekly series with a past start date must still produce upcoming instances.'
        );
        $this->assertCount(6, $result);

        foreach ($result as $event) {
            $this->assertSame('weekly-monday', $event['series_id'] ?? null);
            $this->assertGreaterThan(
                $reference,
                strtotime($event['end_datetime']),
                'Every returned instance must end in the future.'
            );
        }

        // First upcoming Monday after 2026-06-11 (Thursday) is 2026-06-15.
        $this->assertSame('2026-06-15', $result[0]['instance_date'] ?? null);
    }

    public function testLegacyRecurringEventWithPastStartStillYieldsUpcomingInstances(): void
    {
        // Legacy recurring event stored directly in the events table; its original
        // datetime is in the past but the recurrence continues into the future.
        $this->insertEvent([
            'id' => 'legacy-weekly',
            'title' => 'Legacy Recurring',
            'slug' => 'legacy-recurring',
            'start_datetime' => '2026-01-07 18:00:00',
            'end_datetime' => '2026-01-07 20:00:00',
            'status' => 'published',
            'is_recurring' => 1,
            'rrule' => 'FREQ=WEEKLY;BYDAY=WE',
        ]);

        [$expanded, $reference] = $this->expandForUpcoming('2026-06-11 12:00:00');
        $result = $this->selectUpcoming($expanded, $reference, 4);

        $this->assertCount(4, $result);
        foreach ($result as $event) {
            $this->assertGreaterThan($reference, strtotime($event['end_datetime']));
        }
    }

    public function testFinishedStandaloneEventIsExcluded(): void
    {
        $this->insertEvent([
            'id' => 'past-standalone',
            'title' => 'Past Standalone',
            'slug' => 'past-standalone',
            'start_datetime' => '2026-06-01 19:00:00',
            'end_datetime' => '2026-06-01 21:00:00',
            'status' => 'published',
        ]);
        $this->insertEvent([
            'id' => 'future-standalone',
            'title' => 'Future Standalone',
            'slug' => 'future-standalone',
            'start_datetime' => '2026-06-20 19:00:00',
            'end_datetime' => '2026-06-20 21:00:00',
            'status' => 'published',
        ]);

        [$expanded, $reference] = $this->expandForUpcoming('2026-06-11 12:00:00');
        $result = $this->selectUpcoming($expanded, $reference, 6);
        $ids = array_column($result, 'id');

        $this->assertContains('future-standalone', $ids);
        $this->assertNotContains('past-standalone', $ids);
    }

    /**
     * Build the expanded event list using the same filter window the upcoming()
     * endpoint constructs, returning it alongside the reference timestamp.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function expandForUpcoming(string $nowString): array
    {
        $now = strtotime($nowString);
        $filters = [
            'from_date' => date('Y-m-d 00:00:00', strtotime('-1 day', $now)),
            'to_date' => date('Y-m-d H:i:s', strtotime('+24 months', $now)),
        ];

        return [$this->getExpandedEvents($filters), $now];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function getExpandedEvents(array $filters): array
    {
        return $this->getExpandedEventsMethod->invoke($this->controller, $filters);
    }

    /**
     * @param array<int, array<string, mixed>> $expanded
     * @return array<int, array<string, mixed>>
     */
    private function selectUpcoming(array $expanded, int $reference, int $limit): array
    {
        return $this->selectUpcomingMethod->invoke($this->controller, $expanded, $reference, $limit);
    }

    private function createSchema(): void
    {
        $this->connection->exec(
            <<<SQL
            CREATE TABLE events (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT DEFAULT '',
                content TEXT DEFAULT '',
                start_datetime TEXT NOT NULL,
                end_datetime TEXT NOT NULL,
                timezone TEXT DEFAULT 'Europe/Berlin',
                is_recurring INTEGER DEFAULT 0,
                rrule TEXT DEFAULT NULL,
                recurrence_end_date TEXT DEFAULT NULL,
                parent_event_id TEXT DEFAULT NULL,
                override_type TEXT DEFAULT NULL,
                cancellation_reason TEXT DEFAULT NULL,
                location_type TEXT DEFAULT 'physical',
                location_name TEXT DEFAULT '',
                location_address TEXT DEFAULT '',
                location_url TEXT DEFAULT '',
                location_instructions TEXT DEFAULT '',
                category TEXT DEFAULT 'stammtisch',
                difficulty_level TEXT DEFAULT 'all',
                max_participants INTEGER DEFAULT NULL,
                current_participants INTEGER DEFAULT 0,
                age_restriction INTEGER DEFAULT 18,
                requirements TEXT DEFAULT '',
                safety_notes TEXT DEFAULT '',
                preparation_notes TEXT DEFAULT '',
                status TEXT NOT NULL,
                is_featured INTEGER DEFAULT 0,
                requires_registration INTEGER DEFAULT 1,
                registration_deadline TEXT DEFAULT NULL,
                organizer_name TEXT DEFAULT '',
                organizer_email TEXT DEFAULT '',
                organizer_bio TEXT DEFAULT '',
                tags TEXT DEFAULT '[]',
                meta_description TEXT DEFAULT '',
                image_url TEXT DEFAULT '',
                created_at TEXT DEFAULT NULL,
                updated_at TEXT DEFAULT NULL,
                series_id TEXT DEFAULT NULL,
                instance_date TEXT DEFAULT NULL
            )
            SQL
        );

        $this->connection->exec(
            <<<SQL
            CREATE TABLE event_series (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT DEFAULT '',
                start_date TEXT NOT NULL,
                end_date TEXT DEFAULT NULL,
                start_time TEXT DEFAULT NULL,
                end_time TEXT DEFAULT NULL,
                rrule TEXT NOT NULL,
                exdates TEXT DEFAULT '[]',
                default_location_type TEXT DEFAULT 'physical',
                default_location_name TEXT DEFAULT '',
                default_location_address TEXT DEFAULT '',
                default_category TEXT DEFAULT 'stammtisch',
                default_duration_minutes INTEGER DEFAULT 120,
                tags TEXT DEFAULT '[]',
                status TEXT NOT NULL,
                created_at TEXT DEFAULT NULL,
                updated_at TEXT DEFAULT NULL
            )
            SQL
        );
    }

    /**
     * @param array<string, mixed> $series
     */
    private function insertSeries(array $series): void
    {
        $defaults = [
            'description' => '',
            'end_date' => null,
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'exdates' => '[]',
            'default_location_type' => 'physical',
            'default_location_name' => 'Venue',
            'default_location_address' => 'Street 1',
            'default_category' => 'stammtisch',
            'default_duration_minutes' => 120,
            'tags' => '[]',
            'status' => 'published',
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ];

        $statement = $this->connection->prepare(
            'INSERT INTO event_series (
                id, title, slug, description, start_date, end_date, start_time, end_time,
                rrule, exdates, default_location_type, default_location_name, default_location_address,
                default_category, default_duration_minutes, tags, status, created_at, updated_at
            ) VALUES (
                :id, :title, :slug, :description, :start_date, :end_date, :start_time, :end_time,
                :rrule, :exdates, :default_location_type, :default_location_name, :default_location_address,
                :default_category, :default_duration_minutes, :tags, :status, :created_at, :updated_at
            )'
        );

        $statement->execute(array_merge($defaults, $series));
    }

    /**
     * @param array<string, mixed> $event
     */
    private function insertEvent(array $event): void
    {
        $defaults = [
            'description' => '',
            'content' => '',
            'timezone' => 'Europe/Berlin',
            'is_recurring' => 0,
            'rrule' => null,
            'recurrence_end_date' => null,
            'parent_event_id' => null,
            'override_type' => null,
            'cancellation_reason' => null,
            'location_type' => 'physical',
            'location_name' => '',
            'location_address' => '',
            'location_url' => '',
            'location_instructions' => '',
            'category' => 'stammtisch',
            'difficulty_level' => 'all',
            'max_participants' => null,
            'current_participants' => 0,
            'age_restriction' => 18,
            'requirements' => '',
            'safety_notes' => '',
            'preparation_notes' => '',
            'is_featured' => 0,
            'requires_registration' => 1,
            'registration_deadline' => null,
            'organizer_name' => '',
            'organizer_email' => '',
            'organizer_bio' => '',
            'tags' => '[]',
            'meta_description' => '',
            'image_url' => '',
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
            'series_id' => null,
            'instance_date' => null,
        ];

        $statement = $this->connection->prepare(
            'INSERT INTO events (
                id, title, slug, description, content, start_datetime, end_datetime, timezone,
                is_recurring, rrule, recurrence_end_date, parent_event_id, override_type,
                cancellation_reason, location_type, location_name, location_address, location_url,
                location_instructions, category, difficulty_level, max_participants,
                current_participants, age_restriction, requirements, safety_notes, preparation_notes,
                status, is_featured, requires_registration, registration_deadline, organizer_name,
                organizer_email, organizer_bio, tags, meta_description, image_url, created_at,
                updated_at, series_id, instance_date
            ) VALUES (
                :id, :title, :slug, :description, :content, :start_datetime, :end_datetime, :timezone,
                :is_recurring, :rrule, :recurrence_end_date, :parent_event_id, :override_type,
                :cancellation_reason, :location_type, :location_name, :location_address, :location_url,
                :location_instructions, :category, :difficulty_level, :max_participants,
                :current_participants, :age_restriction, :requirements, :safety_notes, :preparation_notes,
                :status, :is_featured, :requires_registration, :registration_deadline, :organizer_name,
                :organizer_email, :organizer_bio, :tags, :meta_description, :image_url, :created_at,
                :updated_at, :series_id, :instance_date
            )'
        );

        $statement->execute(array_merge($defaults, $event));
    }
}
