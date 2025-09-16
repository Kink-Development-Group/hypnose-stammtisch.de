<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Integration;

use PHPUnit\Framework\TestCase;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\FailedLoginTracker;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Config\Config;

/**
 * Integration tests for the Account Lockout & IP Blocking system
 */
class SecurityLockoutIntegrationTest extends TestCase
{
  private static $testUserId;
  private static $testHeadAdminId;
  private static $testIP = '192.168.1.100';

  public static function setUpBeforeClass(): void
  {
    // Set up test configuration
    $_ENV['MAX_FAILED_ATTEMPTS'] = '3'; // Lower for testing
    $_ENV['TIME_WINDOW_SECONDS'] = '300'; // 5 minutes
    $_ENV['IP_BAN_DURATION_SECONDS'] = '600'; // 10 minutes
    $_ENV['ACCOUNT_LOCK_DURATION_SECONDS'] = '600'; // 10 minutes
    $_ENV['HEAD_ADMIN_ROLE_NAME'] = 'head';

    Config::load();

    // Create test users
    self::createTestUsers();
  }

  public static function tearDownAfterClass(): void
  {
    // Cleanup test data
    self::cleanupTestData();
  }

  private static function createTestUsers(): void
  {
    // Create normal test user
    Database::execute(
      'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
      ['test_user', 'test@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user', 1]
    );
    self::$testUserId = Database::lastInsertId();

    // Create head admin test user
    Database::execute(
      'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
      ['test_head_admin', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'head', 1]
    );
    self::$testHeadAdminId = Database::lastInsertId();
  }

  private static function cleanupTestData(): void
  {
    Database::execute('DELETE FROM failed_logins WHERE ip_address = ?', [self::$testIP]);
    Database::execute('DELETE FROM ip_bans WHERE ip_address = ?', [self::$testIP]);
    Database::execute('DELETE FROM users WHERE id IN (?, ?)', [self::$testUserId, self::$testHeadAdminId]);
  }

  protected function setUp(): void
  {
    // Clean up before each test
    Database::execute('DELETE FROM failed_logins WHERE ip_address = ?', [self::$testIP]);
    Database::execute('DELETE FROM ip_bans WHERE ip_address = ?', [self::$testIP]);
    Database::execute(
      'UPDATE users SET locked_until = NULL, locked_reason = NULL WHERE id IN (?, ?)',
      [self::$testUserId, self::$testHeadAdminId]
    );
  }

  /**
   * Test complete failed login flow for normal user
   */
  public function testNormalUserFailedLoginFlow(): void
  {
    $maxAttempts = Config::get('security.max_failed_attempts');

    // Simulate failed login attempts up to threshold
    for ($i = 1; $i <= $maxAttempts; $i++) {
      FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);

      // Check that user is not locked yet
      $this->assertFalse(FailedLoginTracker::isAccountLocked(self::$testUserId));
      $this->assertFalse(IpBanManager::isIPBanned(self::$testIP));
    }

