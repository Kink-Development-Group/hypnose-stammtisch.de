<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Creating Missing Tables from Migration 001 ===\n\n";

  // Create events table
  echo "1. Creating events table...\n";
  $eventsSQL = "
        CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            content LONGTEXT,
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            timezone VARCHAR(50) DEFAULT 'Europe/Berlin',
            is_recurring BOOLEAN DEFAULT FALSE,
            rrule TEXT NULL,
            recurrence_end_date DATE NULL,
            location_type ENUM ('online', 'physical', 'hybrid') DEFAULT 'physical',
            location_name VARCHAR(255),
            location_address TEXT,
            location_url VARCHAR(500),
            location_instructions TEXT,
            category ENUM ('workshop', 'stammtisch', 'practice', 'lecture', 'special') DEFAULT 'stammtisch',
            difficulty_level ENUM ('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
            max_participants INT NULL,
            current_participants INT DEFAULT 0,
            age_restriction INT DEFAULT 18,
            requirements TEXT,
            safety_notes TEXT,
            preparation_notes TEXT,
            status ENUM ('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
            is_featured BOOLEAN DEFAULT FALSE,
            requires_registration BOOLEAN DEFAULT TRUE,
            registration_deadline DATETIME NULL,
            organizer_name VARCHAR(255),
            organizer_email VARCHAR(255),
            organizer_bio TEXT,
            tags JSON,
            meta_description TEXT,
            image_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_start_datetime (start_datetime),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_slug (slug),
            INDEX idx_is_recurring (is_recurring),
            INDEX idx_location_type (location_type)
        )
    ";

  Database::execute($eventsSQL);
  echo "   ✓ Events table created successfully\n";

  // Create event_registrations table
  echo "\n2. Creating event_registrations table...\n";
  $registrationsSQL = "
        CREATE TABLE IF NOT EXISTS event_registrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            experience_level ENUM ('none', 'beginner', 'intermediate', 'advanced') DEFAULT 'none',
            special_needs TEXT,
            dietary_requirements TEXT,
            emergency_contact VARCHAR(255),
            consent_data_processing BOOLEAN DEFAULT FALSE,
            consent_photo_video BOOLEAN DEFAULT FALSE,
            accepted_code_of_conduct BOOLEAN DEFAULT FALSE,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM ('pending', 'confirmed', 'cancelled', 'attended') DEFAULT 'pending',
            notes TEXT,

            FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
            INDEX idx_event_id (event_id),
            INDEX idx_email (email),
            INDEX idx_status (status),
            UNIQUE KEY unique_event_registration (event_id, email)
        )
    ";

  Database::execute($registrationsSQL);
  echo "   ✓ Event_registrations table created successfully\n";

  // Create contact_submissions table
  echo "\n3. Creating contact_submissions table...\n";
  $contactSQL = "
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject ENUM ('teilnahme', 'organisation', 'feedback', 'partnership', 'support', 'conduct', 'other') NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referrer VARCHAR(500),
            status ENUM ('new', 'in_progress', 'resolved', 'spam') DEFAULT 'new',
            assigned_to VARCHAR(255),
            response_sent BOOLEAN DEFAULT FALSE,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,

            INDEX idx_subject (subject),
            INDEX idx_status (status),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_email (email)
        )
    ";

  Database::execute($contactSQL);
  echo "   ✓ Contact_submissions table created successfully\n";

  // Create calendar_feed_tokens table
  echo "\n4. Creating calendar_feed_tokens table...\n";
  $calendarTokensSQL = "
        CREATE TABLE IF NOT EXISTS calendar_feed_tokens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            allowed_ips JSON,
            access_level ENUM ('public', 'registered', 'admin') DEFAULT 'public',
            last_accessed TIMESTAMP NULL,
            access_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,

            INDEX idx_token (token),
            INDEX idx_is_active (is_active)
        )
    ";

  Database::execute($calendarTokensSQL);
  echo "   ✓ Calendar_feed_tokens table created successfully\n";

  // Create sessions table
  echo "\n5. Creating sessions table...\n";
  $sessionsSQL = "
        CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_last_activity (last_activity)
        )
    ";

  Database::execute($sessionsSQL);
  echo "   ✓ Sessions table created successfully\n";

  // Create rate_limits table
  echo "\n6. Creating rate_limits table...\n";
  $rateLimitsSQL = "
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            requests_count INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
            INDEX idx_window_start (window_start)
        )
    ";

  Database::execute($rateLimitsSQL);
  echo "   ✓ Rate_limits table created successfully\n";

  echo "\n=== Final table check ===\n";
  $tables = Database::fetchAll("SHOW TABLES");
  foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    echo "   - $tableName\n";
  }

  echo "\n=== All missing tables created successfully ===\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
