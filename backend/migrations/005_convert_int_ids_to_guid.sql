-- Migration: Convert all remaining INT IDs to UUID (Simple version)
-- Version: 005
-- Date: 2025-08-08

-- This migration converts all remaining INT AUTO_INCREMENT primary keys to UUID (VARCHAR(36))
-- for better security, scalability, and consistency across the database

-- NOTE: This will reset all existing data in these tables to ensure clean UUID migration
-- Backup your data before running this migration in production!

-- Insert migration record first to avoid conflicts
INSERT INTO migrations (version, description)
VALUES ('005', 'Convert all remaining INT IDs to UUID (VARCHAR(36)) for consistency and security')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- 1. Drop and recreate users table with UUID
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator', 'head') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
);

-- 2. Drop and recreate event_series table with UUID
DROP TABLE IF EXISTS event_series;
CREATE TABLE event_series (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    rrule TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    exdates TEXT NULL,
    -- Default event properties for the series
    default_duration_minutes INT DEFAULT 120,
    default_location_type ENUM('online', 'physical', 'hybrid') DEFAULT 'physical',
    default_location_name VARCHAR(255),
    default_location_address TEXT,
    default_category ENUM('workshop', 'stammtisch', 'practice', 'lecture', 'special') DEFAULT 'stammtisch',
    default_max_participants INT NULL,
    default_requires_registration BOOLEAN DEFAULT TRUE,

    -- Status and metadata
    status ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
);

-- Update events table to use VARCHAR(36) for series_id
ALTER TABLE events MODIFY COLUMN series_id VARCHAR(36) NULL;

-- Re-add foreign key constraint
ALTER TABLE events ADD FOREIGN KEY (series_id) REFERENCES event_series(id) ON DELETE CASCADE;

-- 3. Drop and recreate message_notes table with UUID
DROP TABLE IF EXISTS message_notes;
CREATE TABLE message_notes (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    message_id VARCHAR(36) NOT NULL,
    admin_user VARCHAR(50) NOT NULL,
    note TEXT NOT NULL,
    note_type ENUM('processing', 'communication', 'general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key constraints
    FOREIGN KEY (message_id) REFERENCES contact_submissions(id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_message_id (message_id),
    INDEX idx_admin_user (admin_user),
    INDEX idx_created_at (created_at)
);

-- 4. Drop and recreate message_responses table with UUID
DROP TABLE IF EXISTS message_responses;
CREATE TABLE message_responses (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    message_id VARCHAR(36) NOT NULL,
    admin_user VARCHAR(50) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'sent', 'failed') DEFAULT 'draft',
    error_message TEXT NULL,

    -- Foreign key constraints
    FOREIGN KEY (message_id) REFERENCES contact_submissions(id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_message_id (message_id),
    INDEX idx_admin_user (admin_user),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- 5. Drop and recreate admin_email_addresses table with UUID
DROP TABLE IF EXISTS admin_email_addresses;
CREATE TABLE admin_email_addresses (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
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

    -- Indexes
    INDEX idx_email (email),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
);

INSERT INTO admin_email_addresses (email, display_name, department, is_active, is_default) VALUES
('kontakt@hypnose-stammtisch.de', 'Kontakt Team', 'Allgemein', TRUE, TRUE),
('events@hypnose-stammtisch.de', 'Event Team', 'Events', TRUE, FALSE),
('admin@hypnose-stammtisch.de', 'Administration', 'Admin', TRUE, FALSE),
('support@hypnose-stammtisch.de', 'Support Team', 'Support', TRUE, FALSE),
('info@hypnose-stammtisch.de', 'Information', 'Info', TRUE, FALSE);
