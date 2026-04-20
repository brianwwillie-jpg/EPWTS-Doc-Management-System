-- ============================================
-- EPWTS Database Schema - Backup & Restore Tables
-- ============================================
-- Tables created by db_migration_backup.php

-- ============= Table 1: deleted_documents =============
-- Purpose: Stores soft-deleted documents for recovery
-- History: Keeps all deletion metadata for audit trail

CREATE TABLE IF NOT EXISTS `deleted_documents` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `doc_id` VARCHAR(20) NOT NULL,                   -- Original document ID (e.g., AB260504001)
    `file_name` VARCHAR(255) NOT NULL,               -- Original filename
    `file_path` VARCHAR(255),                         -- Path to file
    `section` VARCHAR(50),                            -- Department: AB, CW, AD, EP, DO
    `doc_type` VARCHAR(50),                           -- Document type: Contracts, Drawings, etc.
    `uploaded_by` VARCHAR(50),                        -- Who originally uploaded it
    `upload_date` DATETIME,                           -- When it was originally uploaded
    `expiry_date` DATE,                               -- Expiration date if applicable
    `description` TEXT,                               -- Document description/notes
    `deleted_by` VARCHAR(50),                         -- Username who deleted it
    `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When it was deleted
    
    -- Indexes for fast queries
    INDEX idx_doc_id (doc_id),
    INDEX idx_section (section),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_deleted_by (deleted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============= Table 2: backup_logs =============
-- Purpose: Tracks all backup operations
-- Use: Audit trail and maintenance history

CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `backup_type` ENUM('database', 'files', 'document_deletion') NOT NULL,
    -- Identifies type of backup operation:
    -- - 'database' = Full database SQL dump
    -- - 'files' = Backup of all uploaded documents
    -- - 'document_deletion' = Log of deleted documents
    
    `created_by` VARCHAR(50) NOT NULL,                -- Username who created the backup
    `file_path` VARCHAR(255),                         -- Where the backup is stored
    `file_size` VARCHAR(50),                          -- Size in KB or file count
    `file_count` INT DEFAULT 0,                       -- Number of files (for file backups)
    `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
    -- Status of backup:
    -- - 'success' = Backup completed successfully
    -- - 'failed' = Backup encountered errors
    -- - 'partial' = Some files/tables backed up, some failed
    
    `notes` TEXT,                                     -- Additional backup details
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When backup was created
    
    -- Indexes for fast queries
    INDEX idx_backup_type (backup_type),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============= Table 3: restore_logs =============
-- Purpose: Tracks all restore operations
-- Use: Audit trail for system recoveries

CREATE TABLE IF NOT EXISTS `restore_logs` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `restore_type` ENUM('database', 'files', 'document') NOT NULL,
    -- Type of restore operation:
    -- - 'database' = Restored entire database from backup
    -- - 'files' = Restored file backup
    -- - 'document' = Restored single deleted document
    
    `restored_from` VARCHAR(255),                     -- Backup file name or document ID restored from
    `restored_by` VARCHAR(50) NOT NULL,               -- Username who performed the restore
    `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
    -- Status of restore:
    -- - 'success' = Restore completed successfully
    -- - 'failed' = Restore encountered errors
    -- - 'partial' = Some data restored, some failed
    
    `notes` TEXT,                                     -- Additional restore details
    `restored_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When restore was performed
    
    -- Indexes for fast queries
    INDEX idx_restore_type (restore_type),
    INDEX idx_restored_by (restored_by),
    INDEX idx_restored_at (restored_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================
-- USAGE EXAMPLES
-- ============================================

-- Example 1: View all deleted documents
SELECT * FROM deleted_documents ORDER BY deleted_at DESC;

-- Example 2: Find all documents deleted by a specific user
SELECT * FROM deleted_documents WHERE deleted_by = 'username' ORDER BY deleted_at DESC;

-- Example 3: View backup history
SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 20;

-- Example 4: View database backups only
SELECT * FROM backup_logs WHERE backup_type = 'database' ORDER BY created_at DESC;

-- Example 5: View restore history
SELECT * FROM restore_logs ORDER BY restored_at DESC LIMIT 20;

-- Example 6: Get documents deleted in the last 7 days
SELECT * FROM deleted_documents WHERE deleted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Example 7: List failed backup attempts
SELECT * FROM backup_logs WHERE status = 'failed' ORDER BY created_at DESC;

-- Example 8: Get full backup timeline
SELECT 
    created_at as 'Operation Time',
    'BACKUP' as 'Type',
    created_by as 'User',
    CONCAT(backup_type, ' - ', status) as 'Details'
FROM backup_logs
UNION ALL
SELECT 
    restored_at as 'Operation Time',
    'RESTORE' as 'Type',
    restored_by as 'User',
    CONCAT(restore_type, ' - ', status) as 'Details'
FROM restore_logs
ORDER BY 1 DESC;


-- ============================================
-- MAINTENANCE QUERIES
-- ============================================

-- Delete deleted documents older than 30 days (after backup)
DELETE FROM deleted_documents WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Clear old backup logs (older than 90 days)
DELETE FROM backup_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- View system statistics
SELECT 
    (SELECT COUNT(*) FROM deleted_documents) as 'Deleted Documents',
    (SELECT COUNT(*) FROM backup_logs WHERE status = 'success') as 'Successful Backups',
    (SELECT COUNT(*) FROM restore_logs WHERE status = 'success') as 'Successful Restores',
    (SELECT COUNT(DISTINCT created_by) FROM backup_logs) as 'Users Creating Backups';

-- Check backup frequency
SELECT 
    DATE(created_at) as 'Date',
    COUNT(*) as 'Backup Count',
    GROUP_CONCAT(DISTINCT backup_type) as 'Types'
FROM backup_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY 1 DESC;


-- ============================================
-- RELATIONSHIPS & FOREIGN KEYS (Optional)
-- ============================================
-- Note: These can be added later if needed

-- Link deleted_documents to users table
-- ALTER TABLE deleted_documents ADD CONSTRAINT fk_deleted_by 
--   FOREIGN KEY (deleted_by) REFERENCES users(username);

-- Link backup_logs to users table
-- ALTER TABLE backup_logs ADD CONSTRAINT fk_backup_created_by 
--   FOREIGN KEY (created_by) REFERENCES users(username);

-- Link restore_logs to users table
-- ALTER TABLE restore_logs ADD CONSTRAINT fk_restore_restored_by 
--   FOREIGN KEY (restored_by) REFERENCES users(username);


-- ============================================
-- DATABASE INTEGRITY CHECKS
-- ============================================

-- Verify tables exist
SHOW TABLES LIKE '%deleted%';
SHOW TABLES LIKE '%backup%';
SHOW TABLES LIKE '%restore%';

-- Check table structure
DESCRIBE deleted_documents;
DESCRIBE backup_logs;
DESCRIBE restore_logs;

-- Check indexes
SHOW INDEX FROM deleted_documents;
SHOW INDEX FROM backup_logs;
SHOW INDEX FROM restore_logs;

-- Check disk usage
SELECT 
    table_name,
    ROUND((data_free + data_length) / 1024 / 1024, 2) as 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'epwts_db' 
AND table_name IN ('deleted_documents', 'backup_logs', 'restore_logs');
