<?php
session_start();
include "../../config/db.php";
include "../../includes/functions.php";

// Security Check: Only Super Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super_Admin') {
    die("Access Denied. Super Admin privileges required.");
    exit();
}

$current_user = $_SESSION['username'] ?? 'Unknown';
$backup_dir = "../../backups/";
$backup_files_dir = "../../backups/files/";

// Create backup directories if they don't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}
if (!is_dir($backup_files_dir)) {
    mkdir($backup_files_dir, 0755, true);
}

// ============= HANDLE ACTIONS =============

// Create Database Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_db') {
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . "epwts_db_{$timestamp}.sql";
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Generate SQL dump
    $sql_dump = "-- EPWTS Database Backup\n";
    $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= "-- Backed up by: {$current_user}\n";
    $sql_dump .= "-- Database: epwts_db\n";
    $sql_dump .= "-- ============================================\n\n";
    
    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $create_result = $conn->query("SHOW CREATE TABLE `{$table}`");
        $create_row = $create_result->fetch_row();
        $sql_dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql_dump .= $create_row[1] . ";\n\n";
        
        // Get table data
        $data_result = $conn->query("SELECT * FROM `{$table}`");
        while ($data_row = $data_result->fetch_assoc()) {
            $columns = implode("`, `", array_keys($data_row));
            $values = implode(", ", array_map(function($v) use ($conn) {
                if ($v === null) {
                    return 'NULL';
                }
                return "'" . $conn->real_escape_string($v) . "'";
            }, array_values($data_row)));
            $sql_dump .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$values});\n";
        }
        $sql_dump .= "\n";
    }
    
    // Save backup file
    if (file_put_contents($backup_file, $sql_dump)) {
        $success_message = "Database backed up successfully! File: " . basename($backup_file);
        
        // Log backup action
        $log_sql = "INSERT INTO backup_logs (backup_type, created_by, file_path, file_size, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($log_sql);
        $file_size = filesize($backup_file);
        $status = "success";
        $stmt->bind_param("sssss", $type, $current_user, $backup_file, $file_size, $status);
        $type = "database";
        $stmt->execute();
    } else {
        $error_message = "Failed to create database backup!";
    }
}

// Create File Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_files') {
    $timestamp = date('Y-m-d_H-i-s');
    $backup_folder = $backup_files_dir . "backup_{$timestamp}/";
    mkdir($backup_folder, 0755, true);
    
    // Copy all uploads
    $source = "../../uploads/";
    $files_count = 0;
    
    if (is_dir($source)) {
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && !is_dir($source . $file)) {
                copy($source . $file, $backup_folder . $file);
                $files_count++;
            }
        }
    }
    
    if ($files_count > 0) {
        $success_message = "File backup created successfully! Backed up {$files_count} files.";
        
        // Log backup action
        $log_sql = "INSERT INTO backup_logs (backup_type, created_by, file_path, file_count, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($log_sql);
        $status = "success";
        $stmt->bind_param("sssss", $type, $current_user, $backup_folder, $files_count, $status);
        $type = "files";
        $stmt->execute();
    } else {
        $error_message = "No files to backup or failed to create file backup!";
    }
}

// Restore Database from Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore_db') {
    $backup_file = isset($_POST['backup_file']) ? htmlspecialchars($_POST['backup_file']) : '';
    $full_path = $backup_dir . basename($backup_file);
    
    if (file_exists($full_path)) {
        $sql_content = file_get_contents($full_path);
        
        // Split by semicolon and execute queries
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));
        $success_count = 0;
        $error_count = 0;
        
        foreach ($queries as $query) {
            if (!empty($query) && !strpos($query, '--')) {
                if ($conn->query($query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $success_message = "Database restored successfully! Executed {$success_count} queries.";
            
            // Log restore action
            $log_sql = "INSERT INTO restore_logs (restore_type, restored_from, restored_by, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($log_sql);
            $status = "success";
            $stmt->bind_param("ssss", $type, $filename, $current_user, $status);
            $type = "database";
            $filename = basename($backup_file);
            $stmt->execute();
        } else {
            $error_message = "Failed to restore database. No valid queries executed.";
        }
    } else {
        $error_message = "Backup file not found!";
    }
}

// Restore Deleted Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore_deleted') {
    $deleted_doc_id = intval($_POST['deleted_doc_id']);
    
    $sql = "SELECT * FROM deleted_documents WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleted_doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $deleted_doc = $result->fetch_assoc();
    
    if ($deleted_doc) {
        // Restore to documents table
        $restore_sql = "INSERT INTO documents (doc_id, file_name, file_path, section, doc_type, uploaded_by, upload_date, expiry_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $restore_stmt = $conn->prepare($restore_sql);
        $restore_stmt->bind_param("sssssssss", 
            $deleted_doc['doc_id'],
            $deleted_doc['file_name'],
            $deleted_doc['file_path'],
            $deleted_doc['section'],
            $deleted_doc['doc_type'],
            $deleted_doc['uploaded_by'],
            $deleted_doc['upload_date'],
            $deleted_doc['expiry_date'],
            $deleted_doc['description']
        );
        
        if ($restore_stmt->execute()) {
            // Remove from deleted_documents
            $delete_sql = "DELETE FROM deleted_documents WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $deleted_doc_id);
            $delete_stmt->execute();
            
            $success_message = "Document '{$deleted_doc['file_name']}' restored successfully!";
            
            // Log restore
            $log_sql = "INSERT INTO restore_logs (restore_type, restored_from, restored_by, status) VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $type = "document";
            $status = "success";
            $log_stmt->bind_param("ssss", $type, $deleted_doc['doc_id'], $current_user, $status);
            $log_stmt->execute();
        } else {
            $error_message = "Failed to restore document!";
        }
    } else {
        $error_message = "Deleted document not found!";
    }
}

