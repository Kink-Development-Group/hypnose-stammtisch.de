<?php

declare(strict_types=1);

/**
 * Database Setup Script
 * Creates the database and runs initial setup
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;

// Load configuration
Config::load(__DIR__ . '/../backend');

class DatabaseSetup
{
  private PDO $db;

  public function __construct()
  {
    // Connect without specifying database first
    $config = Config::get('db');

    $dsn = sprintf(
      'mysql:host=%s;port=%d;charset=%s',
      $config['host'],
      $config['port'],
      $config['charset']
    );

    $this->db = new PDO(
      $dsn,
      $config['user'],
      $config['pass'],
      $config['options']
    );
  }

  public function setup(): void
  {
    echo "🔧 Setting up database...\n";

    try {
      $config = Config::get('db');
      $dbName = $config['name'];

      // Create database if it doesn't exist
      echo "   Creating database '{$dbName}' if it doesn't exist...\n";
      $this->db->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

      // Use the database
      $this->db->exec("USE `{$dbName}`");

      echo "✅ Database setup completed!\n";
    } catch (Exception $e) {
      echo "❌ Error during database setup: " . $e->getMessage() . "\n";
      throw $e;
    }
  }
}

// Run the setup
try {
  $setup = new DatabaseSetup();
  $setup->setup();

  echo "\nNow running seeding...\n\n";

  // Now run the seeder
  require_once __DIR__ . '/seed.php';
} catch (Exception $e) {
  echo "❌ Fatal error: " . $e->getMessage() . "\n";
  exit(1);
}
