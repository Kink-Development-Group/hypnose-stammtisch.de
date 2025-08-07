<?php

declare(strict_types=1);

/**
 * Simple Database Setup and Seeding Script
 * Creates tables directly and adds test data
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__ . '/../backend');

try {
  $db = Database::getConnection();

  echo "🔧 Creating database tables...\n";

  // Create users table
  $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'moderator') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_is_active (is_active)
        )
    ");
  echo "   ✓ Created users table\n";

  // Create events table
  $db->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            series_id INT NULL,
            instance_date DATE NULL,
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
            location_type ENUM('online', 'physical', 'hybrid') DEFAULT 'physical',
            location_name VARCHAR(255),
            location_address TEXT,
            location_url VARCHAR(500),
            location_instructions TEXT,
            category ENUM('workshop', 'stammtisch', 'practice', 'lecture', 'special') DEFAULT 'stammtisch',
            difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
            max_participants INT NULL,
            current_participants INT DEFAULT 0,
            age_restriction INT DEFAULT 18,
            requirements TEXT,
            safety_notes TEXT,
            preparation_notes TEXT,
            status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
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
            INDEX idx_location_type (location_type),
            INDEX idx_series_id (series_id),
            INDEX idx_instance_date (instance_date)
        )
    ");
  echo "   ✓ Created events table\n";

  // Create event_series table
  $db->exec("
        CREATE TABLE IF NOT EXISTS event_series (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            rrule TEXT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            exdates TEXT NULL,
            default_duration_minutes INT DEFAULT 120,
            default_location_type ENUM('online', 'physical', 'hybrid') DEFAULT 'physical',
            default_location_name VARCHAR(255),
            default_location_address TEXT,
            default_category ENUM('workshop', 'stammtisch', 'practice', 'lecture', 'special') DEFAULT 'stammtisch',
            default_max_participants INT NULL,
            default_requires_registration BOOLEAN DEFAULT TRUE,
            status ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
            tags JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_start_date (start_date)
        )
    ");
  echo "   ✓ Created event_series table\n";

  // Create contact_submissions table
  $db->exec("
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject ENUM('teilnahme', 'organisation', 'feedback', 'partnership', 'support', 'conduct', 'other') NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referrer VARCHAR(500),
            status ENUM('new', 'in_progress', 'resolved', 'spam') DEFAULT 'new',
            assigned_to VARCHAR(255),
            response_sent BOOLEAN DEFAULT FALSE,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            INDEX idx_subject (subject),
            INDEX idx_status (status),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_email (email)
        )
    ");
  echo "   ✓ Created contact_submissions table\n";

  // Check if already seeded
  $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
  $stmt->execute();
  if ($stmt->fetchColumn() > 0) {
    echo "✅ Database already seeded. Skipping data creation.\n";
    exit(0);
  }

  echo "🌱 Seeding database with test data...\n";

  // Create admin user
  $passwordHash = password_hash('password', PASSWORD_DEFAULT);
  $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
  $stmt->execute(['admin', 'admin@example.com', $passwordHash]);
  echo "   ✓ Created admin user (admin@example.com / password)\n";

  // Create sample events
  $events = [
    [
      'title' => 'Einführung in die Hypnose',
      'slug' => 'test-single-event-1',
      'description' => 'Ein Einführungsworkshop für Hypnose-Neulinge.',
      'content' => 'In diesem Workshop lernen Sie die Grundlagen der Hypnose kennen...',
      'start_datetime' => '2025-08-15 19:00:00',
      'end_datetime' => '2025-08-15 21:00:00',
      'timezone' => 'Europe/Berlin',
      'location_type' => 'physical',
      'location_name' => 'Seminarraum Alpha',
      'location_address' => 'Lernstraße 789, 12345 Musterstadt',
      'category' => 'workshop',
      'difficulty_level' => 'beginner',
      'max_participants' => 12,
      'status' => 'published',
      'is_featured' => 1,
      'requires_registration' => true,
      'registration_deadline' => '2025-08-14 18:00:00',
      'organizer_name' => 'Dr. Maria Hypnose',
      'organizer_email' => 'maria@hypnose-example.com',
      'tags' => '["workshop", "beginner", "introduction"]'
    ],
    [
      'title' => 'Selbsthypnose für Anfänger',
      'slug' => 'test-single-event-2',
      'description' => 'Lernen Sie, sich selbst zu hypnotisieren.',
      'content' => 'Praktische Übungen zur Selbsthypnose...',
      'start_datetime' => '2025-07-20 14:00:00',
      'end_datetime' => '2025-07-20 16:00:00',
      'timezone' => 'Europe/Berlin',
      'location_type' => 'online',
      'location_url' => 'https://zoom.us/j/123456789',
      'category' => 'practice',
      'difficulty_level' => 'beginner',
      'max_participants' => 25,
      'status' => 'completed',
      'requires_registration' => true,
      'organizer_name' => 'Thomas Entspannt',
      'organizer_email' => 'thomas@hypnose-example.com',
      'tags' => '["practice", "beginner", "self-hypnosis"]'
    ],
    [
      'title' => 'Fortgeschrittene Hypnose-Techniken',
      'slug' => 'test-single-event-3',
      'description' => 'Workshop für erfahrene Hypnotiseure.',
      'content' => 'Vertiefende Techniken und moderne Ansätze...',
      'start_datetime' => '2025-09-10 18:30:00',
      'end_datetime' => '2025-09-10 21:30:00',
      'timezone' => 'Europe/Berlin',
      'location_type' => 'physical',
      'location_name' => 'Praxis für Hypnosetherapie',
      'location_address' => 'Therapieweg 321, 12345 Musterstadt',
      'category' => 'workshop',
      'difficulty_level' => 'advanced',
      'max_participants' => 8,
      'status' => 'published',
      'requires_registration' => true,
      'registration_deadline' => '2025-09-08 23:59:00',
      'organizer_name' => 'Prof. Dr. Hypno Expert',
      'organizer_email' => 'expert@hypnose-example.com',
      'tags' => '["workshop", "advanced", "techniques"]'
    ]
  ];

  foreach ($events as $event) {
    $sql = "INSERT INTO events (
            title, slug, description, content, start_datetime, end_datetime, timezone,
            location_type, location_name, location_address, location_url,
            category, difficulty_level, max_participants, status, is_featured,
            requires_registration, registration_deadline, organizer_name, organizer_email, tags
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
      $event['title'],
      $event['slug'],
      $event['description'],
      $event['content'],
      $event['start_datetime'],
      $event['end_datetime'],
      $event['timezone'],
      $event['location_type'],
      $event['location_name'] ?? null,
      $event['location_address'] ?? null,
      $event['location_url'] ?? null,
      $event['category'],
      $event['difficulty_level'],
      $event['max_participants'],
      $event['status'],
      $event['is_featured'] ?? 0,
      $event['requires_registration'],
      $event['registration_deadline'] ?? null,
      $event['organizer_name'],
      $event['organizer_email'],
      $event['tags']
    ]);
  }
  echo "   ✓ Created " . count($events) . " sample events\n";

  // Create sample contact submissions
  $submissions = [
    [
      'name' => 'Anna Neugierig',
      'email' => 'anna@example.com',
      'subject' => 'teilnahme',
      'message' => 'Hallo! Ich interessiere mich für Hypnose und würde gerne an einem Ihrer Termine teilnehmen. Können Sie mir mehr Informationen zukommen lassen?',
      'status' => 'new',
      'submitted_at' => '2025-08-05 14:30:00'
    ],
    [
      'name' => 'Marcus Zweifel',
      'email' => 'marcus@example.com',
      'subject' => 'feedback',
      'message' => 'Ich war beim letzten Workshop dabei und fand ihn sehr inspirierend. Wann findet der nächste statt?',
      'status' => 'resolved',
      'response_sent' => 1,
      'submitted_at' => '2025-08-03 09:15:00',
      'processed_at' => '2025-08-04 11:20:00'
    ],
    [
      'name' => 'Lisa Therapeutin',
      'email' => 'lisa@therapie-example.com',
      'subject' => 'partnership',
      'message' => 'Guten Tag, ich bin Psychotherapeutin und würde gerne über eine mögliche Zusammenarbeit sprechen. Hypnose könnte eine wertvolle Ergänzung für meine Praxis sein.',
      'status' => 'in_progress',
      'assigned_to' => 'admin',
      'submitted_at' => '2025-08-01 16:45:00'
    ]
  ];

  foreach ($submissions as $submission) {
    $sql = "INSERT INTO contact_submissions (
            name, email, subject, message, status, response_sent,
            submitted_at, processed_at, assigned_to
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
      $submission['name'],
      $submission['email'],
      $submission['subject'],
      $submission['message'],
      $submission['status'],
      $submission['response_sent'] ?? 0,
      $submission['submitted_at'],
      $submission['processed_at'] ?? null,
      $submission['assigned_to'] ?? null
    ]);
  }
  echo "   ✓ Created " . count($submissions) . " sample contact submissions\n";

  // Create sample event series
  $series = [
    [
      'title' => 'Wöchentlicher Hypnose-Stammtisch',
      'slug' => 'weekly-stammtisch',
      'description' => 'Unser regelmäßiger wöchentlicher Stammtisch für alle Hypnose-Interessierten.',
      'rrule' => 'FREQ=WEEKLY;BYDAY=TU;INTERVAL=1',
      'start_date' => '2025-08-05',
      'end_date' => '2025-12-31',
      'exdates' => '["2025-08-26"]',
      'default_duration_minutes' => 120,
      'default_location_type' => 'physical',
      'default_location_name' => 'Café Zentral',
      'default_location_address' => 'Hauptstraße 123, 12345 Musterstadt',
      'default_category' => 'stammtisch',
      'default_max_participants' => 15,
      'default_requires_registration' => 1,
      'status' => 'published',
      'tags' => '["stammtisch", "weekly", "community"]'
    ]
  ];

  foreach ($series as $seriesItem) {
    $sql = "INSERT INTO event_series (
            title, slug, description, rrule, start_date, end_date, exdates,
            default_duration_minutes, default_location_type, default_location_name,
            default_location_address, default_category, default_max_participants,
            default_requires_registration, status, tags
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
      $seriesItem['title'],
      $seriesItem['slug'],
      $seriesItem['description'],
      $seriesItem['rrule'],
      $seriesItem['start_date'],
      $seriesItem['end_date'],
      $seriesItem['exdates'],
      $seriesItem['default_duration_minutes'],
      $seriesItem['default_location_type'],
      $seriesItem['default_location_name'],
      $seriesItem['default_location_address'],
      $seriesItem['default_category'],
      $seriesItem['default_max_participants'],
      $seriesItem['default_requires_registration'],
      $seriesItem['status'],
      $seriesItem['tags']
    ]);
  }
  echo "   ✓ Created " . count($series) . " sample event series\n";

  echo "\n✅ Database setup and seeding completed successfully!\n";
  echo "\n📧 Admin Login Credentials:\n";
  echo "   Email: admin@example.com\n";
  echo "   Password: password\n\n";
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  exit(1);
}