// Permanently Delete Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
    $backup_file = isset($_POST['backup_file']) ? htmlspecialchars($_POST['backup_file']) : '';
    $full_path = $backup_dir . basename($backup_file);
    
    if (file_exists($full_path) && unlink($full_path)) {
        $success_message = "Backup file deleted successfully!";
    } else {
        $error_message = "Failed to delete backup file!";
    }
}

// Get all database backups
$db_backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . "epwts_db_*.sql");
    foreach ($files as $file) {
        $db_backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file),
            'path' => $file
        ];
    }
    usort($db_backups, function($a, $b) { return $b['date'] - $a['date']; });
}

// Get all file backups
$file_backups = [];
if (is_dir($backup_files_dir)) {
    $folders = array_filter(glob($backup_files_dir . "*"), 'is_dir');
    foreach ($folders as $folder) {
        $file_count = count(array_diff(scandir($folder), ['..', '.']));
        $file_backups[] = [
            'name' => basename($folder),
            'date' => filemtime($folder),
            'file_count' => $file_count,
            'path' => $folder
        ];
    }
    usort($file_backups, function($a, $b) { return $b['date'] - $a['date']; });
}

// Get deleted documents
$deleted_sql = "SELECT * FROM deleted_documents ORDER BY deleted_at DESC";
$deleted_result = $conn->query($deleted_sql);

// Get backup logs
$logs_sql = "SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 20";
$logs_result = $conn->query($logs_sql);

