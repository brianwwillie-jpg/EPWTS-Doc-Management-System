<?php

include '../includes/header.php';
include '../config/db.php';
session_start();

// Security Check: Only Super Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super_Admin') {
    die("Access Denied. Super Admin privileges required.");
    exit();
}

// Check if username already exists
    $check_sql = "SELECT name FROM users WHERE name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

?>
<div class="container mt-5">
    <!-- Welcome Card -->
    <div class="card" style="background: linear-gradient(135deg, #004566 0%, #3c6e71 100%); color: #180b0b; border: none; margin-bottom: 30px;">
        <div class="card-body p-4">
            <h2 style="font-weight: 700; margin-bottom: 10px;">
                <i class="fas fa-tachometer-alt me-2"></i>Super Admin Dashboard
            </h2>
            <p style="margin: 0; font-size: 1.05rem; opacity: 0.95;">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong> | Use the links below to manage the entire system
            </p>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-users" style="font-size: 2.5rem; color: #3c6e71; margin-bottom: 10px;"></i>
                <h4>Total Users</h4>
                <div class="stat-number">
                    <?php
                    $user_count_sql = "SELECT COUNT(*) AS total_users FROM users";
                    $user_count_result = $conn->query($user_count_sql);
                    echo $user_count_result->fetch_assoc()['total_users'];
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-file-alt" style="font-size: 2.5rem; color: #004566; margin-bottom: 10px;"></i>
                <h4>Total Documents</h4>
                <div class="stat-number">
                    <?php
                    $doc_count_sql = "SELECT COUNT(*) AS total_docs FROM documents";
                    $doc_count_result = $conn->query($doc_count_sql);
                    echo $doc_count_result->fetch_assoc()['total_docs'];
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-trash-alt" style="font-size: 2.5rem; color: #8b3a3a; margin-bottom: 10px;"></i>
                <h4>Deleted Documents</h4>
                <div class="stat-number">
                    <?php
                    $del_count_sql = "SELECT COUNT(*) AS deleted_docs FROM deleted_documents";
                    $del_count_result = $conn->query($del_count_sql);
                    echo $del_count_result->fetch_assoc()['deleted_docs'];
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-exchange-alt" style="font-size: 2.5rem; color: #a5671a; margin-bottom: 10px;"></i>
                <h4>Total Backups</h4>
                <div class="stat-number">
                    <?php
                    $backup_count_sql = "SELECT COUNT(*) AS total_backups FROM backup_logs";
                    $backup_count_result = $conn->query($backup_count_sql);
                    echo $backup_count_result->fetch_assoc()['total_backups'];
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Links Grid -->
    <div class="row mb-4">
        <div class="col-12">
            <h3 style="color: #004566; font-weight: 700; margin-bottom: 20px;">
                <i class="fas fa-tasks me-2"></i>Management Features
            </h3>
        </div>
        
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-users-cog me-2" style="color: #3c6e71;"></i>User Management
                    </h5>
                    <p class="card-text">Create, edit, and manage user accounts and permissions across the system.</p>
                    <a href="super-admin-features/super_admin_user_mngt.php" class="btn btn-primary btn-sm" style="background-color: #3c6e71; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Manage Users
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-cogs me-2" style="color: #004566;"></i>Feature Management
                    </h5>
                    <p class="card-text">Control which features are available and configure system settings.</p>
                    <a href="super-admin-features/super_admin_feature_mngt.php" class="btn btn-primary btn-sm" style="background-color: #004566; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Manage Features
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-th me-2" style="color: #353535;"></i>Module Management
                    </h5>
                    <p class="card-text">Manage system modules and their configurations for different departments.</p>
                    <a href="super-admin-features/super_admin_feature_mngt.php" class="btn btn-primary btn-sm" style="background-color: #353535; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Manage Modules
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-file-invoice me-2" style="color: #3c6e71;"></i>Document Management
                    </h5>
                    <p class="card-text">View, edit, delete, and organize all documents in the system with full control.</p>
                    <a href="super-admin-features/super_admin_doc_mngt.php" class="btn btn-primary btn-sm" style="background-color: #3c6e71; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Manage Documents
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-history me-2" style="color: #004566;"></i>Audit Logs
                    </h5>
                    <p class="card-text">Review system activity logs and track all user actions for compliance.</p>
                    <a href="super-admin-features/super_admin_audit_log.php" class="btn btn-primary btn-sm" style="background-color: #004566; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>View Logs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-hdd me-2" style="color: #a5671a;"></i>System Monitor
                    </h5>
                    <p class="card-text">Monitor system health, performance metrics, and resource usage.</p>
                    <a href="super-admin-features/super_admin_system_monitor.php" class="btn btn-primary btn-sm" style="background-color: #a5671a; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>View Monitor
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-sliders-h me-2" style="color: #353535;"></i>System Settings
                    </h5>
                    <p class="card-text">Configure system-wide settings, backup schedules, and maintenance options.</p>
                    <a href="super-admin-features/super_admin_settings.php" class="btn btn-primary btn-sm" style="background-color: #353535; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card module-card h-100">
                <div class="card-body">
                    <h5 class="card-title module-card-title">
                        <i class="fas fa-backup me-2" style="color: #3c6e71;"></i>Backup & Restore
                    </h5>
                    <p class="card-text">Create backups, restore from backups, and recover deleted documents.</p>
                    <a href="super-admin-features/super_admin_backup.php" class="btn btn-primary btn-sm" style="background-color: #3c6e71; border: none;">
                        <i class="fas fa-arrow-right me-1"></i>Backup & Restore
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview Card -->
    <div class="card" style="border-left: 5px solid #3c6e71; margin-top: 30px;">
        <div class="card-header" style="background-color: #004566; border-bottom: 3px solid #3c6e71;">
            <h4 style="margin: 0; color: #ffffff; font-weight: 700;">
                <i class="fas fa-info-circle me-2"></i>System Overview
            </h4>
        </div>
        <div class="card-body">
            <p class="lead">You have complete control over the EPWTS Document Management System. From this dashboard, you can:</p>
            <div class="row mt-4">
                <div class="col-md-6">
                    <ul class="list-unstyled" style="color: #353535;">
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #3c6e71; margin-right: 8px;"></i>
                            <strong>Manage Users</strong> - Create and configure user roles and permissions
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #3c6e71; margin-right: 8px;"></i>
                            <strong>Control Features</strong> - Enable/disable features for departments
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #3c6e71; margin-right: 8px;"></i>
                            <strong>Manage Modules</strong> - Configure departmental modules
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled" style="color: #353535;">
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #004566; margin-right: 8px;"></i>
                            <strong>Document Control</strong> - Complete visibility and control over all documents
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #004566; margin-right: 8px;"></i>
                            <strong>Disaster Recovery</strong> - Automatic backups and recovery options
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fas fa-check-circle" style="color: #004566; margin-right: 8px;"></i>
                            <strong>Compliance Tracking</strong> - Complete audit trails and logs
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>