<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\AdminEventsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AdminEventsControllerTimezoneTest extends TestCase
{
    private ReflectionMethod $formatEventForAdminResponseMethod;
    private ReflectionMethod $normalizeEventTimezoneMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatEventForAdminResponseMethod = new ReflectionMethod(
            AdminEventsController::class,
            'formatEventForAdminResponse'
        );
        $this->formatEventForAdminResponseMethod->setAccessible(true);

        $this->normalizeEventTimezoneMethod = new ReflectionMethod(
            AdminEventsController::class,
            'normalizeEventTimezone'
        );
        $this->normalizeEventTimezoneMethod->setAccessible(true);
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function formatEventForAdminResponse(array $event): array
    {
        return $this->formatEventForAdminResponseMethod->invoke(null, $event);
    }

    private function normalizeEventTimezone(mixed $timezone): string
    {
        return $this->normalizeEventTimezoneMethod->invoke(null, $timezone);
    }

    public function testConvertsUtcToEventTimezone(): void
    {
        $formatted = $this->formatEventForAdminResponse([
            'start_datetime' => '2026-04-22 17:00:00',
            'end_datetime' => '2026-04-22 21:00:00',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->assertSame('2026-04-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-04-22T23:00:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testFormatEventForAdminResponseFallsBackToBerlinTimezone(): void
    {
        $formatted = $this->formatEventForAdminResponse([
            'start_datetime' => '2026-01-22 18:00:00',
            'end_datetime' => '2026-01-22 20:00:00',
        ]);

        $this->assertSame('2026-01-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-01-22T21:00:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testFormatEventForAdminResponseFallsBackFromInvalidTimezone(): void
    {
        $formatted = $this->formatEventForAdminResponse([
            'start_datetime' => '2026-01-22 18:00:00',
            'end_datetime' => '2026-01-22 20:00:00',
            'timezone' => 'Invalid/Timezone',
        ]);

        $this->assertSame('2026-01-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-01-22T21:00:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testFormatEventForAdminResponseTrimsValidTimezone(): void
    {
        $formatted = $this->formatEventForAdminResponse([
            'start_datetime' => '2026-04-22 17:00:00',
            'end_datetime' => '2026-04-22 21:00:00',
            'timezone' => ' Europe/Berlin ',
        ]);

        $this->assertSame('2026-04-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-04-22T23:00:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testFormatEventForAdminResponseNormalizesTimezoneWithoutDatetimes(): void
    {
        $formatted = $this->formatEventForAdminResponse([
            'title' => 'Nur Zeitzone',
            'timezone' => 'Invalid/Timezone',
        ]);

        $this->assertSame('Nur Zeitzone', $formatted['title']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
        $this->assertArrayNotHasKey('start_datetime', $formatted);
        $this->assertArrayNotHasKey('end_datetime', $formatted);
    }

    public function testNormalizeEventTimezoneFallsBackFromInvalidTimezone(): void
    {
        $this->assertSame('Europe/Berlin', $this->normalizeEventTimezone('Invalid/Timezone'));
    }

    public function testNormalizeEventTimezoneTrimsValidTimezone(): void
    {
        $this->assertSame('Europe/Berlin', $this->normalizeEventTimezone(' Europe/Berlin '));
    }
}
