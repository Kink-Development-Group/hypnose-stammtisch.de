-- 1. Migrations tracking
CREATE TABLE IF NOT EXISTS migrations (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  version VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users / Auth
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  pending_email VARCHAR(255) NULL,
  email_change_token VARCHAR(64) NULL,
  email_change_requested_at TIMESTAMP NULL,
  password_hash VARCHAR(255) NOT NULL,
  twofa_secret VARCHAR(64) NULL,
  twofa_enabled BOOLEAN NOT NULL DEFAULT FALSE,
  role ENUM('admin','moderator','head') DEFAULT 'admin',
  is_active BOOLEAN DEFAULT TRUE,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_twofa_backup_codes (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_used_at (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Core domain: Events
CREATE TABLE IF NOT EXISTS event_series (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  rrule TEXT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  exdates TEXT NULL,
  default_duration_minutes INT DEFAULT 120,
  default_location_type ENUM('online','physical','hybrid') DEFAULT 'physical',
  default_location_name VARCHAR(255),
  default_location_address TEXT,
  default_category ENUM('workshop','stammtisch','practice','lecture','special') DEFAULT 'stammtisch',
  default_max_participants INT NULL,
  default_requires_registration BOOLEAN DEFAULT TRUE,
  status ENUM('draft','published','cancelled') DEFAULT 'draft',
  tags JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
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
  location_type ENUM('online','physical','hybrid') DEFAULT 'physical',
  location_name VARCHAR(255),
  location_address TEXT,
  location_url VARCHAR(500),
  location_instructions TEXT,
  category ENUM('workshop','stammtisch','practice','lecture','special') DEFAULT 'stammtisch',
  difficulty_level ENUM('beginner','intermediate','advanced','all') DEFAULT 'all',
  max_participants INT NULL,
  current_participants INT DEFAULT 0,
  age_restriction INT DEFAULT 18,
  requirements TEXT,
  safety_notes TEXT,
  preparation_notes TEXT,
  status ENUM('draft','published','cancelled','completed') DEFAULT 'draft',
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
  series_id VARCHAR(36) NULL,
  instance_date DATE NULL,
  INDEX idx_start_datetime (start_datetime),
  INDEX idx_status (status),
  INDEX idx_category (category),
  INDEX idx_slug (slug),
  INDEX idx_is_recurring (is_recurring),
  INDEX idx_location_type (location_type),
  INDEX idx_series_id (series_id),
  INDEX idx_instance_date (instance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  event_id VARCHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  experience_level ENUM('none','beginner','intermediate','advanced') DEFAULT 'none',
  special_needs TEXT,
  dietary_requirements TEXT,
  emergency_contact VARCHAR(255),
  consent_data_processing BOOLEAN DEFAULT FALSE,
  consent_photo_video BOOLEAN DEFAULT FALSE,
  accepted_code_of_conduct BOOLEAN DEFAULT FALSE,
  registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','confirmed','cancelled','attended') DEFAULT 'pending',
  notes TEXT,
  INDEX idx_event_id (event_id),
  INDEX idx_email (email),
  INDEX idx_status (status),
  UNIQUE KEY unique_event_registration (event_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Contact / Messaging
CREATE TABLE IF NOT EXISTS contact_submissions (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject ENUM('teilnahme','organisation','feedback','partnership','support','conduct','other') NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  referrer VARCHAR(500),
  status ENUM('new','in_progress','resolved','spam') DEFAULT 'new',
  assigned_to VARCHAR(255),
  response_sent BOOLEAN DEFAULT FALSE,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL,
  INDEX idx_subject (subject),
  INDEX idx_status (status),
  INDEX idx_submitted_at (submitted_at),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_notes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  message_id VARCHAR(36) NOT NULL,
  admin_user VARCHAR(50) NOT NULL,
  note TEXT NOT NULL,
  note_type ENUM('processing','communication','general') DEFAULT 'general',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_message_id (message_id),
  INDEX idx_admin_user (admin_user),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_responses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  message_id VARCHAR(36) NOT NULL,
  admin_user VARCHAR(50) NOT NULL,
  from_email VARCHAR(255) NOT NULL,
  to_email VARCHAR(255) NOT NULL,
  subject VARCHAR(500) NOT NULL,
  body TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('draft','sent','failed') DEFAULT 'draft',
  error_message TEXT NULL,
  INDEX idx_message_id (message_id),
  INDEX idx_admin_user (admin_user),
  INDEX idx_status (status),
  INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Infrastructure / Ops
CREATE TABLE IF NOT EXISTS calendar_feed_tokens (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  token VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  allowed_ips JSON,
  access_level ENUM('public','registered','admin') DEFAULT 'public',
  last_accessed TIMESTAMP NULL,
  access_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  INDEX idx_token (token),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ip_address VARCHAR(45) NOT NULL,
  endpoint VARCHAR(255) NOT NULL,
  requests_count INT DEFAULT 1,
  window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
  INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_keys (
  `key` VARCHAR(160) NOT NULL PRIMARY KEY,
  `hits` INT NOT NULL DEFAULT 0,
  `period_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_period_start (period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id INT NULL,
  ip_address VARCHAR(45) NULL,
  action VARCHAR(100) NOT NULL,
  resource_type VARCHAR(60) NULL,
  resource_id VARCHAR(64) NULL,
  meta JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_action (action),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  payload JSON NOT NULL,
  status ENUM('pending','processed','rejected') DEFAULT 'pending',
  ip_address VARCHAR(45),
  user_agent TEXT,
  referrer VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL,
  processed_by VARCHAR(255) NULL,
  notes TEXT,
  INDEX idx_status (status),
  INDEX idx_created_at (created_at),
  INDEX idx_processed_at (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Default data & record baseline migration
INSERT INTO calendar_feed_tokens (token, name, description, access_level)
VALUES (SHA2(CONCAT('default_token_', NOW(), RAND()),256), 'Default Public Feed', 'Default public calendar feed token', 'public')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO migrations (version, description)
VALUES ('001', 'Unified initial full schema baseline')
ON DUPLICATE KEY UPDATE description=VALUES(description);