    // One more attempt should trigger locks
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);

    // Verify account is locked and IP is banned
    $this->assertTrue(FailedLoginTracker::isAccountLocked(self::$testUserId));
    $this->assertTrue(IpBanManager::isIPBanned(self::$testIP));
  }

  /**
   * Test head admin exception - account not locked but IP banned
   */
  public function testHeadAdminFailedLoginFlow(): void
  {
    $maxAttempts = Config::get('security.max_failed_attempts');

    // Simulate failed login attempts exceeding threshold
    for ($i = 1; $i <= $maxAttempts + 2; $i++) {
      FailedLoginTracker::handleFailedLogin(self::$testHeadAdminId, 'admin@example.com', self::$testIP);
    }

    // Verify head admin account is NOT locked but IP is banned
    $this->assertFalse(FailedLoginTracker::isAccountLocked(self::$testHeadAdminId));
    $this->assertTrue(IpBanManager::isIPBanned(self::$testIP));
  }

  /**
   * Test successful login clears failed attempts
   */
  public function testSuccessfulLoginClearsAttempts(): void
  {
    // Record some failed attempts
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);

    // Verify attempts are recorded
    $attempts = FailedLoginTracker::getFailedAttemptsForAccount(self::$testUserId);
    $this->assertEquals(2, $attempts);

    // Simulate successful login
    FailedLoginTracker::clearFailedAttemptsForAccount(self::$testUserId);

    // Verify attempts are cleared
    $attempts = FailedLoginTracker::getFailedAttemptsForAccount(self::$testUserId);
    $this->assertEquals(0, $attempts);
  }

  /**
   * Test IP ban middleware functionality
   */
  public function testIpBanMiddleware(): void
  {
    // Ban the IP
    IpBanManager::banIP(self::$testIP, 'Test ban');

    // Check middleware response
    $result = IpBanManager::checkIPBanMiddleware(self::$testIP);
    $this->assertTrue($result['blocked']);
    $this->assertEquals('IP address is banned', $result['reason']);
  }

  /**
   * Test time window logic - old attempts don't count
   */
  public function testTimeWindowLogic(): void
  {
    // Insert old failed attempt (outside time window)
    $oldTimestamp = date('Y-m-d H:i:s', time() - Config::get('security.time_window_seconds') - 100);
    Database::execute(
      'INSERT INTO failed_logins (account_id, username_entered, ip_address, created_at) VALUES (?, ?, ?, ?)',
      [self::$testUserId, 'test@example.com', self::$testIP, $oldTimestamp]
    );

    // Check that old attempt doesn't count
    $attempts = FailedLoginTracker::getFailedAttemptsForAccount(self::$testUserId);
    $this->assertEquals(0, $attempts);

    // Add recent attempt
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);

    // Check that only recent attempt counts
    $attempts = FailedLoginTracker::getFailedAttemptsForAccount(self::$testUserId);
    $this->assertEquals(1, $attempts);
  }

  /**
   * Test manual unlock functionality
   */
  public function testManualUnlock(): void
  {
    // Lock the account
    FailedLoginTracker::lockAccount(self::$testUserId, 'Test lock');
    $this->assertTrue(FailedLoginTracker::isAccountLocked(self::$testUserId));

    // Unlock the account
    $success = FailedLoginTracker::unlockAccount(self::$testUserId);
    $this->assertTrue($success);
    $this->assertFalse(FailedLoginTracker::isAccountLocked(self::$testUserId));
  }

  /**
   * Test manual IP ban removal
   */
  public function testManualIpBanRemoval(): void
  {
    // Ban the IP
    IpBanManager::banIP(self::$testIP, 'Test ban');
    $this->assertTrue(IpBanManager::isIPBanned(self::$testIP));

    // Remove the ban
    $success = IpBanManager::removeIPBan(self::$testIP);
    $this->assertTrue($success);
    $this->assertFalse(IpBanManager::isIPBanned(self::$testIP));
  }

  /**
   * Test cleanup of expired bans
   */
  public function testExpiredBanCleanup(): void
  {
    // Create an expired ban
    $expiredTime = date('Y-m-d H:i:s', time() - 100);
    Database::execute(
      'INSERT INTO ip_bans (ip_address, reason, expires_at, is_active) VALUES (?, ?, ?, ?)',
      [self::$testIP, 'Test expired ban', $expiredTime, 1]
    );

    // Cleanup expired bans
    $cleaned = IpBanManager::cleanupExpiredBans();
    $this->assertEquals(1, $cleaned);

    // Verify ban is no longer active
    $this->assertFalse(IpBanManager::isIPBanned(self::$testIP));
  }

  /**
   * Test authentication flow with locked account
   */
  public function testAuthenticationWithLockedAccount(): void
  {
    // Lock the account
    FailedLoginTracker::lockAccount(self::$testUserId, 'Test lock');

    // Try to authenticate
    $result = AdminAuth::authenticate('test@example.com', 'password123', self::$testIP);

    // Should fail due to locked account
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('locked', strtolower($result['message']));
  }

  /**
   * Test multiple IPs for same account
   */
  public function testMultipleIPsForSameAccount(): void
  {
    $ip1 = '192.168.1.100';
    $ip2 = '192.168.1.101';
    $maxAttempts = Config::get('security.max_failed_attempts');

    // Failed attempts from different IPs for same account
    for ($i = 1; $i <= $maxAttempts; $i++) {
      FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', $ip1);
    }

    // One more from different IP should still trigger lock
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', $ip2);

    // Account should be locked
    $this->assertTrue(FailedLoginTracker::isAccountLocked(self::$testUserId));

    // Both IPs should be banned
    $this->assertTrue(IpBanManager::isIPBanned($ip1));
    $this->assertTrue(IpBanManager::isIPBanned($ip2));

    // Cleanup
    IpBanManager::removeIPBan($ip1);
    IpBanManager::removeIPBan($ip2);
  }

  /**
   * Test security statistics
   */
  public function testSecurityStatistics(): void
  {
    // Create some test data
    FailedLoginTracker::handleFailedLogin(self::$testUserId, 'test@example.com', self::$testIP);
    IpBanManager::banIP(self::$testIP, 'Test ban');
    FailedLoginTracker::lockAccount(self::$testUserId, 'Test lock');

    // Get stats
    $since24h = date('Y-m-d H:i:s', time() - 86400);

    $failedLogins = Database::fetchOne(
      'SELECT COUNT(*) as count FROM failed_logins WHERE created_at >= ?',
      [$since24h]
    )['count'];

    $activeBans = Database::fetchOne(
      'SELECT COUNT(*) as count FROM ip_bans WHERE is_active = 1'
    )['count'];

    $lockedAccounts = Database::fetchOne(
      'SELECT COUNT(*) as count FROM users WHERE locked_until IS NOT NULL'
    )['count'];

    // Verify stats are reasonable
    $this->assertGreaterThanOrEqual(1, $failedLogins);
    $this->assertGreaterThanOrEqual(1, $activeBans);
    $this->assertGreaterThanOrEqual(1, $lockedAccounts);
  }
}
