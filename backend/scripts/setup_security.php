#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Quick setup script for Security: Account Lockout & IP Blocking
 * This script ensures all components are properly configured and working
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Colors for console output
class Colors
{
  const GREEN = "\033[32m";
  const RED = "\033[31m";
  const YELLOW = "\033[33m";
  const BLUE = "\033[34m";
  const RESET = "\033[0m";
}

function success($message)
{
  echo Colors::GREEN . "✓ " . $message . Colors::RESET . "\n";
}

function error($message)
{
  echo Colors::RED . "✗ " . $message . Colors::RESET . "\n";
}

function warning($message)
{
  echo Colors::YELLOW . "⚠ " . $message . Colors::RESET . "\n";
}

function info($message)
{
  echo Colors::BLUE . "ℹ " . $message . Colors::RESET . "\n";
}

function checkRequirement($check, $message)
{
  if ($check) {
    success($message);
    return true;
  } else {
    error($message);
    return false;
  }
}

echo "\n" . Colors::BLUE . "Security Setup Verification" . Colors::RESET . "\n";
echo "==========================\n\n";

$errors = 0;

// Check PHP version
info("Checking PHP version...");
if (!checkRequirement(version_compare(PHP_VERSION, '8.0.0', '>='), "PHP 8.0+ required (current: " . PHP_VERSION . ")")) {
  $errors++;
}

// Load configuration
try {
  Config::load(__DIR__ . '/..');
  success("Configuration loaded");
} catch (Exception $e) {
  error("Failed to load configuration: " . $e->getMessage());
  $errors++;
  exit(1);
}

// Check database connection
info("Checking database connection...");
try {
  $db = Database::getConnection();
  success("Database connection established");
} catch (Exception $e) {
  error("Database connection failed: " . $e->getMessage());
  $errors++;
}

// Check required tables
info("Checking required tables...");
$requiredTables = ['users', 'failed_logins', 'ip_bans'];
foreach ($requiredTables as $table) {
  try {
    $exists = Database::fetchOne("SHOW TABLES LIKE '{$table}'");
    if (!checkRequirement($exists !== false, "Table '{$table}' exists")) {
      $errors++;
    }
  } catch (Exception $e) {
    error("Error checking table '{$table}': " . $e->getMessage());
    $errors++;
  }
}

// Check table structure
info("Checking table structures...");

// Check users table for new columns
try {
  $userColumns = Database::fetchAll("DESCRIBE users");
  $columnNames = array_column($userColumns, 'Field');

  $requiredUserColumns = ['locked_until', 'locked_reason'];
  foreach ($requiredUserColumns as $column) {
    if (!checkRequirement(in_array($column, $columnNames), "Users table has '{$column}' column")) {
      $errors++;
    }
  }
} catch (Exception $e) {
  error("Error checking users table structure: " . $e->getMessage());
  $errors++;
}

// Check failed_logins table structure
try {
  $failedLoginColumns = Database::fetchAll("DESCRIBE failed_logins");
  $columnNames = array_column($failedLoginColumns, 'Field');

  $requiredColumns = ['id', 'account_id', 'username_entered', 'ip_address', 'user_agent', 'created_at'];
  foreach ($requiredColumns as $column) {
    if (!checkRequirement(in_array($column, $columnNames), "failed_logins table has '{$column}' column")) {
      $errors++;
    }
  }
} catch (Exception $e) {
  error("Error checking failed_logins table structure: " . $e->getMessage());
  $errors++;
}

// Check ip_bans table structure
try {
  $ipBanColumns = Database::fetchAll("DESCRIBE ip_bans");
  $columnNames = array_column($ipBanColumns, 'Field');

  $requiredColumns = ['id', 'ip_address', 'reason', 'banned_by', 'created_at', 'expires_at', 'is_active'];
  foreach ($requiredColumns as $column) {
    if (!checkRequirement(in_array($column, $columnNames), "ip_bans table has '{$column}' column")) {
      $errors++;
    }
  }
} catch (Exception $e) {
  error("Error checking ip_bans table structure: " . $e->getMessage());
  $errors++;
}

// Check configuration values
info("Checking security configuration...");
$securityConfig = [
  'security.max_failed_attempts' => Config::get('security.max_failed_attempts'),
  'security.time_window_seconds' => Config::get('security.time_window_seconds'),
  'security.ip_ban_duration_seconds' => Config::get('security.ip_ban_duration_seconds'),
  'security.account_lock_duration_seconds' => Config::get('security.account_lock_duration_seconds'),
  'security.head_admin_role_name' => Config::get('security.head_admin_role_name')
];

foreach ($securityConfig as $key => $value) {
  if (!checkRequirement($value !== null, "Configuration '{$key}' is set (value: {$value})")) {
    $errors++;
  }
}

