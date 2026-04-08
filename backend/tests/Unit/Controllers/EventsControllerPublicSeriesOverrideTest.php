<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\EventsController;
use HypnoseStammtisch\Database\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class EventsControllerPublicSeriesOverrideTest extends TestCase
{
    private EventsController $controller;
    private ReflectionMethod $getExpandedEventsMethod;
    private ReflectionMethod $getSeriesOverrideMethod;
    private PDO $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new EventsController();
        $this->getExpandedEventsMethod = new ReflectionMethod(EventsController::class, 'getExpandedEvents');
        $this->getExpandedEventsMethod->setAccessible(true);
        $this->getSeriesOverrideMethod = new ReflectionMethod(EventsController::class, 'getSeriesOverride');
        $this->getSeriesOverrideMethod->setAccessible(true);

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

    public function testExpandedEventsIgnoreNonPublicSeriesRowsButApplyExplicitPublicOverrides(): void
    {
        $this->insertSeries([
            'id' => 'series-1',
            'title' => 'Series Title',
            'slug' => 'series-title',
            'description' => 'Series description',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-03',
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'exdates' => '[]',
            'default_location_type' => 'physical',
            'default_location_name' => 'Venue',
            'default_location_address' => 'Street 1',
            'default_category' => 'stammtisch',
            'default_duration_minutes' => 120,
            'tags' => '[]',
            'status' => 'published',
            'created_at' => '2026-04-01 10:00:00',
            'updated_at' => '2026-04-01 10:00:00',
        ]);

        $this->insertEvent([
            'id' => 'stale-instance',
            'title' => 'Stale materialized row',
            'slug' => 'stale-instance',
            'start_datetime' => '2026-05-01 19:00:00',
            'end_datetime' => '2026-05-01 21:00:00',
            'status' => 'published',
            'series_id' => 'series-1',
            'instance_date' => '2026-05-01',
        ]);
        $this->insertEvent([
            'id' => 'draft-override',
            'title' => 'Draft override row',
            'slug' => 'draft-override',
            'start_datetime' => '2026-05-02 19:30:00',
            'end_datetime' => '2026-05-02 21:30:00',
            'status' => 'draft',
            'override_type' => 'changed',
            'series_id' => 'series-1',
            'instance_date' => '2026-05-02',
        ]);
        $this->insertEvent([
            'id' => 'published-override',
            'title' => 'Published override row',
            'slug' => 'published-override',
            'start_datetime' => '2026-05-03 20:00:00',
            'end_datetime' => '2026-05-03 22:00:00',
            'status' => 'published',
            'override_type' => 'changed',
            'series_id' => 'series-1',
            'instance_date' => '2026-05-03',
        ]);

        $expanded = $this->getExpandedEvents([
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-04',
        ]);

        $expandedIds = array_values(array_filter(
            array_column($expanded, 'id'),
            static fn ($id): bool => is_string($id) && $id !== ''
        ));

        $seriesInstances = [];
        foreach ($expanded as $event) {
            if (($event['series_id'] ?? null) === 'series-1') {
                $seriesInstances[$event['instance_date']] = $event;
            }
        }

        $this->assertNotContains('stale-instance', $expandedIds);
        $this->assertNotContains('draft-override', $expandedIds);
        $this->assertSame(
            1,
            count(array_filter($expandedIds, static fn (string $id): bool => $id === 'published-override'))
        );
        $this->assertCount(3, $seriesInstances);
        $this->assertSame('Series Title', $seriesInstances['2026-05-01']['title']);
        $this->assertSame('Series Title', $seriesInstances['2026-05-02']['title']);
        $this->assertSame('Published override row', $seriesInstances['2026-05-03']['title']);
    }

    public function testSeriesInstanceLookupOnlyReturnsExplicitPublicOverrides(): void
    {
        $this->insertEvent([
            'id' => 'stale-instance',
            'title' => 'Stale materialized row',
            'slug' => 'stale-instance',
            'start_datetime' => '2026-05-01 19:00:00',
            'end_datetime' => '2026-05-01 21:00:00',
            'status' => 'published',
            'series_id' => 'series-lookup',
            'instance_date' => '2026-05-01',
        ]);
        $this->insertEvent([
            'id' => 'draft-override',
            'title' => 'Draft override row',
            'slug' => 'draft-override',
            'start_datetime' => '2026-05-02 19:30:00',
            'end_datetime' => '2026-05-02 21:30:00',
            'status' => 'draft',
            'override_type' => 'changed',
            'series_id' => 'series-lookup',
            'instance_date' => '2026-05-02',
        ]);
        $this->insertEvent([
            'id' => 'invalid-type-override',
            'title' => 'Invalid override type row',
            'slug' => 'invalid-type-override',
            'start_datetime' => '2026-05-03 19:30:00',
            'end_datetime' => '2026-05-03 21:30:00',
            'status' => 'published',
            'override_type' => 'rescheduled',
            'series_id' => 'series-lookup',
            'instance_date' => '2026-05-03',
        ]);
        $this->insertEvent([
            'id' => 'published-override',
            'title' => 'Published override row',
            'slug' => 'published-override',
            'start_datetime' => '2026-05-04 20:00:00',
            'end_datetime' => '2026-05-04 22:00:00',
            'status' => 'published',
            'override_type' => 'changed',
            'series_id' => 'series-lookup',
            'instance_date' => '2026-05-04',
        ]);
        $this->insertEvent([
            'id' => 'cancelled-override',
            'title' => 'Cancelled override row',
            'slug' => 'cancelled-override',
            'start_datetime' => '2026-05-05 19:00:00',
            'end_datetime' => '2026-05-05 21:00:00',
            'status' => 'cancelled',
            'override_type' => 'cancelled',
            'series_id' => 'series-lookup',
            'instance_date' => '2026-05-05',
        ]);

        $this->assertNull($this->getSeriesOverride('series-lookup', '2026-05-01'));
        $this->assertNull($this->getSeriesOverride('series-lookup', '2026-05-02'));
        $this->assertNull($this->getSeriesOverride('series-lookup', '2026-05-03'));

        $publishedOverride = $this->getSeriesOverride('series-lookup', '2026-05-04');
        $cancelledOverride = $this->getSeriesOverride('series-lookup', '2026-05-05');

        $this->assertSame('Published override row', $publishedOverride['title']);
        $this->assertSame('changed', $publishedOverride['override_type']);
        $this->assertSame('published', $publishedOverride['status']);

        $this->assertSame('Cancelled override row', $cancelledOverride['title']);
        $this->assertSame('cancelled', $cancelledOverride['override_type']);
        $this->assertSame('cancelled', $cancelledOverride['status']);
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
     * @return array<string, mixed>|null
     */
    private function getSeriesOverride(string $seriesId, string $instanceDate): ?array
    {
        return $this->getSeriesOverrideMethod->invoke($this->controller, $seriesId, $instanceDate);
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

        $statement->execute($series);
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
            'created_at' => '2026-04-01 10:00:00',
            'updated_at' => '2026-04-01 10:00:00',
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
