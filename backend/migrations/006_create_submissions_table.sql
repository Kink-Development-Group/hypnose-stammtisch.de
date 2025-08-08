-- Migration: Create submissions table for event submissions
-- Version: 006
-- Date: 2025-08-08

-- Create submissions table for event submission forms
CREATE TABLE IF NOT EXISTS submissions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    payload JSON NOT NULL,
    status ENUM('pending', 'processed', 'rejected') DEFAULT 'pending',
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by VARCHAR(255) NULL,
    notes TEXT,

    -- Indexes
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_processed_at (processed_at)
);

-- Insert migration record
INSERT INTO migrations (version, description)
VALUES ('006', 'Create submissions table for event submissions')
ON DUPLICATE KEY UPDATE description = VALUES(description);