// Get restore logs
$restore_logs_sql = "SELECT * FROM restore_logs ORDER BY restored_at DESC LIMIT 20";
$restore_logs_result = $conn->query($restore_logs_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPWTS DMS - Backup & Restore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .action-card.danger {
            border-left-color: var(--danger);
        }
        
        .action-card h5 {
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .action-card p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .backup-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .backup-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .backup-item:hover {
            background-color: #f8f9fa;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-info h6 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .backup-info small {
            color: #666;
        }
        
        .badge-file {
            background-color: #e7f3ff;
            color: #0056b3;
        }
        
        .badge-db {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .badge-size {
            background-color: #e8f5e9;
            color: #00695c;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .btn-action {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }
        
        .deleted-item {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .stat-box .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-box .label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            border-bottom: 2px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            background: none;
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-content {
            padding-top: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="header-section">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-cloud-check"></i> Backup & Restore Manager</h2>
                    <small class="opacity-75">Manage system backups and restore deleted items</small>
                </div>
                <a href="../super_admin.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-mini">
            <div class="stat-box">
                <div class="number"><?php echo count($db_backups); ?></div>
                <div class="label">DB Backups</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo count($file_backups); ?></div>
                <div class="label">File Backups</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $deleted_result ? $deleted_result->num_rows : 0; ?></div>
                <div class="label">Deleted Items</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $logs_result ? $logs_result->num_rows : 0; ?></div>
                <div class="label">Recent Actions</div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                    <i class="bi bi-cloud-arrow-down"></i> Create Backup
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore" type="button">
                    <i class="bi bi-cloud-arrow-up"></i> Restore Backup
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="deleted-tab" data-bs-toggle="tab" data-bs-target="#deleted" type="button">
                    <i class="bi bi-trash"></i> Recovery (<?php echo $deleted_result ? $deleted_result->num_rows : 0; ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
                    <i class="bi bi-clock-history"></i> History
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- CREATE BACKUP TAB -->
            <div class="tab-pane fade show active" id="backup" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="action-card">
                            <h5><i class="bi bi-database"></i> Database Backup</h5>
                            <p>Create a complete SQL dump of the entire database including all tables, users, documents, and permissions.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="backup_db">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-cloud-arrow-down"></i> Create Database Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="action-card">
                            <h5><i class="bi bi-folder"></i> File Backup</h5>
                            <p>Create a backup copy of all uploaded documents. This creates a timestamped folder in the backups directory.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="backup_files">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-cloud-arrow-down"></i> Create File Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Database Backups List -->
                <div class="backup-list mt-4">
                    <h5 class="mb-3"><i class="bi bi-database"></i> Recent Database Backups</h5>
                    <?php if (count($db_backups) > 0): ?>
                        <?php foreach ($db_backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <h6><?php echo htmlspecialchars($backup['name']); ?></h6>
                                    <small>Created: <?php echo date('M d, Y H:i:s', $backup['date']); ?> | Size: <?php echo round($backup['size'] / 1024, 2); ?> KB</small>
                                </div>
                                <div>
                                    <span class="badge badge-db">Database</span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this backup?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No database backups found. Create one using the button above.</p>
                    <?php endif; ?>
                </div>

                <!-- File Backups List -->
                <div class="backup-list">
                    <h5 class="mb-3"><i class="bi bi-folder"></i> Recent File Backups</h5>
                    <?php if (count($file_backups) > 0): ?>
                        <?php foreach ($file_backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <h6><?php echo htmlspecialchars($backup['name']); ?></h6>
                                    <small>Created: <?php echo date('M d, Y H:i:s', $backup['date']); ?> | Files: <?php echo $backup['file_count']; ?></small>
                                </div>
                                <div>
                                    <span class="badge badge-file"><?php echo $backup['file_count']; ?> Files</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No file backups found. Create one using the button above.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RESTORE BACKUP TAB -->
            <div class="tab-pane fade" id="restore" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="action-card danger">
                            <h5><i class="bi bi-exclamation-triangle"></i> Restore Database from Backup</h5>
                            <p class="text-danger"><strong>⚠️ WARNING:</strong> This will <strong>REPLACE</strong> the entire current database with data from the selected backup. This action cannot be undone.</p>
                            
                            <?php if (count($db_backups) > 0): ?>
                                <form method="POST" onsubmit="return confirm('This will replace your current database. Are you absolutely sure?');">
                                    <input type="hidden" name="action" value="restore_db">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <select name="backup_file" class="form-select" required>
                                                <option value="">-- Select a backup to restore --</option>
                                                <?php foreach ($db_backups as $backup): ?>
                                                    <option value="<?php echo htmlspecialchars($backup['name']); ?>">
                                                        <?php echo htmlspecialchars($backup['name']); ?> (<?php echo date('M d, Y H:i', $backup['date']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="bi bi-cloud-arrow-up"></i> Restore Database
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">No backups available for restoration. Create a backup first.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECOVERY TAB -->
            <div class="tab-pane fade" id="deleted" role="tabpanel">
                <h5 class="mt-4 mb-3"><i class="bi bi-trash"></i> Deleted Documents Ready for Recovery</h5>
                
                <?php 
                // Reset the pointer
                if ($deleted_result) {
                    $deleted_result->data_seek(0);
                }
                if ($deleted_result && $deleted_result->num_rows > 0): 
                ?>
                    <?php while ($deleted = $deleted_result->fetch_assoc()): ?>
                        <div class="deleted-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6><?php echo htmlspecialchars($deleted['file_name']); ?></h6>
                                    <small class="text-muted">
                                        ID: <strong><?php echo htmlspecialchars($deleted['doc_id']); ?></strong> | 
                                        Deleted: <?php echo date('M d, Y H:i:s', strtotime($deleted['deleted_at'])); ?> | 
                                        Section: <?php echo htmlspecialchars($deleted['section']); ?>
                                    </small>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="restore_deleted">
                                    <input type="hidden" name="deleted_doc_id" value="<?php echo $deleted['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="bi bi-arrow-clockwise"></i> Restore
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No deleted documents available for recovery.
                    </div>
                <?php endif; ?>
            </div>

            <!-- HISTORY TAB -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="bi bi-cloud-arrow-down"></i> Backup History</h5>
                        <div class="backup-list">
                            <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                <?php while ($log = $logs_result->fetch_assoc()): ?>
                                    <div style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <small>
                                            <strong><?php echo ucfirst($log['backup_type']); ?></strong> - 
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?><br>
                                            <span class="text-muted">By: <?php echo htmlspecialchars($log['created_by']); ?> | Status: <span class="badge bg-success"><?php echo $log['status']; ?></span></span>
                                        </small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">No backup history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="bi bi-cloud-arrow-up"></i> Restore History</h5>
                        <div class="backup-list">
                            <?php if ($restore_logs_result && $restore_logs_result->num_rows > 0): ?>
                                <?php while ($rlog = $restore_logs_result->fetch_assoc()): ?>
                                    <div style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <small>
                                            <strong><?php echo ucfirst($rlog['restore_type']); ?></strong> - 
                                            <?php echo date('M d, Y H:i:s', strtotime($rlog['restored_at'])); ?><br>
                                            <span class="text-muted">By: <?php echo htmlspecialchars($rlog['restored_by']); ?> | Status: <span class="badge bg-success"><?php echo $rlog['status']; ?></span></span>
                                        </small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">No restore history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
