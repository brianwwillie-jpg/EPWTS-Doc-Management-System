<?php
/**
 * EPWTS Automated Backup Script
 * 
 * This script can be run via cron job for automated daily backups.
 * 
 * Usage (Linux/Unix Cron):
 * 0 2 * * * php /var/www/html/EPWTS-Document_Management_System/modules/super-admin-features/auto_backup.php
 * 
 * Above runs daily at 2:00 AM
 */

include "../../config/db.php";

// Configuration
define('BACKUP_DIR', "../../backups/");
define('BACKUP_FILES_DIR', "../../backups/files/");
define('DAYS_TO_KEEP', 30);
define('MAX_BACKUPS', 30);
define('LOG_FILE', "../../backups/backup_log.txt");

class AutoBackup {
    private $conn;
    private $backup_dir;
    private $backup_files_dir;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->backup_dir = BACKUP_DIR;
        $this->backup_files_dir = BACKUP_FILES_DIR;
        
        // Create directories if they don't exist
        if (!is_dir($this->backup_dir)) mkdir($this->backup_dir, 0755, true);
        if (!is_dir($this->backup_files_dir)) mkdir($this->backup_files_dir, 0755, true);
    }
    
    /**
     * Create database backup
     */
    public function backupDatabase() {
        $this->log("Starting database backup...");
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_file = $this->backup_dir . "epwts_db_{$timestamp}.sql";
        
        try {
            $tables = [];
            $result = $this->conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sql_dump = "-- EPWTS Database Backup (Automated)\n";
            $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql_dump .= "-- Backed up by: Automated Script\n";
            $sql_dump .= "-- Database: epwts_db\n";
            $sql_dump .= "-- ============================================\n\n";
            
            $total_rows = 0;
            foreach ($tables as $table) {
                // Get CREATE TABLE statement
                $create_result = $this->conn->query("SHOW CREATE TABLE `{$table}`");
                $create_row = $create_result->fetch_row();
                $sql_dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql_dump .= $create_row[1] . ";\n\n";
                
                // Get table data
                $data_result = $this->conn->query("SELECT * FROM `{$table}`");
                $row_count = $data_result->num_rows;
                $total_rows += $row_count;
                
                while ($data_row = $data_result->fetch_assoc()) {
                    $columns = implode("`, `", array_keys($data_row));
                    $values = implode("', '", array_map(function($v) {
                        return $this->conn->real_escape_string($v ?? '');
                    }, array_values($data_row)));
                    $sql_dump .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ('{$values}');\n";
                }
                $sql_dump .= "\n";
            }
            
            // Save backup file
            if (file_put_contents($backup_file, $sql_dump)) {
                $file_size = filesize($backup_file);
                $this->log("✅ Database backup successful! File: " . basename($backup_file) . " (" . round($file_size / 1024, 2) . " KB, {$total_rows} rows)");
                
                // Log to database
                $log_sql = "INSERT INTO backup_logs (backup_type, created_by, file_path, file_size, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($log_sql);
                $type = "database";
                $user = "AutoBackup";
                $status = "success";
                $size = round($file_size / 1024, 2) . " KB";
                $stmt->bind_param("sssss", $type, $user, $backup_file, $size, $status);
                $stmt->execute();
                
                return true;
            } else {
                $this->log("❌ Failed to write database backup file!");
                return false;
            }
        } catch (Exception $e) {
            $this->log("❌ Database backup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create file backup
     */
    public function backupFiles() {
        $this->log("Starting file backup...");
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_folder = $this->backup_files_dir . "backup_{$timestamp}/";
        
        try {
            mkdir($backup_folder, 0755, true);
            
            // Copy all uploads
            $source = "../../uploads/";
            $files_count = 0;
            
            if (is_dir($source)) {
                $files = scandir($source);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && !is_dir($source . $file)) {
                        if (copy($source . $file, $backup_folder . $file)) {
                            $files_count++;
                        } else {
                            $this->log("⚠️  Warning: Could not copy file: {$file}");
                        }
                    }
                }
            }
            
            if ($files_count > 0) {
                $this->log("✅ File backup successful! Backed up {$files_count} files to: " . basename($backup_folder));
                
                // Log to database
                $log_sql = "INSERT INTO backup_logs (backup_type, created_by, file_path, file_count, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($log_sql);
                $type = "files";
                $user = "AutoBackup";
                $status = "success";
                $stmt->bind_param("sssss", $type, $user, $backup_folder, $files_count, $status);
                $stmt->execute();
                
                return true;
            } else {
                $this->log("⚠️  File backup: No files to backup!");
                return false;
            }
        } catch (Exception $e) {
            $this->log("❌ File backup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up old backups
     */
    public function cleanupOldBackups() {
        $this->log("Cleaning up old backups...");
        
        $deleted_count = 0;
        
        // Clean database backups
        if (is_dir($this->backup_dir)) {
            $files = glob($this->backup_dir . "epwts_db_*.sql");
            
            // Sort by date (oldest first)
            usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
            
            // Keep only MAX_BACKUPS
            if (count($files) > MAX_BACKUPS) {
                $to_delete = array_slice($files, 0, count($files) - MAX_BACKUPS);
                foreach ($to_delete as $file) {
                    if (unlink($file)) {
                        $this->log("  Deleted: " . basename($file));
                        $deleted_count++;
                    }
                }
            }
            
            // Delete backups older than DAYS_TO_KEEP
            foreach ($files as $file) {
                if (time() - filemtime($file) > (DAYS_TO_KEEP * 86400)) {
                    if (unlink($file)) {
                        $this->log("  Deleted (expired): " . basename($file));
                        $deleted_count++;
                    }
                }
            }
        }
        
        // Clean file backups (folders)
        if (is_dir($this->backup_files_dir)) {
            $folders = array_filter(glob($this->backup_files_dir . "*"), 'is_dir');
            
            foreach ($folders as $folder) {
                if (time() - filemtime($folder) > (DAYS_TO_KEEP * 86400)) {
                    // Use recursive delete
                    $this->deleteDirectory($folder);
                    $this->log("  Deleted (expired): " . basename($folder));
                    $deleted_count++;
                }
            }
        }
        
        if ($deleted_count > 0) {
            $this->log("✅ Cleanup completed! Deleted {$deleted_count} old backup(s)");
        } else {
            $this->log("✅ Cleanup completed. No old backups to delete");
        }
    }
    
    /**
     * Recursively delete directory
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['..', '.']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Log message to file and echo
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        
        // Write to file
        file_put_contents(LOG_FILE, $log_message, FILE_APPEND);
        
        // Echo to console/cron output
        echo $log_message;
    }
    
    /**
     * Run all backup tasks
     */
    public function runAll() {
        $this->log("========================================");
        $this->log("EPWTS AUTOMATED BACKUP - Started");
        $this->log("========================================");
        
        $db_success = $this->backupDatabase();
        $file_success = $this->backupFiles();
        $this->cleanupOldBackups();
        
        $this->log("========================================");
        $this->log("EPWTS AUTOMATED BACKUP - Completed");
        $this->log("Database: " . ($db_success ? "✅ SUCCESS" : "❌ FAILED"));
        $this->log("Files: " . ($file_success ? "✅ SUCCESS" : "❌ FAILED"));
        $this->log("========================================");
        $this->log("");
    }
}

// Run backup
$backup = new AutoBackup($conn);
$backup->runAll();
?>
