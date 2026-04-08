<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\EventsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EventsControllerExpandedBaseEventTest extends TestCase
{
    private EventsController $controller;
    private ReflectionMethod $shouldSkipExpandedBaseEventMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new EventsController();
        $this->shouldSkipExpandedBaseEventMethod = new ReflectionMethod(
            EventsController::class,
            'shouldSkipExpandedBaseEvent'
        );
        $this->shouldSkipExpandedBaseEventMethod->setAccessible(true);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function shouldSkipExpandedBaseEvent(array $event): bool
    {
        return $this->shouldSkipExpandedBaseEventMethod->invoke($this->controller, $event);
    }

    public function testSkipsPersistedSeriesInstanceRows(): void
    {
        $this->assertTrue($this->shouldSkipExpandedBaseEvent([
            'series_id' => 'series-1',
            'instance_date' => '2026-05-01',
            'title' => 'Persisted series instance',
        ]));
    }

    public function testKeepsStandalonePublishedEventsInBasePass(): void
    {
        $this->assertFalse($this->shouldSkipExpandedBaseEvent([
            'id' => 'event-1',
            'title' => 'Standalone event',
            'start_datetime' => '2026-05-01 19:00:00',
        ]));
    }

    public function testKeepsSeriesParentRowsWithoutInstanceDate(): void
    {
        $this->assertFalse($this->shouldSkipExpandedBaseEvent([
            'id' => 'event-2',
            'series_id' => 'series-1',
            'title' => 'Series parent row',
        ]));
    }
}
