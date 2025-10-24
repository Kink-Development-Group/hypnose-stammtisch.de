<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Simple test to verify PHPUnit configuration
 */
class PHPUnitConfigTest extends TestCase
{
  public function testPHPUnitIsWorking(): void
  {
    $this->assertTrue(true);
  }

  public function testAssertionMethods(): void
  {
    $this->assertIsString('hello');
    $this->assertIsInt(42);
    $this->assertEquals(2, 1 + 1);
  }
}
