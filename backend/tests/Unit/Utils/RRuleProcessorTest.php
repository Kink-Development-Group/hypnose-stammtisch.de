<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use Carbon\Carbon;
use HypnoseStammtisch\Utils\RRuleProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the RRuleProcessor utility class
 */
class RRuleProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00', 'Europe/Berlin'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // parseRRule Tests
    // =========================================================================

    public function testParseRRuleBasic(): void
    {
        $result = RRuleProcessor::parseRRule('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');

        $this->assertEquals('WEEKLY', $result['FREQ']);
        $this->assertEquals('1', $result['INTERVAL']);
        $this->assertEquals('TU', $result['BYDAY']);
    }

    public function testParseRRuleWithCount(): void
    {
        $result = RRuleProcessor::parseRRule('FREQ=DAILY;COUNT=10');

        $this->assertEquals('DAILY', $result['FREQ']);
        $this->assertEquals('10', $result['COUNT']);
    }

    public function testParseRRuleWithUntil(): void
    {
        $result = RRuleProcessor::parseRRule('FREQ=MONTHLY;UNTIL=20251231');

        $this->assertEquals('MONTHLY', $result['FREQ']);
        $this->assertEquals('20251231', $result['UNTIL']);
    }

    public function testParseRRuleWithBySetPos(): void
    {
        $result = RRuleProcessor::parseRRule('FREQ=MONTHLY;BYDAY=TU;BYSETPOS=1');

        $this->assertEquals('MONTHLY', $result['FREQ']);
        $this->assertEquals('TU', $result['BYDAY']);
        $this->assertEquals('1', $result['BYSETPOS']);
    }

    // =========================================================================
    // parseByDay Tests
    // =========================================================================

    public function testParseByDaySimple(): void
    {
        $result = RRuleProcessor::parseByDay('MO,TU,WE');

        $this->assertEquals(['MO', 'TU', 'WE'], $result['days']);
        $this->assertNull($result['positions']['MO']);
        $this->assertNull($result['positions']['TU']);
        $this->assertNull($result['positions']['WE']);
    }

    public function testParseByDayWithPosition(): void
    {
        $result = RRuleProcessor::parseByDay('1TU');

        $this->assertEquals(['TU'], $result['days']);
        $this->assertEquals(1, $result['positions']['TU']);
    }

    public function testParseByDayWithNegativePosition(): void
    {
        $result = RRuleProcessor::parseByDay('-1FR');

        $this->assertEquals(['FR'], $result['days']);
        $this->assertEquals(-1, $result['positions']['FR']);
    }

    public function testParseByDayMixed(): void
    {
        $result = RRuleProcessor::parseByDay('1MO,2TU,-1FR');

        $this->assertEquals(['MO', 'TU', 'FR'], $result['days']);
        $this->assertEquals(1, $result['positions']['MO']);
        $this->assertEquals(2, $result['positions']['TU']);
        $this->assertEquals(-1, $result['positions']['FR']);
    }

    // =========================================================================
    // validateRRule Tests
    // =========================================================================

    public function testValidateRRuleValid(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');
        $this->assertEmpty($errors);
    }

    public function testValidateRRuleMissingFreq(): void
    {
        $errors = RRuleProcessor::validateRRule('INTERVAL=1;BYDAY=TU');
        $this->assertContains('FREQ is required', $errors);
    }

    public function testValidateRRuleInvalidFreq(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=HOURLY;INTERVAL=1');
        $this->assertContains('Invalid FREQ value', $errors);
    }

    public function testValidateRRuleInvalidInterval(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=WEEKLY;INTERVAL=0');
        $this->assertContains('INTERVAL must be between 1 and 366', $errors);
    }

    public function testValidateRRuleInvalidCount(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=DAILY;COUNT=0');
        $this->assertContains('COUNT must be between 1 and 1000', $errors);
    }

    public function testValidateRRuleInvalidBySetPos(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=MONTHLY;BYDAY=TU;BYSETPOS=0');
        $this->assertContains('BYSETPOS must be between -5 and 5 (excluding 0)', $errors);
    }

    public function testValidateRRuleConflictingRules(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=MONTHLY;BYSETPOS=1;BYMONTHDAY=15');
        $this->assertContains('BYSETPOS and BYMONTHDAY cannot be used together', $errors);
    }

    public function testValidateRRuleValidMonthlyBySetPos(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=MONTHLY;BYDAY=TU;BYSETPOS=1');
        $this->assertEmpty($errors);
    }

    public function testValidateRRuleValidLastWeekday(): void
    {
        $errors = RRuleProcessor::validateRRule('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1');
        $this->assertEmpty($errors);
    }

    // =========================================================================
    // buildRRule Tests
    // =========================================================================

    public function testBuildRRuleBasic(): void
    {
        $result = RRuleProcessor::buildRRule([
            'FREQ' => 'WEEKLY',
            'INTERVAL' => '1',
            'BYDAY' => 'TU'
        ]);

        $this->assertStringContainsString('FREQ=WEEKLY', $result);
        $this->assertStringContainsString('INTERVAL=1', $result);
        $this->assertStringContainsString('BYDAY=TU', $result);
    }

    public function testBuildRRuleWithBySetPos(): void
    {
        $result = RRuleProcessor::buildRRule([
            'FREQ' => 'MONTHLY',
            'BYDAY' => 'TU',
            'BYSETPOS' => '1'
        ]);

        $this->assertStringContainsString('FREQ=MONTHLY', $result);
        $this->assertStringContainsString('BYDAY=TU', $result);
        $this->assertStringContainsString('BYSETPOS=1', $result);
    }

    public function testBuildRRuleFreqComesFirst(): void
    {
        $result = RRuleProcessor::buildRRule([
            'INTERVAL' => '2',
            'FREQ' => 'MONTHLY',
            'BYDAY' => 'MO'
        ]);

        $this->assertStringStartsWith('FREQ=MONTHLY', $result);
    }

    // =========================================================================
    // describeRRule Tests
    // =========================================================================

    public function testDescribeRRuleWeekly(): void
    {
        $result = RRuleProcessor::describeRRule('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');

        $this->assertStringContainsString('Wöchentlich', $result);
        $this->assertStringContainsString('Dienstag', $result);
    }

    public function testDescribeRRuleMonthlyBySetPos(): void
    {
        $result = RRuleProcessor::describeRRule('FREQ=MONTHLY;BYDAY=TU;BYSETPOS=1');

        $this->assertStringContainsString('Monatlich', $result);
        $this->assertStringContainsString('ersten', $result);
        $this->assertStringContainsString('Dienstag', $result);
    }

    public function testDescribeRRuleLastFriday(): void
    {
        $result = RRuleProcessor::describeRRule('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1');

        $this->assertStringContainsString('Monatlich', $result);
        $this->assertStringContainsString('letzten', $result);
        $this->assertStringContainsString('Freitag', $result);
    }

    public function testDescribeRRuleWithCount(): void
    {
        $result = RRuleProcessor::describeRRule('FREQ=DAILY;COUNT=10');

        $this->assertStringContainsString('Täglich', $result);
        $this->assertStringContainsString('10 Termine', $result);
    }

    public function testDescribeRRuleWithUntil(): void
    {
        $result = RRuleProcessor::describeRRule('FREQ=WEEKLY;UNTIL=20251231');

        $this->assertStringContainsString('Wöchentlich', $result);
        $this->assertStringContainsString('31.12.2025', $result);
    }

    // =========================================================================
    // expandRecurringEvent Tests
    // =========================================================================

    public function testExpandWeeklyEvent(): void
    {
        $event = [
            'id' => 'test-1',
            'title' => 'Weekly Meeting',
            'start_datetime' => '2025-01-07 19:00:00', // Tuesday
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Should have 4 Tuesdays in January 2025: 7, 14, 21, 28
        $this->assertCount(4, $instances);

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-07', $dates);
        $this->assertContains('2025-01-14', $dates);
        $this->assertContains('2025-01-21', $dates);
        $this->assertContains('2025-01-28', $dates);
    }

    public function testExpandMonthlyFirstTuesday(): void
    {
        $event = [
            'id' => 'test-2',
            'title' => 'First Tuesday Meeting',
            'start_datetime' => '2025-01-07 19:00:00', // First Tuesday of Jan 2025
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=MONTHLY;BYDAY=TU;BYSETPOS=1'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-06-30');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // First Tuesdays: Jan 7, Feb 4, Mar 4, Apr 1, May 6, Jun 3
        $this->assertGreaterThanOrEqual(5, count($instances));

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-07', $dates);
        $this->assertContains('2025-02-04', $dates);
        $this->assertContains('2025-03-04', $dates);
    }

    public function testExpandMonthlyLastFriday(): void
    {
        $event = [
            'id' => 'test-3',
            'title' => 'Last Friday Meeting',
            'start_datetime' => '2025-01-31 18:00:00', // Last Friday of Jan 2025
            'end_datetime' => '2025-01-31 20:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-04-30');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Last Fridays: Jan 31, Feb 28, Mar 28, Apr 25
        $this->assertGreaterThanOrEqual(3, count($instances));

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-31', $dates);
        $this->assertContains('2025-02-28', $dates);
    }

    public function testExpandMonthlyThirdMonday(): void
    {
        $event = [
            'id' => 'test-4',
            'title' => 'Third Monday Meeting',
            'start_datetime' => '2025-01-20 19:00:00', // Third Monday of Jan 2025
            'end_datetime' => '2025-01-20 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=3'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-03-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Third Mondays: Jan 20, Feb 17, Mar 17
        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-20', $dates);
        $this->assertContains('2025-02-17', $dates);
        $this->assertContains('2025-03-17', $dates);
    }

    public function testExpandWithExdates(): void
    {
        $event = [
            'id' => 'test-5',
            'title' => 'Weekly with Exception',
            'start_datetime' => '2025-01-07 19:00:00',
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');
        $exdates = ['2025-01-14']; // Skip second Tuesday

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end, $exdates);

        // Should have 3 Tuesdays (skipped Jan 14)
        $this->assertCount(3, $instances);

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertNotContains('2025-01-14', $dates);
        $this->assertContains('2025-01-07', $dates);
        $this->assertContains('2025-01-21', $dates);
        $this->assertContains('2025-01-28', $dates);
    }

    public function testExpandWithCount(): void
    {
        $event = [
            'id' => 'test-6',
            'title' => 'Limited Series',
            'start_datetime' => '2025-01-07 19:00:00',
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU;COUNT=3'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-12-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Should have exactly 3 instances
        $this->assertCount(3, $instances);
    }

    public function testExpandWithUntil(): void
    {
        $event = [
            'id' => 'test-7',
            'title' => 'Until Date Series',
            'start_datetime' => '2025-01-07 19:00:00',
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU;UNTIL=20250120'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-12-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Should have 2 instances (Jan 7 and Jan 14, not Jan 21 which is after UNTIL)
        $this->assertCount(2, $instances);

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-07', $dates);
        $this->assertContains('2025-01-14', $dates);
    }

    public function testExpandMonthlyByMonthDay(): void
    {
        $event = [
            'id' => 'test-8',
            'title' => '15th of Month',
            'start_datetime' => '2025-01-15 19:00:00',
            'end_datetime' => '2025-01-15 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=15'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-03-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Should have Jan 15, Feb 15, Mar 15
        $this->assertCount(3, $instances);

        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-15', $dates);
        $this->assertContains('2025-02-15', $dates);
        $this->assertContains('2025-03-15', $dates);
    }

    public function testExpandDailyEvent(): void
    {
        $event = [
            'id' => 'test-9',
            'title' => 'Daily Meeting',
            'start_datetime' => '2025-01-01 09:00:00',
            'end_datetime' => '2025-01-01 10:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=DAILY;INTERVAL=1;COUNT=5'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        $this->assertCount(5, $instances);
    }

    public function testExpandYearlyEvent(): void
    {
        $event = [
            'id' => 'test-10',
            'title' => 'Annual Event',
            'start_datetime' => '2025-06-15 10:00:00',
            'end_datetime' => '2025-06-15 12:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=YEARLY;INTERVAL=1'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2027-12-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Should have 2025, 2026, 2027
        $this->assertCount(3, $instances);
    }

    public function testExpandBiweeklyEvent(): void
    {
        $event = [
            'id' => 'test-11',
            'title' => 'Bi-weekly Meeting',
            'start_datetime' => '2025-01-07 19:00:00',
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-02-28');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Bi-weekly Tuesdays: Jan 7, Jan 21, Feb 4, Feb 18
        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-07', $dates);
        $this->assertContains('2025-01-21', $dates);
    }

    public function testExpandSecondSaturday(): void
    {
        $event = [
            'id' => 'test-12',
            'title' => 'Second Saturday Workshop',
            'start_datetime' => '2025-01-11 14:00:00', // Second Saturday of Jan 2025
            'end_datetime' => '2025-01-11 18:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=MONTHLY;BYDAY=SA;BYSETPOS=2'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-04-30');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        // Second Saturdays: Jan 11, Feb 8, Mar 8, Apr 12
        $dates = array_map(fn($i) => substr($i['start_datetime'], 0, 10), $instances);
        $this->assertContains('2025-01-11', $dates);
        $this->assertContains('2025-02-08', $dates);
        $this->assertContains('2025-03-08', $dates);
        $this->assertContains('2025-04-12', $dates);
    }

    public function testExpandPreservesEventData(): void
    {
        $event = [
            'id' => 'test-13',
            'title' => 'Event with Data',
            'description' => 'Test description',
            'start_datetime' => '2025-01-07 19:00:00',
            'end_datetime' => '2025-01-07 21:00:00',
            'timezone' => 'Europe/Berlin',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU;COUNT=1',
            'location_name' => 'Test Location',
            'category' => 'stammtisch'
        ];

        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        $instances = RRuleProcessor::expandRecurringEvent($event, $start, $end);

        $this->assertCount(1, $instances);
        $instance = $instances[0];

        $this->assertEquals('Event with Data', $instance['title']);
        $this->assertEquals('Test description', $instance['description']);
        $this->assertEquals('Test Location', $instance['location_name']);
        $this->assertEquals('stammtisch', $instance['category']);
        $this->assertTrue($instance['is_recurring_instance']);
        $this->assertEquals('test-13', $instance['parent_event_id']);
    }
}
