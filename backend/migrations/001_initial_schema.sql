-- Migration: Create initial database schema for Hypnose Stammtisch
-- Version: 001
-- Date: 2025-08-06

-- Events table
CREATE TABLE
  IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    content LONGTEXT,
    -- DateTime fields
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'Europe/Berlin',
    -- Recurrence (RRULE support)
    is_recurring BOOLEAN DEFAULT FALSE,
    rrule TEXT NULL,
    recurrence_end_date DATE NULL,
    -- Location
    location_type ENUM ('online', 'physical', 'hybrid') DEFAULT 'physical',
    location_name VARCHAR(255),
    location_address TEXT,
    location_url VARCHAR(500),
    location_instructions TEXT,
    -- Event details
    category ENUM (
      'workshop',
      'stammtisch',
      'practice',
      'lecture',
      'special'
    ) DEFAULT 'stammtisch',
    difficulty_level ENUM ('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
    max_participants INT NULL,
    current_participants INT DEFAULT 0,
    -- Requirements and safety
    age_restriction INT DEFAULT 18,
    requirements TEXT,
    safety_notes TEXT,
    preparation_notes TEXT,
    -- Status and visibility
    status ENUM ('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    requires_registration BOOLEAN DEFAULT TRUE,
    registration_deadline DATETIME NULL,
    -- Organizer info
    organizer_name VARCHAR(255),
    organizer_email VARCHAR(255),
    organizer_bio TEXT,
    -- Metadata
    tags JSON,
    meta_description TEXT,
    image_url VARCHAR(500),
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Indexes
    INDEX idx_start_datetime (start_datetime),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_slug (slug),
    INDEX idx_is_recurring (is_recurring),
    INDEX idx_location_type (location_type)
  );

-- Event registrations table
CREATE TABLE
  IF NOT EXISTS event_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    -- Participant info
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    -- Experience and preferences
    experience_level ENUM ('none', 'beginner', 'intermediate', 'advanced') DEFAULT 'none',
    special_needs TEXT,
    dietary_requirements TEXT,
    emergency_contact VARCHAR(255),
    -- Consent and agreements
    consent_data_processing BOOLEAN DEFAULT FALSE,
    consent_photo_video BOOLEAN DEFAULT FALSE,
    accepted_code_of_conduct BOOLEAN DEFAULT FALSE,
    -- Registration details
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM ('pending', 'confirmed', 'cancelled', 'attended') DEFAULT 'pending',
    notes TEXT,
    -- Indexes
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_email (email),
    INDEX idx_status (status),
    UNIQUE KEY unique_event_registration (event_id, email)
  );

-- Contact form submissions table
CREATE TABLE
  IF NOT EXISTS contact_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    -- Contact info
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject ENUM (
      'teilnahme',
      'organisation',
      'feedback',
      'partnership',
      'support',
      'conduct',
      'other'
    ) NOT NULL,
    message TEXT NOT NULL,
    -- Metadata
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    -- Processing status
    status ENUM ('new', 'in_progress', 'resolved', 'spam') DEFAULT 'new',
    assigned_to VARCHAR(255),
    response_sent BOOLEAN DEFAULT FALSE,
    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    -- Indexes
    INDEX idx_subject (subject),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_email (email)
  );

-- Calendar feed tokens table (for secure ICS access)
CREATE TABLE
  IF NOT EXISTS calendar_feed_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    -- Access control
    is_active BOOLEAN DEFAULT TRUE,
    allowed_ips JSON,
    access_level ENUM ('public', 'registered', 'admin') DEFAULT 'public',
    -- Usage tracking
    last_accessed TIMESTAMP NULL,
    access_count INT DEFAULT 0,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    -- Indexes
    INDEX idx_token (token),
    INDEX idx_is_active (is_active)
  );

-- Sessions table (for CSRF protection and rate limiting)
CREATE TABLE
  IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Indexes
    INDEX idx_last_activity (last_activity)
  );

-- Rate limiting table
CREATE TABLE
  IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    requests_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Indexes
    UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
  );

-- Migration tracking table
CREATE TABLE
  IF NOT EXISTS migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_version (version)
  );

-- Insert initial migration record
INSERT INTO
  migrations (version, description)
VALUES
  ('001', 'Create initial database schema') ON DUPLICATE KEY
UPDATE description =
VALUES
  (description);

-- Insert default calendar feed token
INSERT INTO
  calendar_feed_tokens (token, name, description, access_level)
VALUES
  (
    SHA2 (CONCAT ('default_token_', NOW (), RAND ()), 256),
    'Default Public Feed',
    'Default public calendar feed token',
    'public'
  ) ON DUPLICATE KEY
UPDATE name =
VALUES
  (name);

-- Create default admin user (if needed for future admin panel)
-- This is commented out for now, but can be used later
-- CREATE TABLE IF NOT EXISTS admin_users (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     username VARCHAR(50) NOT NULL UNIQUE,
--     email VARCHAR(255) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) NOT NULL,
--     is_active BOOLEAN DEFAULT TRUE,
--     last_login TIMESTAMP NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );
