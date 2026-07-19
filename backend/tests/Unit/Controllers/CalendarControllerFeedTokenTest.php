<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\CalendarController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The public feed is requested without a token from both routes
 * (/calendar/feed with no path segment, /calendar.ics with no ?token), so the
 * controller receives null. Under strict_types that null must never reach a
 * string function - strcasecmp() raises a TypeError rather than coercing, which
 * took the whole feed down with a fatal error.
 */
class CalendarControllerFeedTokenTest extends TestCase
{
  private ReflectionMethod $normalize;

  protected function setUp(): void
  {
    parent::setUp();

    $this->normalize = new ReflectionMethod(CalendarController::class, 'normalizeFeedToken');
  }

  private function normalize(?string $token): ?string
  {
    return $this->normalize->invoke(null, $token);
  }

  public function testNullIsTreatedAsThePublicFeed(): void
  {
    // Regression: this raised "strcasecmp(): Argument #1 must be of type
    // string, null given" and returned a fatal instead of the calendar.
    $this->assertNull($this->normalize(null));
  }

  public function testEmptyAndWhitespaceTokensAreTreatedAsPublic(): void
  {
    $this->assertNull($this->normalize(''));
    $this->assertNull($this->normalize('   '));
  }

  public function testLiteralPublicIsTreatedAsPublicRegardlessOfCase(): void
  {
    $this->assertNull($this->normalize('public'));
    $this->assertNull($this->normalize('PUBLIC'));
    $this->assertNull($this->normalize('Public'));
  }

  public function testRealTokenIsPreservedAndTrimmed(): void
  {
    $this->assertSame('abc123', $this->normalize('abc123'));
    $this->assertSame('abc123', $this->normalize('  abc123  '));
  }

  public function testTokenContainingPublicIsNotSwallowed(): void
  {
    $this->assertSame('public-feed-token', $this->normalize('public-feed-token'));
  }
}
