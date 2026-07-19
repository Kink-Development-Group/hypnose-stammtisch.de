<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use HypnoseStammtisch\Utils\ICSGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests that user-supplied organizer data cannot inject calendar properties.
 *
 * ORGANIZER carries the name as a quoted CN parameter, which escapeValue()
 * cannot be used on - so CTLs and DQUOTE have to be stripped instead.
 */
class ICSGeneratorOrganizerTest extends TestCase
{
  private ReflectionMethod $formatEvent;

  protected function setUp(): void
  {
    parent::setUp();

    $reflection = new ReflectionClass(ICSGenerator::class);
    $this->formatEvent = $reflection->getMethod('formatEvent');
  }

  /**
   * @param array<string, mixed> $overrides
   * @return array<int, string>
   */
  private function formatEvent(array $overrides): array
  {
    $event = array_merge([
      'id' => 1,
      'title' => 'Test Event',
      'start_datetime' => '2026-08-01 18:00:00',
      'end_datetime' => '2026-08-01 20:00:00',
    ], $overrides);

    return $this->formatEvent->invoke(null, $event);
  }

  private function organizerLine(array $lines): string
  {
    foreach ($lines as $line) {
      if (str_starts_with($line, 'ORGANIZER')) {
        return $line;
      }
    }

    return '';
  }

  public function testOrganizerUsesCnParameterWithMailtoValue(): void
  {
    $lines = $this->formatEvent([
      'organizer_name' => 'Max Mustermann',
      'organizer_email' => 'max@example.com',
    ]);

    $this->assertContains(
      'ORGANIZER;CN="Max Mustermann":MAILTO:max@example.com',
      $lines
    );
  }

  public function testOrganizerIsEmittedWithoutCnWhenNameIsMissing(): void
  {
    $lines = $this->formatEvent(['organizer_email' => 'max@example.com']);

    $this->assertContains('ORGANIZER:MAILTO:max@example.com', $lines);
  }

  public function testNewlinesInOrganizerNameCannotInjectProperties(): void
  {
    $lines = $this->formatEvent([
      'organizer_name' => "Eve\r\nATTENDEE:MAILTO:victim@example.com\r\nX-EVIL",
      'organizer_email' => 'eve@example.com',
    ]);

    $organizer = $this->organizerLine($lines);

    // The payload stays inert inside the quoted CN; what must not happen is a
    // line break turning it into a property of its own.
    $this->assertStringNotContainsString("\r", $organizer);
    $this->assertStringNotContainsString("\n", $organizer);
    foreach ($lines as $line) {
      $this->assertStringStartsNotWith('ATTENDEE', $line);
    }
  }

  public function testNewlinesInOrganizerEmailCannotInjectProperties(): void
  {
    $lines = $this->formatEvent([
      'organizer_name' => 'Eve',
      'organizer_email' => "eve@example.com\r\nDESCRIPTION:pwned",
    ]);

    $organizer = $this->organizerLine($lines);

    $this->assertSame(
      'ORGANIZER;CN="Eve":MAILTO:eve@example.comDESCRIPTION:pwned',
      $organizer
    );
    $this->assertStringNotContainsString("\n", $organizer);
  }

  public function testDoubleQuoteInOrganizerNameCannotEscapeTheCnParameter(): void
  {
    $lines = $this->formatEvent([
      'organizer_name' => 'Eve";X-EVIL=1;CN="Eve',
      'organizer_email' => 'eve@example.com',
    ]);

    $organizer = $this->organizerLine($lines);

    // Exactly one quoted region, so the extra "CN=" the payload carries stays
    // part of the value instead of becoming a second parameter.
    $this->assertSame(2, substr_count($organizer, '"'));
    $this->assertMatchesRegularExpression(
      '/^ORGANIZER;CN="[^"]*":MAILTO:eve@example\.com$/',
      $organizer
    );
  }

  public function testUmlautsInOrganizerNameSurviveSanitizing(): void
  {
    $lines = $this->formatEvent([
      'organizer_name' => 'Jörg Müller-Groß',
      'organizer_email' => 'joerg@example.com',
    ]);

    $this->assertStringContainsString(
      'CN="Jörg Müller-Groß"',
      $this->organizerLine($lines)
    );
  }

  private function lineStartingWith(array $lines, string $prefix): string
  {
    foreach ($lines as $line) {
      if (str_starts_with($line, $prefix)) {
        return $line;
      }
    }

    return '';
  }

  public function testTzidAlwaysReferencesTheDefinedVtimezone(): void
  {
    // Only Europe/Berlin has a VTIMEZONE in the feed, so a TZID pointing
    // anywhere else would be a dangling reference (RFC 5545 §3.2.19).
    $lines = $this->formatEvent([
      'timezone' => 'America/New_York',
      'start_datetime' => '2026-08-01 12:00:00',
      'end_datetime' => '2026-08-01 14:00:00',
    ]);

    // 12:00 in New York (UTC-4 in August) is 18:00 in Berlin.
    $this->assertSame(
      'DTSTART;TZID=Europe/Berlin:20260801T180000',
      $this->lineStartingWith($lines, 'DTSTART')
    );
    $this->assertSame(
      'DTEND;TZID=Europe/Berlin:20260801T200000',
      $this->lineStartingWith($lines, 'DTEND')
    );
  }

