<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\AdminEventsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AdminEventsControllerTimezoneTest extends TestCase
{
    public function testFormatEventForAdminResponseConvertsUtcToEventTimezone(): void
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
    }
}