// Check if required files exist
info("Checking required files...");
$requiredFiles = [
  '../src/Utils/FailedLoginTracker.php',
  '../src/Utils/IpBanManager.php',
  '../src/Controllers/AdminSecurityController.php',
  '../cli/commands/security.php',
  '../scripts/security_maintenance.php'
];

foreach ($requiredFiles as $file) {
  $path = __DIR__ . '/' . $file;
  if (!checkRequirement(file_exists($path), "File exists: {$file}")) {
    $errors++;
  }
}

// Test core functionality
info("Testing core functionality...");

try {
  // Test FailedLoginTracker
  $testIP = '127.0.0.1';
  $testAttempts = \HypnoseStammtisch\Utils\FailedLoginTracker::getFailedAttemptsForIP($testIP);
  success("FailedLoginTracker is functional");
} catch (Exception $e) {
  error("FailedLoginTracker test failed: " . $e->getMessage());
  $errors++;
}

try {
  // Test IpBanManager
  $testIP = '127.0.0.1';
  $isBanned = \HypnoseStammtisch\Utils\IpBanManager::isIPBanned($testIP);
  success("IpBanManager is functional");
} catch (Exception $e) {
  error("IpBanManager test failed: " . $e->getMessage());
  $errors++;
}

// Check indexes for performance
info("Checking database indexes...");
try {
  $indexes = Database::fetchAll("SHOW INDEX FROM failed_logins");
  $indexColumns = array_column($indexes, 'Column_name');

  $recommendedIndexes = ['account_id', 'ip_address', 'created_at'];
  foreach ($recommendedIndexes as $column) {
    if (in_array($column, $indexColumns)) {
      success("Index exists for failed_logins.{$column}");
    } else {
      warning("Consider adding index for failed_logins.{$column}");
    }
  }
} catch (Exception $e) {
  warning("Could not check indexes: " . $e->getMessage());
}

// Performance recommendations
echo "\n" . Colors::BLUE . "Performance Recommendations:" . Colors::RESET . "\n";
echo "==============================\n";

// Check if there are many old failed_logins records
try {
  $oldRecords = Database::fetchOne(
    "SELECT COUNT(*) as count FROM failed_logins WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
  )['count'] ?? 0;

  if ($oldRecords > 1000) {
    warning("Found {$oldRecords} old failed_login records. Consider running cleanup.");
    echo "  Run: php scripts/security_maintenance.php cleanup-logs\n";
  } else {
    success("Failed login records are clean ({$oldRecords} old records)");
  }
} catch (Exception $e) {
  warning("Could not check old records: " . $e->getMessage());
}

// Check for expired bans
try {
  $expiredBans = Database::fetchOne(
    "SELECT COUNT(*) as count FROM ip_bans WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()"
  )['count'] ?? 0;

  if ($expiredBans > 0) {
    warning("Found {$expiredBans} expired IP bans. Consider running cleanup.");
    echo "  Run: php scripts/security_maintenance.php cleanup-bans\n";
  } else {
    success("No expired IP bans found");
  }
} catch (Exception $e) {
  warning("Could not check expired bans: " . $e->getMessage());
}

// Setup recommendations
echo "\n" . Colors::BLUE . "Setup Recommendations:" . Colors::RESET . "\n";
echo "======================\n";

echo "1. Add to crontab for automated maintenance:\n";
echo "   0 3 * * * cd " . dirname(__DIR__) . " && php scripts/security_maintenance.php all\n\n";

echo "2. Monitor security logs regularly:\n";
echo "   php cli/commands/security.php stats\n\n";

echo "3. Test the system with a few failed logins:\n";
echo "   - Try logging in with wrong credentials\n";
echo "   - Check if attempts are being tracked\n";
echo "   - Verify IP banning works\n\n";

echo "4. Configure appropriate values in .env:\n";
echo "   MAX_FAILED_ATTEMPTS=5\n";
echo "   TIME_WINDOW_SECONDS=900\n";
echo "   IP_BAN_DURATION_SECONDS=3600\n";
echo "   ACCOUNT_LOCK_DURATION_SECONDS=3600\n\n";

// Summary
echo "\n" . Colors::BLUE . "Setup Summary:" . Colors::RESET . "\n";
echo "==============\n";

if ($errors === 0) {
  success("All checks passed! Security system is ready to use.");
  echo "\nNext steps:\n";
  echo "- Review configuration in .env\n";
  echo "- Test the system with failed logins\n";
  echo "- Set up automated maintenance cron job\n";
  echo "- Monitor security statistics\n";
} else {
  error("Found {$errors} issue(s) that need to be resolved.");
  echo "\nPlease fix the errors above before using the security system.\n";
}

echo "\nFor detailed documentation, see:\n";
echo "- backend/README_SECURITY.md\n";
echo "- docs/SECURITY_LOCKOUT_IMPLEMENTATION.md\n\n";

exit($errors > 0 ? 1 : 0);
