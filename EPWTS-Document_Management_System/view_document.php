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

// Check if user has view permission OR an approved view request
$has_view_permission = hasPermission($conn, $current_user_id, 'view');

$check_sql = "SELECT id FROM requests WHERE doc_id = ? AND requested_by = ? AND request_type = 'View' AND status = 'Approved' ORDER BY id DESC LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $doc_id, $_SESSION['username']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if (!$has_view_permission && $check_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>You do not have permission to view this document.</div>";
    include 'includes/footer.php';
    exit();
}

// Note: one-time view permission requires a DB update to support a 'Used' request status.

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
?>

<div class="container mt-5">
    <h2>View Document</h2>
    <div class="card">
        <div class="card-header">
            <h3><?php echo htmlspecialchars($row['file_name']); ?></h3>
            <p>Document ID: <?php echo htmlspecialchars($row['doc_id']); ?></p>
        </div>
        <div class="card-body">
            <p><strong>File Path:</strong> <?php echo htmlspecialchars($row['file_path']); ?></p>
            <p><strong>Section:</strong> <?php echo htmlspecialchars($row['section']); ?></p>
            <p><strong>Uploaded At:</strong> <?php echo htmlspecialchars(isset($row['uploaded_at']) ? $row['uploaded_at'] : (isset($row['upload_date']) ? $row['upload_date'] : 'N/A')); ?></p>
            
            <a href="<?php echo htmlspecialchars($file_url); ?>" class="btn btn-primary" target="_blank">Download/View File</a>
            <a href="modules/<?php echo strtolower($_SESSION['role']); ?>.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>