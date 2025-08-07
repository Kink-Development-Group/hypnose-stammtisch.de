-- Migration: Create users table for admin authentication
-- Version: 002
-- Date: 2025-08-07

-- Admin users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
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

-- Event series table for recurring events
CREATE TABLE IF NOT EXISTS event_series (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    rrule TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    exdates TEXT NULL, -- JSON array of exception dates
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

-- Add series_id to events table to link individual events to their series
ALTER TABLE events
ADD COLUMN series_id INT NULL AFTER id,
ADD COLUMN instance_date DATE NULL AFTER series_id,
ADD FOREIGN KEY (series_id) REFERENCES event_series(id) ON DELETE CASCADE,
ADD INDEX idx_series_id (series_id),
ADD INDEX idx_instance_date (instance_date);

-- Insert migration record
INSERT INTO migrations (version, description)
VALUES ('002', 'Create users table and event series support')
ON DUPLICATE KEY UPDATE description = VALUES(description);
