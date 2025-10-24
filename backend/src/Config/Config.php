<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Config;

use Dotenv\Dotenv;

/**
 * Configuration manager for the application
 */
class Config
{
  private static array $config = [];
  private static bool $loaded = false;

  /**
   * Load configuration from environment file
   */
  public static function load(string $path = null): void
  {
    if (self::$loaded) {
      return;
    }

    $path = $path ?: dirname(__DIR__);

    if (file_exists($path . '/.env')) {
      $dotenv = Dotenv::createImmutable($path);
      $dotenv->load();
    }

    self::$config = [
      // Database
      'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'name' => $_ENV['DB_NAME'] ?? 'hypnose_stammtisch',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
          \PDO::ATTR_EMULATE_PREPARES => false,
        ]
      ],

      // Application
      'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'https://hypnose-stammtisch.de',
        'frontend_url' => $_ENV['FRONTEND_URL'] ?? 'https://hypnose-stammtisch.de',
        'timezone' => $_ENV['CALENDAR_TIMEZONE'] ?? 'Europe/Berlin',
      ],

      // Security
      'security' => [
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
        'csrf_token_name' => $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token',
        'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 3600),
        
        // Failed login protection
        'max_failed_attempts' => (int)($_ENV['MAX_FAILED_ATTEMPTS'] ?? 5),
        'time_window_seconds' => (int)($_ENV['TIME_WINDOW_SECONDS'] ?? 900), // 15 minutes
        'ip_ban_duration_seconds' => (int)($_ENV['IP_BAN_DURATION_SECONDS'] ?? 3600), // 1 hour, 0 = permanent
        'account_lock_duration_seconds' => (int)($_ENV['ACCOUNT_LOCK_DURATION_SECONDS'] ?? 3600), // 1 hour, 0 = manual unlock
        'head_admin_role_name' => $_ENV['HEAD_ADMIN_ROLE_NAME'] ?? 'head',
      ],

      // Email
      'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'info@hypnose-stammtisch.de',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Hypnose Stammtisch',
      ],

      // Contact Form
      'contact' => [
        'general' => $_ENV['CONTACT_GENERAL'] ?? 'info@hypnose-stammtisch.de',
        'events' => $_ENV['CONTACT_EVENTS'] ?? 'events@hypnose-stammtisch.de',
        'conduct' => $_ENV['CONTACT_CONDUCT'] ?? 'conduct@hypnose-stammtisch.de',
        'support' => $_ENV['CONTACT_SUPPORT'] ?? 'support@hypnose-stammtisch.de',
      ],

      // Calendar
      'calendar' => [
        'feed_token' => $_ENV['CALENDAR_FEED_TOKEN'] ?? '',
        'timezone' => $_ENV['CALENDAR_TIMEZONE'] ?? 'Europe/Berlin',
      ],

      // File Upload
      'upload' => [
        'max_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760),
        'path' => $_ENV['UPLOAD_PATH'] ?? '../uploads',
        'allowed_extensions' => explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif,pdf'),
      ],

      // Rate Limiting
      'rate_limit' => [
        'requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
        'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600),
      ],

      // CORS
      'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173'),
        'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS'),
        'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With'),
      ],
    ];

    self::$loaded = true;
  }

  /**
   * Get configuration value by key
   */
  public static function get(string $key, mixed $default = null): mixed
  {
    if (!self::$loaded) {
      self::load();
    }

    $keys = explode('.', $key);
    $value = self::$config;

    foreach ($keys as $k) {
      if (!isset($value[$k])) {
        return $default;
      }
      $value = $value[$k];
    }

    return $value;
  }

  /**
   * Check if configuration key exists
   */
  public static function has(string $key): bool
  {
    return self::get($key) !== null;
  }

  /**
   * Get all configuration
   */
  public static function all(): array
  {
    if (!self::$loaded) {
      self::load();
    }

    return self::$config;
  }
}
