<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

// Load configuration
Config::load(__DIR__);

try {
  echo "Creating missing tables...\n";

  // Create message_notes table
  $messageNotesSQL = "
    CREATE TABLE IF NOT EXISTS message_notes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id INT NOT NULL,
        admin_user VARCHAR(50) NOT NULL,
        note TEXT NOT NULL,
        note_type ENUM('processing', 'communication', 'general') DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (message_id) REFERENCES contact_submissions(id) ON DELETE CASCADE,

        INDEX idx_message_id (message_id),
        INDEX idx_admin_user (admin_user),
        INDEX idx_created_at (created_at)
    )";

  Database::execute($messageNotesSQL);
  echo "✓ Created message_notes table\n";

  // Create message_responses table
  $messageResponsesSQL = "
    CREATE TABLE IF NOT EXISTS message_responses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id INT NOT NULL,
        admin_user VARCHAR(50) NOT NULL,
        from_email VARCHAR(255) NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        body TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('draft', 'sent', 'failed') DEFAULT 'draft',
        error_message TEXT NULL,

        FOREIGN KEY (message_id) REFERENCES contact_submissions(id) ON DELETE CASCADE,

        INDEX idx_message_id (message_id),
        INDEX idx_admin_user (admin_user),
        INDEX idx_status (status),
        INDEX idx_sent_at (sent_at)
    )";

  Database::execute($messageResponsesSQL);
  echo "✓ Created message_responses table\n";

  // Create admin_email_addresses table
  $adminEmailSQL = "
    CREATE TABLE IF NOT EXISTS admin_email_addresses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL UNIQUE,
        display_name VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        is_default BOOLEAN DEFAULT FALSE,
        smtp_host VARCHAR(255) NULL,
        smtp_port INT DEFAULT 587,
        smtp_username VARCHAR(255) NULL,
        smtp_password VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_email (email),
        INDEX idx_department (department),
        INDEX idx_is_active (is_active),
        INDEX idx_is_default (is_default)
    )";

  Database::execute($adminEmailSQL);
  echo "✓ Created admin_email_addresses table\n";

  // Insert default email addresses
  $insertEmailsSQL = "
    INSERT IGNORE INTO admin_email_addresses (email, display_name, department, is_active, is_default) VALUES
    ('kontakt@hypnose-stammtisch.de', 'Kontakt Team', 'Allgemein', TRUE, TRUE),
    ('events@hypnose-stammtisch.de', 'Event Team', 'Events', TRUE, FALSE),
    ('admin@hypnose-stammtisch.de', 'Administration', 'Admin', TRUE, FALSE),
    ('support@hypnose-stammtisch.de', 'Support Team', 'Support', TRUE, FALSE),
    ('info@hypnose-stammtisch.de', 'Information', 'Info', TRUE, FALSE)
    ";

  Database::execute($insertEmailsSQL);
  echo "✓ Inserted default email addresses\n";

  echo "\nAll tables created successfully!\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
