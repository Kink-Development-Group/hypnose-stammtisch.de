<?php

declare(strict_types=1);

/**
 * Database Seeding Script
 * Creates test data for the Hypnose Stammtisch application
 *
 * Usage: php scripts/seed.php
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__ . '/../backend');

class DatabaseSeeder
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getConnection();
  }

  public function run(): void
  {
    echo "🌱 Starting database seeding...\n";

    // Check if already seeded
    if ($this->isAlreadySeeded()) {
      echo "✅ Database already seeded. Skipping.\n";
      return;
    }

    try {
      $this->db->beginTransaction();

      // Run migrations first
      $this->runMigrations();

      // Create admin user
      $this->createAdminUser();

      // Create sample data
      $this->createEventSeries();
      $this->createSingleEvents();
      $this->createContactSubmissions();

      $this->db->commit();
      echo "✅ Database seeding completed successfully!\n";
    } catch (Exception $e) {
      $this->db->rollBack();
      echo "❌ Error during seeding: " . $e->getMessage() . "\n";
      throw $e;
    }
  }

  private function isAlreadySeeded(): bool
  {
    try {
      $stmt = $this->db->prepare("SELECT COUNT(*) FROM events WHERE slug = ?");
      $stmt->execute(['test-single-event-1']);
      return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
      // Table doesn't exist yet, so not seeded
      return false;
    }
  }

  private function runMigrations(): void
  {
    echo "🔧 Running migrations...\n";

    $migrationFiles = [
      __DIR__ . '/../backend/migrations/001_initial_schema.sql',
      __DIR__ . '/../backend/migrations/002_create_users_table.sql'
    ];

    foreach ($migrationFiles as $file) {
      if (file_exists($file)) {
        echo "   Executing " . basename($file) . "...\n";
        $sql = file_get_contents($file);

        // Split by semicolon and execute each statement separately
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
          if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
              $this->db->exec($statement);
            } catch (PDOException $e) {
              // Ignore "already exists" errors
              if (
                strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate') === false
              ) {
                echo "     Warning: " . $e->getMessage() . "\n";
              }
            }
          }
        }
        echo "   ✓ Executed " . basename($file) . "\n";
      }
    }
  }

  private function createAdminUser(): void
  {
    echo "👤 Creating admin user...\n";

    $email = 'admin@example.com';
    $password = 'password';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT IGNORE INTO users (username, email, password_hash, role, is_active)
                VALUES (?, ?, ?, 'admin', 1)";

    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute(['admin', $email, $passwordHash]);

    if ($result && $stmt->rowCount() > 0) {
      echo "   ✓ Admin user created:\n";
      echo "     Email: {$email}\n";
      echo "     Password: {$password}\n";
    } else {
      echo "   ℹ Admin user already exists\n";
    }
  }

  private function createEventSeries(): void
  {
    echo "📅 Creating recurring event series...\n";

    $series = [
      [
        'title' => 'Wöchentlicher Hypnose-Stammtisch',
        'slug' => 'weekly-stammtisch',
        'description' => 'Unser regelmäßiger wöchentlicher Stammtisch für alle Hypnose-Interessierten.',
        'rrule' => 'FREQ=WEEKLY;BYDAY=TU;INTERVAL=1',
        'start_date' => '2025-08-05', // First Tuesday
        'end_date' => '2025-12-31',
        'exdates' => json_encode(['2025-08-26']), // Exception: skip one week
        'default_duration_minutes' => 120,
        'default_location_type' => 'physical',
        'default_location_name' => 'Café Zentral',
        'default_location_address' => 'Hauptstraße 123, 12345 Musterstadt',
        'default_category' => 'stammtisch',
        'default_max_participants' => 15,
        'default_requires_registration' => true,
        'status' => 'published',
        'tags' => json_encode(['stammtisch', 'weekly', 'community'])
      ],
      [
        'title' => 'Monatlicher Hypnose-Workshop',
        'slug' => 'monthly-workshop',
        'description' => 'Vertiefende Workshops zu verschiedenen Hypnose-Techniken.',
        'rrule' => 'FREQ=MONTHLY;BYDAY=2SA;INTERVAL=1', // Second Saturday of each month
        'start_date' => '2025-08-09', // Second Saturday in August
        'end_date' => '2025-12-31',
        'exdates' => json_encode([]),
        'default_duration_minutes' => 240,
        'default_location_type' => 'hybrid',
        'default_location_name' => 'Hypnose-Zentrum',
        'default_location_address' => 'Therapiestraße 456, 12345 Musterstadt',
        'default_category' => 'workshop',
        'default_max_participants' => 20,
        'default_requires_registration' => true,
        'status' => 'published',
        'tags' => json_encode(['workshop', 'monthly', 'advanced'])
      ]
    ];

    foreach ($series as $seriesData) {
      $sql = "INSERT IGNORE INTO event_series (
                title, slug, description, rrule, start_date, end_date, exdates,
                default_duration_minutes, default_location_type, default_location_name,
                default_location_address, default_category, default_max_participants,
                default_requires_registration, status, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
        $seriesData['title'],
        $seriesData['slug'],
        $seriesData['description'],
        $seriesData['rrule'],
        $seriesData['start_date'],
        $seriesData['end_date'],
        $seriesData['exdates'],
        $seriesData['default_duration_minutes'],
        $seriesData['default_location_type'],
        $seriesData['default_location_name'],
        $seriesData['default_location_address'],
        $seriesData['default_category'],
        $seriesData['default_max_participants'],
        $seriesData['default_requires_registration'],
        $seriesData['status'],
        $seriesData['tags']
      ]);

      echo "   ✓ Created series: {$seriesData['title']}\n";
    }
  }

  private function createSingleEvents(): void
  {
    echo "🎯 Creating single events...\n";

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
        'is_featured' => true,
        'requires_registration' => true,
        'registration_deadline' => '2025-08-14 18:00:00',
        'organizer_name' => 'Dr. Maria Hypnose',
        'organizer_email' => 'maria@hypnose-example.com',
        'tags' => json_encode(['workshop', 'beginner', 'introduction'])
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
        'tags' => json_encode(['practice', 'beginner', 'self-hypnosis'])
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
        'tags' => json_encode(['workshop', 'advanced', 'techniques'])
      ],
      [
        'title' => 'Hypnose in der Schmerztherapie',
        'slug' => 'test-single-event-4',
        'description' => 'Medizinische Anwendungen der Hypnose.',
        'content' => 'Wissenschaftliche Grundlagen und praktische Anwendung...',
        'start_datetime' => '2025-06-25 10:00:00',
        'end_datetime' => '2025-06-25 17:00:00',
        'timezone' => 'Europe/Berlin',
        'location_type' => 'hybrid',
        'location_name' => 'Universitätsklinik',
        'location_address' => 'Medizinstraße 555, 12345 Musterstadt',
        'location_url' => 'https://teams.microsoft.com/meet/abc123',
        'category' => 'lecture',
        'difficulty_level' => 'intermediate',
        'max_participants' => 30,
        'status' => 'completed',
        'requires_registration' => true,
        'organizer_name' => 'Dr. med. Heiler',
        'organizer_email' => 'heiler@klinik-example.com',
        'tags' => json_encode(['lecture', 'medical', 'pain-therapy'])
      ],
      [
        'title' => 'Entspannungsabend mit Hypnose',
        'slug' => 'test-single-event-5',
        'description' => 'Ein entspannter Abend für alle.',
        'content' => 'Gemeinsame Entspannungsreise...',
        'start_datetime' => '2025-08-25 19:30:00',
        'end_datetime' => '2025-08-25 21:00:00',
        'timezone' => 'Europe/Berlin',
        'location_type' => 'physical',
        'location_name' => 'Wellness-Center Ruhe',
        'location_address' => 'Entspannungsallee 111, 12345 Musterstadt',
        'category' => 'special',
        'difficulty_level' => 'all',
        'max_participants' => 20,
        'status' => 'published',
        'is_featured' => false,
        'requires_registration' => false,
        'organizer_name' => 'Susanne Ruhe',
        'organizer_email' => 'susanne@wellness-example.com',
        'tags' => json_encode(['special', 'relaxation', 'evening'])
      ]
    ];

    foreach ($events as $eventData) {
      // Convert to UTC for database storage
      $startUTC = $this->convertToUTC($eventData['start_datetime'], $eventData['timezone']);
      $endUTC = $this->convertToUTC($eventData['end_datetime'], $eventData['timezone']);
      $registrationDeadlineUTC = isset($eventData['registration_deadline'])
        ? $this->convertToUTC($eventData['registration_deadline'], $eventData['timezone'])
        : null;

      $sql = "INSERT IGNORE INTO events (
                title, slug, description, content, start_datetime, end_datetime, timezone,
                is_recurring, location_type, location_name, location_address, location_url,
                category, difficulty_level, max_participants, current_participants,
                status, is_featured, requires_registration, registration_deadline,
                organizer_name, organizer_email, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
        $eventData['title'],
        $eventData['slug'],
        $eventData['description'],
        $eventData['content'],
        $startUTC,
        $endUTC,
        $eventData['timezone'],
        $eventData['location_type'],
        $eventData['location_name'] ?? null,
        $eventData['location_address'] ?? null,
        $eventData['location_url'] ?? null,
        $eventData['category'],
        $eventData['difficulty_level'],
        $eventData['max_participants'],
        $eventData['status'],
        $eventData['is_featured'] ?? false,
        $eventData['requires_registration'],
        $registrationDeadlineUTC,
        $eventData['organizer_name'],
        $eventData['organizer_email'],
        $eventData['tags']
      ]);

      echo "   ✓ Created event: {$eventData['title']}\n";
    }
  }

  private function createContactSubmissions(): void
  {
    echo "📧 Creating sample contact submissions...\n";

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
        'response_sent' => true,
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
      $sql = "INSERT IGNORE INTO contact_submissions (
                name, email, subject, message, status, response_sent,
                submitted_at, processed_at, assigned_to
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
        $submission['name'],
        $submission['email'],
        $submission['subject'],
        $submission['message'],
        $submission['status'],
        $submission['response_sent'] ?? false,
        $submission['submitted_at'],
        $submission['processed_at'] ?? null,
        $submission['assigned_to'] ?? null
      ]);

      echo "   ✓ Created submission from: {$submission['name']}\n";
    }
  }

  private function convertToUTC(string $datetime, string $timezone): string
  {
    $dt = new DateTime($datetime, new DateTimeZone($timezone));
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
  }
}

// Run the seeder
try {
  $seeder = new DatabaseSeeder();
  $seeder->run();
} catch (Exception $e) {
  echo "❌ Fatal error: " . $e->getMessage() . "\n";
  exit(1);
}
