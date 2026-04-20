<?php
session_start();
include "../../config/db.php";


// Security Check: Only Super Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super_Admin') {
    die("Access Denied. Super Admin privileges required.");
    exit();
}

// Handle permission updates
if (isset($_POST['update_permissions'])) {
    $user_id = $_POST['user_id'];
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    // First, deactivate all existing permissions for this user
    $deactivate_sql = "UPDATE user_permissions SET is_active = FALSE WHERE user_id = ?";
    $deactivate_stmt = $conn->prepare($deactivate_sql);
    $deactivate_stmt->bind_param("i", $user_id);
    $deactivate_stmt->execute();

    // Then, add active permissions
    $insert_sql = "INSERT INTO user_permissions (user_id, permission_type, granted_by, is_active) VALUES (?, ?, ?, TRUE)
                   ON DUPLICATE KEY UPDATE is_active = TRUE, granted_by = VALUES(granted_by), granted_at = CURRENT_TIMESTAMP";
    $insert_stmt = $conn->prepare($insert_sql);

    foreach ($permissions as $permission) {
        $insert_stmt->bind_param("iss", $user_id, $permission, $_SESSION['username']);
        $insert_stmt->execute();
    }

    echo "<div class='alert alert-success'>Permissions updated successfully!</div>";
}

// Get all users except Super Admin
$users_sql = "SELECT id, username, name, role FROM users WHERE role != 'Super_Admin' ORDER BY name";
$users_result = $conn->query($users_sql);

// Function to check if user has permission
function hasPermission($conn, $user_id, $permission_type) {
    $sql = "SELECT id FROM user_permissions WHERE user_id = ? AND permission_type = ? AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $permission_type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get permission audit log
$audit_sql = "SELECT up.*, u.username, u.name
              FROM user_permissions up
              JOIN users u ON up.user_id = u.id
              ORDER BY up.granted_at DESC LIMIT 20";
$audit_result = $conn->query($audit_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPWTS DMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../includes/styles.css">
</head>
<body>
<div class ="links">
    <a href="../super_admin.php" class="btn btn-secondary">Back</a>
</div>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Feature Management
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Manage user permissions for document operations. Grant or revoke access to view, edit, delete, and approve documents.</p>

                    <!-- Permission Management Section -->
                    <div class="row">
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card border">
                                <div class="card-header">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?> (<?php echo $user['role']; ?>)</small>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Permissions:</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="view"
                                                       id="view_<?php echo $user['id']; ?>"
                                                       <?php echo hasPermission($conn, $user['id'], 'view') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="view_<?php echo $user['id']; ?>">
                                                    <i class="fas fa-eye text-info me-1"></i>View Documents
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="edit"
                                                       id="edit_<?php echo $user['id']; ?>"
                                                       <?php echo hasPermission($conn, $user['id'], 'edit') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="edit_<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit text-warning me-1"></i>Edit Documents
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="delete"
                                                       id="delete_<?php echo $user['id']; ?>"
                                                       <?php echo hasPermission($conn, $user['id'], 'delete') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="delete_<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash text-danger me-1"></i>Delete Documents
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="approve"
                                                       id="approve_<?php echo $user['id']; ?>"
                                                       <?php echo hasPermission($conn, $user['id'], 'approve') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="approve_<?php echo $user['id']; ?>">
                                                    <i class="fas fa-check-circle text-success me-1"></i>Approve Requests
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" name="update_permissions" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-save me-1"></i>Update Permissions
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Permission Audit Log -->
                    <div class="mt-5">
                        <h4><i class="fas fa-history me-2"></i>Recent Permission Changes</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User</th>
                                        <th>Permission</th>
                                        <th>Status</th>
                                        <th>Granted By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($audit = $audit_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($audit['name']); ?> (@<?php echo htmlspecialchars($audit['username']); ?>)</td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $audit['permission_type'] == 'view' ? 'info' :
                                                     ($audit['permission_type'] == 'edit' ? 'warning' :
                                                      ($audit['permission_type'] == 'delete' ? 'danger' : 'success'));
                                            ?>">
                                                <i class="fas fa-<?php
                                                    echo $audit['permission_type'] == 'view' ? 'eye' :
                                                         ($audit['permission_type'] == 'edit' ? 'edit' :
                                                          ($audit['permission_type'] == 'delete' ? 'trash' : 'check-circle'));
                                                ?> me-1"></i>
                                                <?php echo ucfirst($audit['permission_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $audit['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $audit['is_active'] ? 'Granted' : 'Revoked'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($audit['granted_by']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($audit['granted_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Notes -->
                    <div class="mt-4 alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>System Notes</h5>
                        <ul class="mb-0">
                            <li><strong>Director Role:</strong> By default, Directors have all permissions (view, edit, delete, approve).</li>
                            <li><strong>Permission Hierarchy:</strong> Users need 'view' permission to request access, and approval permission to approve requests.</li>
                            <li><strong>Audit Trail:</strong> All permission changes are logged with timestamps for accountability.</li>
                            <li><strong>Role-Based Defaults:</strong> Super Admins maintain full access regardless of individual permissions.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../../includes/footer.php"; ?>
