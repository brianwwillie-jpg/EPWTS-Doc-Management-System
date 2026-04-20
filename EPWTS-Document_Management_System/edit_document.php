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

// Check if user has edit permission OR an approved edit request
$has_edit_permission = hasPermission($conn, $current_user_id, 'edit');

$check_sql = "SELECT r.status FROM requests r WHERE r.doc_id = ? AND r.requested_by = ? AND r.request_type = 'Edit' AND r.status = 'Approved'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $doc_id, $_SESSION['username']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if (!$has_edit_permission && $check_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>You do not have permission to edit this document.</div>";
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

$file_url = normalizeUploadUrl($row['file_path']);

// Handle form submission
if (isset($_POST['update_document'])) {
    $new_file_name = $_POST['file_name'];
    $update_sql = "UPDATE documents SET file_name = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_file_name, $doc_id);
    
    if ($update_stmt->execute()) {
        echo "<div class='alert alert-success'>Document updated successfully.</div>";
        // Refresh the row
        $row['file_name'] = $new_file_name;
    } else {
        echo "<div class='alert alert-danger'>Error updating document: " . $update_stmt->error . "</div>";
    }
}
?>

<div class="container mt-5">
    <h2>Edit Document</h2>
    <div class="card">
        <div class="card-header">
            <h3>Edit: <?php echo htmlspecialchars($row['file_name']); ?></h3>
            <p>Document ID: <?php echo htmlspecialchars($row['doc_id']); ?></p>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="file_name" class="form-label">File Name</label>
                    <input type="text" class="form-control" id="file_name" name="file_name" value="<?php echo htmlspecialchars($row['file_name']); ?>" required>
                </div>
                <p><strong>Current File:</strong> <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank">View/Download</a></p>
                <p><strong>Section:</strong> <?php echo htmlspecialchars($row['section']); ?></p>
                <p><strong>Uploaded At:</strong> <?php echo htmlspecialchars(isset($row['uploaded_at']) ? $row['uploaded_at'] : (isset($row['upload_date']) ? $row['upload_date'] : 'N/A')); ?></p>
                
                <button type="submit" name="update_document" class="btn btn-primary">Update Document</button>
                <a href="modules/<?php echo strtolower($_SESSION['role']); ?>.php" class="btn btn-secondary">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>