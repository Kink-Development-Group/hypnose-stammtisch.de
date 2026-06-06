-- Migration 009: Add stammtisch locations table
-- This migration adds the table for managing stammtisch locations on the map

CREATE TABLE IF NOT EXISTS stammtisch_locations (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  region VARCHAR(100) NOT NULL,
  country VARCHAR(3) NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  description TEXT,
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  contact_fetlife VARCHAR(100),
  contact_website VARCHAR(500),
  meeting_frequency VARCHAR(255),
  meeting_location VARCHAR(255),
  meeting_address TEXT,
  next_meeting DATETIME NULL,
  tags JSON,
  is_active BOOLEAN DEFAULT TRUE,
  status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_country (country),
  INDEX idx_region (region),
  INDEX idx_city (city),
  INDEX idx_status (status),
  INDEX idx_is_active (is_active),
  INDEX idx_slug (slug),
  INDEX idx_coordinates (latitude, longitude),

  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

