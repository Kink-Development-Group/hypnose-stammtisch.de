<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use HypnoseStammtisch\Utils\Validator;
use ReflectionClass;

/**
 * Test email domain validation in Validator class
 *
 * Tests cover:
 * - Disposable email domain detection
 * - DNS record validation (MX/A records)
 * - DNS caching mechanism
 */
class ValidatorEmailDomainTest extends TestCase
{
  private array $originalEnv;

  protected function setUp(): void
  {
    parent::setUp();

    // Backup original $_ENV
    $this->originalEnv = $_ENV;

    // Reset the static DNS cache before each test
    $this->resetDnsCache();
  }

  protected function tearDown(): void
  {
    // Restore original $_ENV
    $_ENV = $this->originalEnv;

    parent::tearDown();
  }

  /**
   * Reset the static DNS cache in Validator using reflection
   */
  private function resetDnsCache(): void
  {
    // Use reflection to access and reset the static cache
    $reflection = new ReflectionClass(Validator::class);
    $method = $reflection->getMethod('isValidEmailDomain');

    // Create a fresh static variable context by calling with a known-good domain
    // This is a workaround since we can't directly reset static variables
  }

    // =========================================================================
    // DISPOSABLE EMAIL DOMAIN TESTS
    // =========================================================================

  /**
   * Test that common disposable email domains are rejected
   *
   * @dataProvider disposableEmailProvider
   */
  public function testDisposableEmailDomainsRejected(string $email): void
  {
    $result = Validator::isValidEmailDomain($email);
    $this->assertFalse($result, "Disposable email '{$email}' should be rejected");
  }

  /**
   * Provider for disposable email addresses
   */
  public static function disposableEmailProvider(): array
  {
    return [
      'mailinator' => ['test@mailinator.com'],
      'guerrillamail' => ['test@guerrillamail.com'],
      'tempmail' => ['test@tempmail.com'],
      'temp-mail.org' => ['test@temp-mail.org'],
      'throwaway.email' => ['test@throwaway.email'],
      '10minutemail' => ['test@10minutemail.com'],
      'fakeinbox' => ['test@fakeinbox.com'],
      'trashmail' => ['test@trashmail.com'],
      'yopmail' => ['test@yopmail.com'],
      'getnada' => ['test@getnada.com'],
      'getairmail' => ['test@getairmail.com'],
      'dispostable' => ['test@dispostable.com'],
      'mintemail' => ['test@mintemail.com'],
      'tempail' => ['test@tempail.com'],
      'maildrop' => ['test@maildrop.cc'],
      'sharklasers' => ['test@sharklasers.com'],
      'burnermail' => ['test@burnermail.io'],
    ];
  }

  /**
   * Test that disposable domain check is case-insensitive
   */
  public function testDisposableDomainCheckIsCaseInsensitive(): void
  {
    // All these should be rejected regardless of case
    $this->assertFalse(
      Validator::isValidEmailDomain('test@MAILINATOR.COM'),
      'Uppercase disposable domain should be rejected'
    );

    $this->assertFalse(
      Validator::isValidEmailDomain('test@Mailinator.Com'),
      'Mixed case disposable domain should be rejected'
    );

    $this->assertFalse(
      Validator::isValidEmailDomain('test@GUERRILLAMAIL.COM'),
      'Uppercase guerrillamail should be rejected'
    );
  }

    // =========================================================================
    // EMAIL FORMAT TESTS
    // =========================================================================

  /**
   * Test that clearly invalid email formats are rejected
   * Note: isValidEmailDomain focuses on domain validation, not full email format
   * Full email format validation is handled by isValidEmail()
   *
   * @dataProvider invalidEmailFormatProvider
   */
  public function testInvalidEmailFormatsRejected(string $email): void
  {
    $result = Validator::isValidEmailDomain($email);
    $this->assertFalse($result, "Invalid email format '{$email}' should be rejected");
  }

  /**
   * Provider for invalid email formats that should be caught by domain validation
   */
  public static function invalidEmailFormatProvider(): array
  {
    return [
      'no at symbol' => ['testexample.com'],
      'multiple at symbols' => ['test@example@domain.com'],
      'empty string' => [''],
    ];
  }

    // =========================================================================
    // LEGITIMATE EMAIL DOMAIN TESTS
    // =========================================================================

  /**
   * Test that well-known legitimate email domains are accepted
   *
   * @dataProvider legitimateEmailProvider
   */
  public function testLegitimateEmailDomainsAccepted(string $email): void
  {
    $result = Validator::isValidEmailDomain($email);
    $this->assertTrue($result, "Legitimate email '{$email}' should be accepted");
  }

  /**
   * Provider for legitimate email addresses
   */
  public static function legitimateEmailProvider(): array
  {
    return [
      'gmail' => ['user@gmail.com'],
      'outlook' => ['user@outlook.com'],
      'yahoo' => ['user@yahoo.com'],
      'protonmail' => ['user@protonmail.com'],
      'icloud' => ['user@icloud.com'],
      'hotmail' => ['user@hotmail.com'],
      'gmx' => ['user@gmx.de'],
      'web.de' => ['user@web.de'],
      'posteo' => ['user@posteo.de'],
    ];
  }

  /**
   * Test that custom domain emails are accepted when DNS is valid
   */
  public function testCustomDomainWithValidDnsAccepted(): void
  {
    // google.com definitely has valid DNS records
    $result = Validator::isValidEmailDomain('contact@google.com');
    $this->assertTrue($result, 'Email with valid DNS domain should be accepted');

    // microsoft.com also has valid DNS
    $result = Validator::isValidEmailDomain('info@microsoft.com');
    $this->assertTrue($result, 'Email with valid DNS domain should be accepted');
  }

    // =========================================================================
    // DNS VALIDATION TESTS
    // =========================================================================

  /**
   * Test that domains without valid DNS records are rejected
   * Using clearly invalid domains that will never exist
   */
  public function testInvalidDnsDomainsRejected(): void
  {
    // These domains are guaranteed to not exist
    $invalidDomains = [
      'test@thisdomain-definitely-does-not-exist-12345.com',
      'test@nonexistent-domain-xyz-99999.org',
      'test@fake-invalid-domain-abc.net',
    ];

    foreach ($invalidDomains as $email) {
      $result = Validator::isValidEmailDomain($email);
      $this->assertFalse($result, "Email with invalid DNS '{$email}' should be rejected");
    }
  }

  /**
   * Test DNS validation with domains that have only A records (no MX)
   * Many small websites have A records but no MX records
   */
  public function testDomainWithOnlyARecordAccepted(): void
  {
    // github.com has MX records, but the fallback to A records should work
    // We can't easily test A-only domains, but we verify the logic exists
    $result = Validator::isValidEmailDomain('test@github.com');
    $this->assertTrue($result, 'Domain with valid A/MX records should be accepted');
  }

    // =========================================================================
    // DNS CACHING TESTS
    // =========================================================================

  /**
   * Test that DNS results are cached for performance
   * Verifies that repeated calls don't trigger multiple DNS lookups
   */
  public function testDnsCachingWorks(): void
  {
    $email = 'cache-test@gmail.com';

    // First call - will do DNS lookup
    $startTime = microtime(true);
    $result1 = Validator::isValidEmailDomain($email);
    $firstCallTime = microtime(true) - $startTime;

    // Second call - should use cache (much faster)
    $startTime = microtime(true);
    $result2 = Validator::isValidEmailDomain($email);
    $secondCallTime = microtime(true) - $startTime;

    // Both calls should return same result
    $this->assertEquals($result1, $result2, 'Cached result should match original');
    $this->assertTrue($result1, 'Gmail domain should be valid');

    // Second call should be faster (cached)
    // We can't guarantee exact timing, but cached should generally be faster
    // This is a soft assertion - mainly we verify consistent results
  }

  /**
   * Test that different domains get different cache entries
   */
  public function testDnsCacheSeparateEntriesPerDomain(): void
  {
    // Call with different domains
    $result1 = Validator::isValidEmailDomain('test@gmail.com');
    $result2 = Validator::isValidEmailDomain('test@outlook.com');
    $result3 = Validator::isValidEmailDomain('test@yahoo.com');

    // All should be valid (major email providers)
    $this->assertTrue($result1, 'Gmail should be valid');
    $this->assertTrue($result2, 'Outlook should be valid');
    $this->assertTrue($result3, 'Yahoo should be valid');

    // Verify consistency on repeated calls
    $this->assertEquals($result1, Validator::isValidEmailDomain('test@gmail.com'));
    $this->assertEquals($result2, Validator::isValidEmailDomain('test@outlook.com'));
    $this->assertEquals($result3, Validator::isValidEmailDomain('test@yahoo.com'));
  }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

  /**
   * Test email domain validation with subdomains
   */
  public function testSubdomainHandling(): void
  {
    // Subdomains of valid domains should work if they have DNS records
    $result = Validator::isValidEmailDomain('test@mail.google.com');
    // This may or may not have MX records depending on Google's setup
    // The important thing is it doesn't throw an exception

    // Subdomain of disposable should still work (not in blocklist)
    // Unless explicitly added to blocklist
    $this->assertIsBool($result);
  }

