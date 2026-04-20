<?php
/**
 * EPWTS Database Migration - Backup & Restore Tables
 * 
 * This script creates the necessary tables for backup and restore functionality.
 * Run this ONCE after deploying the backup feature.
 * 
 * Tables Created:
 * 1. deleted_documents - Tracks deleted documents for recovery
 * 2. backup_logs - Logs all backup operations
 * 3. restore_logs - Logs all restore operations
 */

include "../../config/db.php";

// Check if we're creating tables
$create_tables = true;

if ($create_tables) {
    // ============= CREATE deleted_documents TABLE =============
    $deleted_docs_sql = "CREATE TABLE IF NOT EXISTS `deleted_documents` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `doc_id` VARCHAR(20) NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255),
        `section` VARCHAR(50),
        `doc_type` VARCHAR(50),
        `uploaded_by` VARCHAR(50),
        `upload_date` DATETIME,
        `expiry_date` DATE,
        `description` TEXT,
        `deleted_by` VARCHAR(50),
        `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_doc_id (doc_id),
        INDEX idx_section (section)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    // ============= CREATE backup_logs TABLE =============
    $backup_logs_sql = "CREATE TABLE IF NOT EXISTS `backup_logs` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `backup_type` ENUM('database', 'files') NOT NULL COMMENT 'Type of backup',
        `created_by` VARCHAR(50) NOT NULL,
        `file_path` VARCHAR(255),
        `file_size` VARCHAR(50),
        `file_count` INT DEFAULT 0,
        `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_backup_type (backup_type),
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    // ============= CREATE restore_logs TABLE =============
    $restore_logs_sql = "CREATE TABLE IF NOT EXISTS `restore_logs` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `restore_type` ENUM('database', 'files', 'document') NOT NULL COMMENT 'Type of restore',
        `restored_from` VARCHAR(255),
        `restored_by` VARCHAR(50) NOT NULL,
        `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
        `notes` TEXT,
        `restored_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_restore_type (restore_type),
        INDEX idx_restored_by (restored_by),
        INDEX idx_restored_at (restored_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    // Execute table creation
    $tables = [
        'deleted_documents' => $deleted_docs_sql,
        'backup_logs' => $backup_logs_sql,
        'restore_logs' => $restore_logs_sql
    ];

    $created = [];
    $errors = [];

    foreach ($tables as $table_name => $sql) {
        if ($conn->query($sql)) {
            $created[] = $table_name;
        } else {
            $errors[] = "Error creating $table_name: " . $conn->error;
        }
    }

    // Display results
    echo "<!DOCTYPE html>";
    echo "<html>";
    echo "<head>";
    echo "<title>Database Migration - Backup & Restore</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head>";
    echo "<body class='p-5'>";
    echo "<div class='container'>";
    echo "<h2>Database Migration Results</h2>";
    
    if (count($created) > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h5>✅ Successfully Created Tables:</h5>";
        foreach ($created as $table) {
            echo "<li>$table</li>";
        }
        echo "</div>";
    }

    if (count($errors) > 0) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>❌ Errors:</h5>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</div>";
    }

    echo "<hr>";
    echo "<p><strong>Migration Status:</strong> " . (count($errors) === 0 ? "✅ SUCCESS - All tables created!" : "⚠️ PARTIAL - Check errors above") . "</p>";
    echo "<p><a href='../super_admin.php' class='btn btn-primary'>Back to Dashboard</a></p>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
}
?>
