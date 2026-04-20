<?php
include 'includes/header.php';
include 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['doc_id'])) {
    echo "<div class='alert alert-danger'>No document specified.</div>";
    include 'includes/footer.php';
    exit();
}

$doc_id = $_GET['doc_id'];

// Function to check if user has permission
function hasPermission($conn, $user_id, $permission_type) {
    // Super Admin and Director have all permissions by default
    if (isset($_SESSION['role']) && ($_SESSION['role'] == 'Super_Admin' || $_SESSION['role'] == 'Director')) {
        return true;
    }

    $sql = "SELECT id FROM user_permissions WHERE user_id = ? AND permission_type = ? AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $permission_type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get current user's ID
$user_sql = "SELECT id FROM users WHERE username = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$current_user_id = $user_result->fetch_assoc()['id'];

// Check if user has delete permission OR an approved delete request
$has_delete_permission = hasPermission($conn, $current_user_id, 'delete');

$check_sql = "SELECT r.status FROM requests r WHERE r.doc_id = ? AND r.requested_by = ? AND r.request_type = 'Delete' AND r.status = 'Approved'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $doc_id, $_SESSION['username']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if (!$has_delete_permission && $check_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>You do not have permission to delete this document.</div>";
    include 'includes/footer.php';
    exit();
}

// Fetch document details
$sql = "SELECT * FROM documents WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Document not found.</div>";
    include 'includes/footer.php';
    exit();
}

$row = $result->fetch_assoc();

function normalizeUploadUrl($path) {
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\.\./|\./)+#', '', $path);
    return ltrim($path, '/');
}

function resolveUploadFilePath($path) {
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\.\./|\./)+#', '', $path);
    return __DIR__ . '/' . ltrim($path, '/');
}

$file_url = normalizeUploadUrl($row['file_path']);
$physical_path = resolveUploadFilePath($row['file_path']);

// Handle deletion
if (isset($_POST['confirm_delete'])) {
    // Step 1: Archive to deleted_documents table (soft delete)
    $archive_sql = "INSERT INTO deleted_documents (doc_id, file_name, file_path, section, doc_type, uploaded_by, upload_date, expiry_date, description, deleted_by) 
                    SELECT doc_id, file_name, file_path, section, doc_type, uploaded_by, upload_date, expiry_date, description, ? FROM documents WHERE id = ?";
    $archive_stmt = $conn->prepare($archive_sql);
    $deleted_by = $_SESSION['username'];
    $archive_stmt->bind_param("si", $deleted_by, $doc_id);
    
    if ($archive_stmt->execute()) {
        // Step 2: Delete from documents table
        $delete_sql = "DELETE FROM documents WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $doc_id);
        
        if ($delete_stmt->execute()) {
            // Step 3: Log the deletion
            $log_sql = "INSERT INTO backup_logs (backup_type, created_by, file_path, status, notes) VALUES ('document_deletion', ?, ?, 'success', ?)";
            $log_stmt = $conn->prepare($log_sql);
            $note = "Document deleted: " . $row['file_name'] . " (ID: " . $row['doc_id'] . ")";
            $log_stmt->bind_param("sss", $deleted_by, $physical_path, $note);
            $log_stmt->execute();
            
            echo "<div class='alert alert-success'><strong>✅ Document Deleted Successfully</strong><br>The document has been moved to the Deleted Items vault and can be recovered for 30 days.</div>";
            echo "<a href='modules/" . strtolower($_SESSION['role']) . ".php' class='btn btn-primary'>Back to Dashboard</a>";
            echo " <a href='modules/super-admin-features/super_admin_backup.php' class='btn btn-info'>Go to Recovery</a>";
            include 'includes/footer.php';
            exit();
        } else {
            echo "<div class='alert alert-danger'>Error deleting document: " . $delete_stmt->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error archiving document for recovery: " . $archive_stmt->error . "</div>";
    }
}
?>

<div class="container mt-5">
    <h2>Delete Document</h2>
    <div class="card">
        <div class="card-header">
            <h3>Confirm Deletion: <?php echo htmlspecialchars($row['file_name']); ?></h3>
            <p>Document ID: <?php echo htmlspecialchars($row['doc_id']); ?></p>
        </div>
        <div class="card-body">
            <p>Are you sure you want to delete this document? This action cannot be undone.</p>
            <p><strong>File Name:</strong> <?php echo htmlspecialchars($row['file_name']); ?></p>
            <p><strong>Section:</strong> <?php echo htmlspecialchars($row['section']); ?></p>
            <p><strong>Uploaded At:</strong> <?php echo htmlspecialchars(isset($row['uploaded_at']) ? $row['uploaded_at'] : (isset($row['upload_date']) ? $row['upload_date'] : 'N/A')); ?></p>
            
            <form method="POST">
                <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete Document</button>
                <a href="modules/<?php echo strtolower($_SESSION['role']); ?>.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>