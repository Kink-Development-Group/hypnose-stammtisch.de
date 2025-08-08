-- Migration: Add admin notes and email response functionality
-- Version: 003
-- Date: 2025-08-07

-- Admin notes for messages
CREATE TABLE IF NOT EXISTS message_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
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

-- Email responses sent through the admin interface
CREATE TABLE IF NOT EXISTS message_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
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

-- Available email addresses for admin responses
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

    -- Indexes
    INDEX idx_email (email),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
);

-- Insert default email addresses
INSERT INTO admin_email_addresses (email, display_name, department, is_active, is_default) VALUES
('kontakt@hypnose-stammtisch.de', 'Kontakt Team', 'Allgemein', TRUE, TRUE),
('events@hypnose-stammtisch.de', 'Event Team', 'Events', TRUE, FALSE),
('admin@hypnose-stammtisch.de', 'Administration', 'Admin', TRUE, FALSE),
('support@hypnose-stammtisch.de', 'Support Team', 'Support', TRUE, FALSE),
('info@hypnose-stammtisch.de', 'Information', 'Info', TRUE, FALSE)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    department = VALUES(department),
    is_active = VALUES(is_active);
