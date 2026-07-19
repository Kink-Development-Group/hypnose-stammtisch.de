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
}
