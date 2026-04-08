<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\EventsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EventsControllerSeriesOverrideTest extends TestCase
{
    private EventsController $controller;
    private ReflectionMethod $isExplicitSeriesOverrideMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new EventsController();
        $this->isExplicitSeriesOverrideMethod = new ReflectionMethod(
            EventsController::class,
            'isExplicitSeriesOverride'
        );
        $this->isExplicitSeriesOverrideMethod->setAccessible(true);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function isExplicitSeriesOverride(array $event): bool
    {
        return $this->isExplicitSeriesOverrideMethod->invoke($this->controller, $event);
    }

    public function testIgnoresMaterializedSeriesInstanceWithoutOverrideType(): void
    {
        $this->assertFalse($this->isExplicitSeriesOverride([
            'series_id' => 'series-1',
            'instance_date' => '2026-05-01',
            'title' => 'Generated instance'
        ]));
    }

    public function testAcceptsChangedSeriesOverride(): void
    {
        $this->assertTrue($this->isExplicitSeriesOverride([
            'series_id' => 'series-1',
            'instance_date' => '2026-05-01',
            'override_type' => 'changed'
        ]));
    }

    public function testAcceptsCancelledSeriesOverride(): void
    {
        $this->assertTrue($this->isExplicitSeriesOverride([
            'series_id' => 'series-1',
            'instance_date' => '2026-05-01',
            'override_type' => 'cancelled'
        ]));
    }
}
