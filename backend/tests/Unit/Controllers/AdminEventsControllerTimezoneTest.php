<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\AdminEventsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AdminEventsControllerTimezoneTest extends TestCase
{
    public function testConvertsUtcToEventTimezone(): void
    {
        $method = new ReflectionMethod(AdminEventsController::class, 'formatEventForAdminResponse');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, [
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
        $method = new ReflectionMethod(AdminEventsController::class, 'formatEventForAdminResponse');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, [
            'start_datetime' => '2026-01-22 18:00:00',
            'end_datetime' => '2026-01-22 20:00:00',
        ]);

        $this->assertSame('2026-01-22T19:00:00', $formatted['start_datetime']);
        $this->assertSame('2026-01-22T21:00:00', $formatted['end_datetime']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
    }

    public function testFormatEventForAdminResponseFallsBackFromInvalidTimezone(): void
    {
        $method = new ReflectionMethod(AdminEventsController::class, 'formatEventForAdminResponse');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, [
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
        $method = new ReflectionMethod(AdminEventsController::class, 'formatEventForAdminResponse');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, [
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
        $method = new ReflectionMethod(AdminEventsController::class, 'formatEventForAdminResponse');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, [
            'title' => 'Nur Zeitzone',
            'timezone' => 'Invalid/Timezone',
        ]);

        $this->assertSame('Nur Zeitzone', $formatted['title']);
        $this->assertSame('Europe/Berlin', $formatted['timezone']);
        $this->assertArrayNotHasKey('start_datetime', $formatted);
        $this->assertArrayNotHasKey('end_datetime', $formatted);
    }
}