  public function testUnknownTimezoneFallsBackInsteadOfThrowing(): void
  {
    $lines = $this->formatEvent([
      'timezone' => "Europe/Berlin\r\nX-EVIL:1",
      'start_datetime' => '2026-08-01 18:00:00',
      'end_datetime' => '2026-08-01 20:00:00',
    ]);

    $this->assertSame(
      'DTSTART;TZID=Europe/Berlin:20260801T180000',
      $this->lineStartingWith($lines, 'DTSTART')
    );
    foreach ($lines as $line) {
      $this->assertStringStartsNotWith('X-EVIL', $line);
    }
  }

  public function testAllDayEventKeepsItsDateAndEmitsNoTzid(): void
  {
    $lines = $this->formatEvent([
      'is_all_day' => 1,
      'start_datetime' => '2026-08-01 00:00:00',
      'end_datetime' => '2026-08-01 00:00:00',
    ]);

    $this->assertSame(
      'DTSTART;VALUE=DATE:20260801',
      $this->lineStartingWith($lines, 'DTSTART')
    );
    $this->assertStringNotContainsString(
      'TZID',
      $this->lineStartingWith($lines, 'DTSTART')
    );
  }

  /**
   * A generated series instance as RRuleProcessor emits it: the series id
   * doubles as the event id, and parent_event_id is set to that same value.
   *
   * @return array<int, string>
   */
  private function seriesInstance(string $seriesId, string $date): array
  {
    return $this->formatEvent([
      'id' => 'series_' . $seriesId,
      'parent_event_id' => 'series_' . $seriesId,
      'series_id' => $seriesId,
      'start_datetime' => $date . ' 19:00:00',
      'end_datetime' => $date . ' 22:00:00',
    ]);
  }

  public function testSeriesInstancesGetDistinctUidsPerOccurrence(): void
  {
    // Every expanded instance of a series shares one id ("series_<id>"), so
    // without the date in the UID a client would collapse the whole series
    // into a single event.
    $july = $this->seriesInstance('abc', '2026-07-20');
    $august = $this->seriesInstance('abc', '2026-08-17');

    $this->assertSame(
      'UID:series-abc-20260720@hypnose-stammtisch.de',
      $this->lineStartingWith($july, 'UID')
    );
    $this->assertNotSame(
      $this->lineStartingWith($july, 'UID'),
      $this->lineStartingWith($august, 'UID')
    );
  }

  public function testSeriesOverrideKeepsTheUidOfTheOccurrenceItReplaces(): void
  {
    // An override is a real events row: its own id, series_id set, but
    // parent_event_id null. It replaces the generated instance for that date,
    // so it has to carry the same UID - otherwise cancelling an occurrence
    // makes the subscriber's original event vanish instead of turning into a
    // cancellation.
    $instance = $this->seriesInstance('abc', '2026-07-20');
    $override = $this->formatEvent([
      'id' => 'real-row-id',
      'parent_event_id' => null,
      'series_id' => 'abc',
      'instance_date' => '2026-07-20',
      'status' => 'cancelled',
      'start_datetime' => '2026-07-20 19:00:00',
      'end_datetime' => '2026-07-20 22:00:00',
    ]);

    $this->assertSame(
      $this->lineStartingWith($instance, 'UID'),
      $this->lineStartingWith($override, 'UID')
    );
    $this->assertContains('STATUS:CANCELLED', $override);
  }

  public function testLegacyRecurringInstancesStillKeyOnParentEvent(): void
  {
    // Non-series recurring events have no series_id and must keep the
    // parent-based UID scheme.
    $lines = $this->formatEvent([
      'id' => 'legacy-1',
      'parent_event_id' => 'legacy-base',
      'start_datetime' => '2026-07-20 19:00:00',
      'end_datetime' => '2026-07-20 22:00:00',
    ]);

    $this->assertSame(
      'UID:event-legacy-base-20260720@hypnose-stammtisch.de',
      $this->lineStartingWith($lines, 'UID')
    );
  }

  public function testStandaloneEventKeepsItsPlainUid(): void
  {
    $lines = $this->formatEvent(['id' => 'plain-1']);

    $this->assertSame(
      'UID:event-plain-1@hypnose-stammtisch.de',
      $this->lineStartingWith($lines, 'UID')
    );
  }

  public function testControlCharsInIdCannotInjectProperties(): void
  {
    $lines = $this->formatEvent(['id' => "1\r\nX-EVIL:1"]);

    $this->assertSame('UID:event-1X-EVIL:1@hypnose-stammtisch.de', $this->lineStartingWith($lines, 'UID'));
    foreach ($lines as $line) {
      $this->assertStringStartsNotWith('X-EVIL', $line);
    }
  }
}
