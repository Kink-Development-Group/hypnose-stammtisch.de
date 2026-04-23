<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\EventsController;
use HypnoseStammtisch\Models\Event;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EventsControllerPublicDateFormattingTest extends TestCase
{
    private EventsController $controller;
    private ReflectionMethod $eventToArrayMethod;
    private ReflectionMethod $formatStoredEventForPublicResponseMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new EventsController();

        $this->eventToArrayMethod = new ReflectionMethod(EventsController::class, 'eventToArray');
        $this->eventToArrayMethod->setAccessible(true);

        $this->formatStoredEventForPublicResponseMethod = new ReflectionMethod(
            EventsController::class,
            'formatStoredEventForPublicResponse'
        );
        $this->formatStoredEventForPublicResponseMethod->setAccessible(true);
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function formatStoredEventForPublicResponse(array $event): array
    {
        return $this->formatStoredEventForPublicResponseMethod->invoke($this->controller, $event);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventToArray(object $event): array
    {
        return $this->eventToArrayMethod->invoke($this->controller, $event);
    }

    public function testFormatStoredEventForPublicResponseConvertsUtcToEventTimezone(): void
    {
        $formatted = $this->formatStoredEventForPublicResponse([
            'start_datetime' => '2026-04-22 17:00:00',
            'end_datetime' => '2026-04-22 20:30:00',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->assertSame('2026-04-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-04-22T22:30:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testEventToArrayFormatsPersistedEventModelDatesForPublicOutput(): void
    {
        $event = Event::fromArray([
            'id' => 'event-1',
            'title' => 'Berlin Evening Event',
            'slug' => 'berlin-evening-event',
            'start_datetime' => '2026-04-22 17:00:00',
            'end_datetime' => '2026-04-22 20:30:00',
            'timezone' => 'Europe/Berlin',
            'status' => 'published',
        ]);

        $formatted = $this->eventToArray($event);

        $this->assertSame('2026-04-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-04-22T22:30:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }
}
