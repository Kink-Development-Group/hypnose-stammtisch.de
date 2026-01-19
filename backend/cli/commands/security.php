<?php

declare(strict_types=1);

/**
 * Security management CLI commands
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\FailedLoginTracker;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Utils\AuditLogger;

// Load configuration
Config::load(__DIR__ . '/../..');

class SecurityCommand
{
  /**
   * Show security statistics
   */
  public static function stats(): void
  {
    echo "Security Statistics\n";
    echo "==================\n\n";

    // Get stats for different time periods
    $periods = [
      '1 hour' => 3600,
      '24 hours' => 86400,
      '7 days' => 604800
    ];

    foreach ($periods as $label => $seconds) {
      $since = date('Y-m-d H:i:s', time() - $seconds);

      $failedLogins = Database::fetchOne(
        'SELECT COUNT(*) as count FROM failed_logins WHERE created_at >= ?',
        [$since]
      )['count'] ?? 0;

      $uniqueIPs = Database::fetchOne(
        'SELECT COUNT(DISTINCT ip_address) as count FROM failed_logins WHERE created_at >= ?',
        [$since]
      )['count'] ?? 0;

      echo "Last {$label}:\n";
      echo "  Failed logins: {$failedLogins}\n";
      echo "  Unique IPs: {$uniqueIPs}\n\n";
    }

    // Current locks and bans
    $activeBans = Database::fetchOne(
      'SELECT COUNT(*) as count FROM ip_bans WHERE is_active = 1'
    )['count'] ?? 0;

    $lockedAccounts = Database::fetchOne(
      'SELECT COUNT(*) as count FROM users WHERE locked_until IS NOT NULL AND (locked_until > NOW() OR locked_until = "0000-00-00 00:00:00")'
    )['count'] ?? 0;

    echo "Current status:\n";
    echo "  Active IP bans: {$activeBans}\n";
    echo "  Locked accounts: {$lockedAccounts}\n\n";
  }

  /**
   * List active IP bans
   */
  public static function listBans(int $limit = 10): void
  {
    echo "Active IP Bans\n";
    echo "==============\n\n";

    $bans = IpBanManager::getActiveBans($limit);

    if (empty($bans)) {
      echo "No active IP bans found.\n";
      return;
    }

    foreach ($bans as $ban) {
      echo "IP: {$ban['ip_address']}\n";
      echo "Reason: {$ban['reason']}\n";
      echo "Created: {$ban['created_at']}\n";
      echo "Expires: " . ($ban['expires_at'] ?: 'Never') . "\n";
      echo "Banned by: " . ($ban['banned_by_username'] ?: 'System') . "\n";
      echo "---\n";
    }
  }

  /**
   * List locked accounts
   */
  public static function listLocked(): void
  {
    echo "Locked Accounts\n";
    echo "===============\n\n";

    $locked = Database::fetchAll(
      'SELECT id, username, email, role, locked_until, locked_reason, created_at
             FROM users
             WHERE locked_until IS NOT NULL AND (locked_until > NOW() OR locked_until = "0000-00-00 00:00:00")
             ORDER BY locked_until DESC'
    );

    if (empty($locked)) {
      echo "No locked accounts found.\n";
      return;
    }

    foreach ($locked as $account) {
      echo "User: {$account['username']} ({$account['email']})\n";
      echo "Role: {$account['role']}\n";
      echo "Reason: {$account['locked_reason']}\n";
      echo "Locked until: " . ($account['locked_until'] === '0000-00-00 00:00:00' ? 'Manual unlock required' : $account['locked_until']) . "\n";
      echo "---\n";
    }
  }

  /**
   * Unlock an account
   */
  public static function unlockAccount(string $identifier): void
  {
    // Try to find user by ID, username, or email
    $user = null;

    if (is_numeric($identifier)) {
      $user = Database::fetchOne('SELECT id, username, email FROM users WHERE id = ?', [(int)$identifier]);
    } else {
      $user = Database::fetchOne(
        'SELECT id, username, email FROM users WHERE username = ? OR email = ?',
        [$identifier, $identifier]
      );
    }

    if (!$user) {
      echo "Error: User not found: {$identifier}\n";
      return;
    }

    $success = FailedLoginTracker::unlockAccount((int)$user['id']);

    if ($success) {
      echo "Successfully unlocked account: {$user['username']} ({$user['email']})\n";
    } else {
      echo "Failed to unlock account or account was not locked: {$user['username']}\n";
    }
  }

  /**
   * Remove an IP ban
   */
  public static function unbanIP(string $ipAddress): void
  {
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
      echo "Error: Invalid IP address format: {$ipAddress}\n";
      return;
    }

    $success = IpBanManager::removeIPBan($ipAddress);

    if ($success) {
      echo "Successfully removed IP ban: {$ipAddress}\n";
    } else {
      echo "Failed to remove IP ban or IP was not banned: {$ipAddress}\n";
    }
  }

  /**
   * Manually ban an IP
   */
  public static function banIP(string $ipAddress, string $reason = 'Manual ban via CLI'): void
  {
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
      echo "Error: Invalid IP address format: {$ipAddress}\n";
      return;
    }

    IpBanManager::banIP($ipAddress, $reason);
    echo "Successfully banned IP: {$ipAddress} (Reason: {$reason})\n";
  }

  /**
   * Clean up expired bans
   */
  public static function cleanup(): void
  {
    echo "Cleaning up expired IP bans...\n";
    $cleaned = IpBanManager::cleanupExpiredBans();
    echo "Cleaned up {$cleaned} expired bans.\n";
  }

  /**
   * Show recent failed login history
   */
  public static function failedLogins(int $limit = 20): void
  {
    echo "Recent Failed Login Attempts\n";
    echo "============================\n\n";

    $history = FailedLoginTracker::getFailedLoginHistory($limit);

    if (empty($history)) {
      echo "No failed login attempts found.\n";
      return;
    }

    foreach ($history as $attempt) {
      echo "Time: {$attempt['created_at']}\n";
      echo "IP: {$attempt['ip_address']}\n";
      echo "Username attempted: " . ($attempt['username_entered'] ?: 'N/A') . "\n";
      echo "Account: " . ($attempt['username'] ? "{$attempt['username']} ({$attempt['email']})" : 'Unknown') . "\n";
      echo "User Agent: " . ($attempt['user_agent'] ?: 'N/A') . "\n";
      echo "---\n";
    }
  }
}

