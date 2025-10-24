<?php

declare(strict_types=1);

/**
 * Test Helper for Secure Credential Management
 *
 * This helper provides utilities for managing test credentials securely
 * without hardcoded passwords in the codebase.
 */

namespace HypnoseStammtisch\Tests\Utils;

class SecureTestCredentials
{
  private static array $credentials = [];

  /**
   * Generate or retrieve secure test credentials
   *
   * @param string $type The type of credential ('user' or 'admin')
   * @return string Secure password
   */
  public static function getPassword(string $type): string
  {
    if (!isset(self::$credentials[$type])) {
      // Try environment variable first
      $envVar = strtoupper("TEST_{$type}_PASSWORD");
      self::$credentials[$type] = $_ENV[$envVar] ?? self::generateSecurePassword();
    }

    return self::$credentials[$type];
  }

  /**
   * Generate a cryptographically secure random password
   *
   * @param int $length Password length (default: 32)
   * @return string Secure random password
   */
  private static function generateSecurePassword(int $length = 32): string
  {
    return bin2hex(random_bytes($length / 2));
  }

  /**
   * Generate test credentials for specific requirements
   *
   * @param array $requirements Password requirements
   * @return string Generated password meeting requirements
   */
  public static function generatePasswordWithRequirements(array $requirements = []): string
  {
    $length = $requirements['length'] ?? 16;
    $includeSpecial = $requirements['special_chars'] ?? true;
    $includeNumbers = $requirements['numbers'] ?? true;
    $includeUppercase = $requirements['uppercase'] ?? true;

    $chars = 'abcdefghijklmnopqrstuvwxyz';

    if ($includeUppercase) {
      $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    if ($includeNumbers) {
      $chars .= '0123456789';
    }

    if ($includeSpecial) {
      $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
    }

    $password = '';
    $charsLength = strlen($chars);

    for ($i = 0; $i < $length; $i++) {
      $password .= $chars[random_int(0, $charsLength - 1)];
    }

    return $password;
  }

  /**
   * Clear all stored credentials (useful for test cleanup)
   */
  public static function clearCredentials(): void
  {
    self::$credentials = [];
  }

  /**
   * Validate if a password meets security requirements
   *
   * @param string $password Password to validate
   * @param array $requirements Requirements to check
   * @return bool True if password meets requirements
   */
  public static function validatePassword(string $password, array $requirements = []): bool
  {
    $minLength = $requirements['min_length'] ?? 12;
    $requireNumbers = $requirements['require_numbers'] ?? true;
    $requireSpecial = $requirements['require_special'] ?? true;
    $requireUppercase = $requirements['require_uppercase'] ?? true;
    $requireLowercase = $requirements['require_lowercase'] ?? true;

    if (strlen($password) < $minLength) {
      return false;
    }

    if ($requireNumbers && !preg_match('/\d/', $password)) {
      return false;
    }

    if ($requireSpecial && !preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password)) {
      return false;
    }

    if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
      return false;
    }

    if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
      return false;
    }

    return true;
  }

  /**
   * Get test email for a given user type
   *
   * @param string $type User type ('user', 'admin', 'head_admin')
   * @return string Test email address
   */
  public static function getTestEmail(string $type): string
  {
    $emails = [
      'user' => 'test@example.com',
      'admin' => 'admin@example.com',
      'head_admin' => 'head.admin@example.com'
    ];

    return $emails[$type] ?? "test.{$type}@example.com";
  }

  /**
   * Get test username for a given user type
   *
   * @param string $type User type
   * @return string Test username
   */
  public static function getTestUsername(string $type): string
  {
    return "test_{$type}";
  }
}
