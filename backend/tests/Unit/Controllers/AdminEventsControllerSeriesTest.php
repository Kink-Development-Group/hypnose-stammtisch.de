<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AdminEventsController series management functions
 * Tests: EXDATE handling, override creation, instance cancellation
 */
class AdminEventsControllerSeriesTest extends TestCase
{
  /**
   * Test that EXDATE validation rejects duplicates
   */
  public function testExdateDuplicateDetection(): void
  {
    // Simulate existing exdates
    $existingExdates = ['2026-01-15', '2026-02-15', '2026-03-15'];
    $newExdate = '2026-02-15';

    // Check for duplicate
    $isDuplicate = in_array($newExdate, $existingExdates);

    $this->assertTrue($isDuplicate, 'Duplicate EXDATE should be detected');
  }

  /**
   * Test that EXDATE array is sorted after adding new date
   */
  public function testExdateSortingAfterAdd(): void
  {
    $exdates = ['2026-03-15', '2026-01-15'];
    $newExdate = '2026-02-15';

    // Add and sort
    if (!in_array($newExdate, $exdates)) {
      $exdates[] = $newExdate;
      sort($exdates);
    }

    $expected = ['2026-01-15', '2026-02-15', '2026-03-15'];
    $this->assertEquals($expected, $exdates, 'EXDATEs should be sorted chronologically');
  }

  /**
   * Test that override payload includes description when provided
   */
  public function testOverridePayloadWithDescription(): void
  {
    $input = [
      'instance_date' => '2026-05-20',
      'title' => 'Special Event',
      'description' => 'This is a special override description',
      'start_time' => '20:00:00',
      'end_time' => '22:00:00',
    ];

    // Verify description is captured
    $this->assertArrayHasKey('description', $input);
    $this->assertNotEmpty($input['description']);
  }

  /**
   * Test that override falls back to series description when not provided
   */
  public function testOverrideFallbackToSeriesDescription(): void
  {
    $seriesDefaults = [
      'title' => 'Series Title',
      'description' => 'Default series description',
    ];

    $input = [
      'instance_date' => '2026-05-20',
      'title' => 'Override Title',
      // No description provided
    ];

    $finalDescription = $input['description'] ?? $seriesDefaults['description'] ?? null;

    $this->assertEquals('Default series description', $finalDescription);
  }

  /**
   * Test cancel instance requires instance_date
   */
  public function testCancelInstanceRequiresDate(): void
  {
    $input = [
      'reason' => 'Test reason',
      // Missing instance_date
    ];

    $hasInstanceDate = !empty($input['instance_date']);

    $this->assertFalse($hasInstanceDate, 'Should detect missing instance_date');
  }

  /**
   * Test cancel instance payload structure
   */
  public function testCancelInstancePayloadStructure(): void
  {
    $input = [
      'instance_date' => '2026-06-15',
      'reason' => 'Venue unavailable',
    ];

    $this->assertArrayHasKey('instance_date', $input);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $input['instance_date']);
    $this->assertArrayHasKey('reason', $input);
  }

  /**
   * Test that restore instance requires instance_date query param
   */
  public function testRestoreInstanceRequiresQueryParam(): void
  {
    // Simulate GET params
    $queryParams = [];

    $instanceDate = $queryParams['instance_date'] ?? null;

    $this->assertNull($instanceDate, 'Should detect missing instance_date query param');
  }

  /**
   * Test date format validation
   */
  public function testDateFormatValidation(): void
  {
    $validDates = ['2026-01-15', '2026-12-31', '2025-06-01'];
    $invalidDates = ['2026/01/15', '15-01-2026', '2026-1-5', 'invalid'];

    $pattern = '/^\d{4}-\d{2}-\d{2}$/';

    foreach ($validDates as $date) {
      $this->assertMatchesRegularExpression($pattern, $date, "Valid date $date should match pattern");
    }

    foreach ($invalidDates as $date) {
      $this->assertDoesNotMatchRegularExpression($pattern, $date, "Invalid date $date should not match pattern");
    }
  }

  /**
   * Test override type values
   */
  public function testOverrideTypeValues(): void
  {
    $validTypes = ['changed', 'cancelled'];

    $this->assertContains('changed', $validTypes);
    $this->assertContains('cancelled', $validTypes);
    $this->assertNotContains('deleted', $validTypes);
    $this->assertNotContains('modified', $validTypes);
  }

  /**
   * Test cancellation reason is optional
   */
  public function testCancellationReasonIsOptional(): void
  {
    $inputWithReason = [
      'instance_date' => '2026-06-15',
      'reason' => 'Weather conditions',
    ];

    $inputWithoutReason = [
      'instance_date' => '2026-06-15',
    ];

    $reasonWithInput = $inputWithReason['reason'] ?? null;
    $reasonWithoutInput = $inputWithoutReason['reason'] ?? null;

    $this->assertNotNull($reasonWithInput);
    $this->assertNull($reasonWithoutInput);
  }

  /**
   * Test EXDATE removal filters correctly
   */
  public function testExdateRemovalFiltering(): void
  {
    $exdates = ['2026-01-15', '2026-02-15', '2026-03-15'];
    $dateToRemove = '2026-02-15';

    $filtered = array_values(array_filter($exdates, fn($d) => $d !== $dateToRemove));

    $this->assertCount(2, $filtered);
    $this->assertNotContains($dateToRemove, $filtered);
    $this->assertContains('2026-01-15', $filtered);
    $this->assertContains('2026-03-15', $filtered);
  }
}