// Command line argument handling
if ($argc < 2) {
  echo "Security Management CLI\n";
  echo "=====================\n\n";
  echo "Usage: php security.php <command> [options]\n\n";
  echo "Commands:\n";
  echo "  stats                     Show security statistics\n";
  echo "  list-bans [limit]         List active IP bans (default: 10)\n";
  echo "  list-locked               List locked accounts\n";
  echo "  unlock <user>             Unlock account (by ID, username, or email)\n";
  echo "  unban <ip>                Remove IP ban\n";
  echo "  ban <ip> [reason]         Manually ban IP address\n";
  echo "  cleanup                   Clean up expired IP bans\n";
  echo "  failed-logins [limit]     Show recent failed login attempts (default: 20)\n\n";
  exit(1);
}

$command = $argv[1];

try {
  switch ($command) {
    case 'stats':
      SecurityCommand::stats();
      break;

    case 'list-bans':
      $limit = isset($argv[2]) ? (int)$argv[2] : 10;
      SecurityCommand::listBans($limit);
      break;

    case 'list-locked':
      SecurityCommand::listLocked();
      break;

    case 'unlock':
      if (!isset($argv[2])) {
        echo "Error: User identifier required\n";
        exit(1);
      }
      SecurityCommand::unlockAccount($argv[2]);
      break;

    case 'unban':
      if (!isset($argv[2])) {
        echo "Error: IP address required\n";
        exit(1);
      }
      SecurityCommand::unbanIP($argv[2]);
      break;

    case 'ban':
      if (!isset($argv[2])) {
        echo "Error: IP address required\n";
        exit(1);
      }
      $reason = $argv[3] ?? 'Manual ban via CLI';
      SecurityCommand::banIP($argv[2], $reason);
      break;

    case 'cleanup':
      SecurityCommand::cleanup();
      break;

    case 'failed-logins':
      $limit = isset($argv[2]) ? (int)$argv[2] : 20;
      SecurityCommand::failedLogins($limit);
      break;

    default:
      echo "Error: Unknown command: {$command}\n";
      exit(1);
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