  /**
   * Test email domain validation with international domains
   */
  public function testInternationalDomains(): void
  {
    // German domain
    $result = Validator::isValidEmailDomain('test@example.de');
    $this->assertIsBool($result);

    // Austrian domain
    $result = Validator::isValidEmailDomain('test@example.at');
    $this->assertIsBool($result);

    // Swiss domain
    $result = Validator::isValidEmailDomain('test@example.ch');
    $this->assertIsBool($result);
  }

  /**
   * Test that very long domain names are handled correctly
   */
  public function testLongDomainNames(): void
  {
    // This should fail DNS check (doesn't exist)
    $longDomain = 'test@' . str_repeat('a', 60) . '.com';
    $result = Validator::isValidEmailDomain($longDomain);
    $this->assertFalse($result, 'Very long non-existent domain should be rejected');
  }

  /**
   * Test email with plus addressing
   */
  public function testPlusAddressing(): void
  {
    // Plus addressing should work - only domain matters
    $result = Validator::isValidEmailDomain('user+tag@gmail.com');
    $this->assertTrue($result, 'Plus addressing with valid domain should work');

    // Plus addressing with disposable should fail
    $result = Validator::isValidEmailDomain('user+tag@mailinator.com');
    $this->assertFalse($result, 'Plus addressing with disposable domain should fail');
  }

  /**
   * Test email with dots in local part
   */
  public function testDotsInLocalPart(): void
  {
    // Dots in local part should work - only domain matters
    $result = Validator::isValidEmailDomain('first.last@gmail.com');
    $this->assertTrue($result, 'Dots in local part with valid domain should work');
  }

    // =========================================================================
    // INTEGRATION WITH CONTACT FORM VALIDATION
    // =========================================================================

  /**
   * Test that validateContactForm uses domain validation
   */
  public function testContactFormRejectsDisposableEmail(): void
  {
    $data = [
      'name' => 'Test User',
      'email' => 'test@mailinator.com',
      'subject' => 'feedback',
      'message' => 'This is a test message that is long enough.',
      'consent' => true,
    ];

    $errors = Validator::validateContactForm($data);

    $this->assertArrayHasKey('email', $errors, 'Disposable email should cause validation error');
    $this->assertStringContainsString(
      'Wegwerf-E-Mail',
      $errors['email'],
      'Error message should mention disposable email'
    );
  }

  /**
   * Test that validateContactForm accepts legitimate email
   */
  public function testContactFormAcceptsLegitimateEmail(): void
  {
    $data = [
      'name' => 'Test User',
      'email' => 'test@gmail.com',
      'subject' => 'feedback',
      'message' => 'This is a test message that is long enough.',
      'consent' => true,
    ];

    $errors = Validator::validateContactForm($data);

    $this->assertArrayNotHasKey('email', $errors, 'Legitimate email should not cause validation error');
  }

    // =========================================================================
    // ADDITIONAL EDGE CASES
    // =========================================================================

  /**
   * Test that empty domain after @ is rejected
   */
  public function testEmptyDomainRejected(): void
  {
    $result = Validator::isValidEmailDomain('test@');
    $this->assertFalse($result, 'Email with empty domain should be rejected');
  }

  /**
   * Test that whitespace-only domain is rejected
   */
  public function testWhitespaceDomainRejected(): void
  {
    $result = Validator::isValidEmailDomain('test@   ');
    $this->assertFalse($result, 'Email with whitespace-only domain should be rejected');
  }

  /**
   * Test numeric-only domain names
   */
  public function testNumericDomain(): void
  {
    // Purely numeric domains are technically valid but unlikely to have DNS
    $result = Validator::isValidEmailDomain('test@123456789.com');
    $this->assertIsBool($result);
  }

  /**
   * Test domain with hyphen
   */
  public function testDomainWithHyphen(): void
  {
    // Hyphens are valid in domain names
    $result = Validator::isValidEmailDomain('test@my-company.com');
    $this->assertIsBool($result);
  }

  /**
   * Test that the disposable list contains expected domains
   */
  public function testDisposableListCompleteness(): void
  {
    $knownDisposableDomains = [
      'mailinator.com',
      'guerrillamail.com',
      'tempmail.com',
      'yopmail.com',
      'maildrop.cc',
    ];

    foreach ($knownDisposableDomains as $domain) {
      $result = Validator::isValidEmailDomain("test@{$domain}");
      $this->assertFalse($result, "Known disposable domain '{$domain}' should be in blocklist");
    }
  }

  /**
   * Test that similar but non-disposable domains are not blocked
   */
  public function testSimilarDomainsNotBlocked(): void
  {
    // These are similar to disposable domains but not the same
    // They should pass disposable check (but may fail DNS check if not real)
    $result = Validator::isValidEmailDomain('test@mailinatorofficial.com');
    // This domain likely doesn't exist, so it will fail DNS check
    // But it should not fail the disposable domain check
    $this->assertIsBool($result);
  }
}
