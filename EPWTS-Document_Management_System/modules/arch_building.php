<?php
include '../includes/header.php';
include '../config/db.php';
include '../includes/functions.php'; // For any utility functions
session_start();

// Check if user is logged in and has Arch_Building role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Arch_Building') {
    header("Location: ../index.php");
    exit();
}

// Display success messages from session
$upload_success = isset($_SESSION['upload_success']) ? $_SESSION['upload_success'] : null;
$upload_error = isset($_SESSION['upload_error']) ? $_SESSION['upload_error'] : null;
$request_success = isset($_SESSION['request_success']) ? $_SESSION['request_success'] : null;
$request_error = isset($_SESSION['request_error']) ? $_SESSION['request_error'] : null;

unset($_SESSION['upload_success'], $_SESSION['upload_error'], $_SESSION['request_success'], $_SESSION['request_error']);

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

// Check user permissions
$can_view = hasPermission($conn, $current_user_id, 'view');
$can_edit = hasPermission($conn, $current_user_id, 'edit');
$can_delete = hasPermission($conn, $current_user_id, 'delete');
$can_approve = hasPermission($conn, $current_user_id, 'approve');

// Define section-specific variables
$section = 'Arch_Building';
$prefix = 'AB';

// Handle document upload
if (isset($_POST['upload_document'])) {
    $doc_suffix = $_POST['doc_suffix'];
    $doc_id = generateDocID($prefix, $doc_suffix);
    $file_name = $_POST['file_name'];
    
    $file = $_FILES['document_file']['name'];
    $target = "../uploads/" . basename($file);
    
    // Check if doc_id already exists
    $check_sql = "SELECT doc_id FROM documents WHERE doc_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $doc_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['upload_error'] = "Error: Document ID '$doc_id' already exists. Please use a different suffix.";
    } else if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
        $insert_sql = "INSERT INTO documents (doc_id, file_name, file_path, section) 
                       VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $doc_id, $file_name, $target, $section);
        
        if ($insert_stmt->execute()) {
            $_SESSION['upload_success'] = "Document uploaded successfully with ID: $doc_id";
        } else {
            $_SESSION['upload_error'] = "Error uploading document: " . $insert_stmt->error;
        }
    } else {
        $_SESSION['upload_error'] = "Error moving uploaded file.";
    }
    header("Location: arch_building.php");
    exit();
}

// Handle request submissions
if (isset($_POST['submit_request'])) {
    $doc_id = $_POST['doc_id'];
    $request_type = $_POST['request_type'];
    $reason = $_POST['reason'];
    $requested_by = $_SESSION['username'];

    $insert_sql = "INSERT INTO requests (requested_by, doc_id, request_type, reason, status) VALUES (?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssss", $requested_by, $doc_id, $request_type, $reason);
    
    if ($stmt->execute()) {
        $_SESSION['request_success'] = "Request submitted successfully. Waiting for director's approval.";
    } else {
        $_SESSION['request_error'] = "Error submitting request: " . $stmt->error;
    }
    header("Location: arch_building.php");
    exit();
}

// Fetch documents for this section
$sql = "SELECT * FROM documents WHERE section = '$section'";
$result = $conn->query($sql);

// Fetch user notifications (approved/declined requests)
$notifications_sql = "SELECT r.*, d.file_name FROM requests r JOIN documents d ON r.doc_id = d.id WHERE r.requested_by = ? AND r.status != 'Pending' ORDER BY r.id DESC";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("s", $_SESSION['username']);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$notifications = [];
while ($notif = $notifications_result->fetch_assoc()) {
    $notifications[] = $notif;
}
$notification_count = count($notifications);

if (!function_exists('requestBadge')) {
    function requestBadge($status) {
        switch ($status) {
            case 'Approved': return '<span class="badge bg-success me-1">Approved</span>';
            case 'Declined': return '<span class="badge bg-danger me-1">Declined</span>';
            case 'Pending': return '<span class="badge bg-warning text-dark me-1">Pending</span>';
            case 'Used': return '<span class="badge bg-info text-dark me-1">Used</span>';
            default: return '<span class="badge bg-secondary me-1">None</span>';
        }
    }
}
?>

<div class="container mt-5">
    <h2>Architectural and Building Works - Document Management</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>. You can upload documents in your department. Document access is controlled by permissions granted by the Super Admin.</p>
    
    <!-- Notifications -->
    <?php if ($notification_count > 0): ?>
    <div class="alert alert-info">
        <h5>Recent Notifications:</h5>
        <ul class="list-group list-group-flush">
        <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    <strong><?php echo htmlspecialchars($notif['request_type']); ?> Request</strong> for "<?php echo htmlspecialchars($notif['file_name']); ?>" has been <strong><?php echo htmlspecialchars($notif['status']); ?></strong>.
                    <div class="small text-muted"><?php echo isset($notif['created_at']) && $notif['created_at'] ? date('Y-m-d H:i:s', strtotime($notif['created_at'])) : 'No timestamp'; ?></div>
                </div>
                <span class="badge bg-<?php echo $notif['status'] == 'Approved' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($notif['status']); ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if ($notification_count > 5): ?>
            <button class="btn btn-link p-0 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#olderNotificationsArch" aria-expanded="false" aria-controls="olderNotificationsArch">
                Show older notifications (<?php echo $notification_count - 5; ?>)
            </button>
            <div class="collapse mt-2" id="olderNotificationsArch">
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($notifications, 5) as $notif): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($notif['request_type']); ?> Request</strong> for "<?php echo htmlspecialchars($notif['file_name']); ?>" has been <strong><?php echo htmlspecialchars($notif['status']); ?></strong>.
                                <div class="small text-muted"><?php echo isset($notif['created_at']) && $notif['created_at'] ? date('Y-m-d H:i:s', strtotime($notif['created_at'])) : 'No timestamp'; ?></div>
                            </div>
                            <span class="badge bg-<?php echo $notif['status'] == 'Approved' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($notif['status']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Upload Documents Form -->
    <div class="card mb-4 p-4">
        <h3>Upload Document</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="doc_id">Document ID Number (<?php echo $prefix; ?> + Date + Your 3-digit suffix):</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="doc_prefix" value="<?php echo $prefix . date('ymd'); ?>" readonly>
                        <input type="text" class="form-control" id="doc_suffix" name="doc_suffix" placeholder="001" maxlength="3" required>
                    </div>
                    <input type="hidden" id="doc_id" name="doc_id" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="file_name">Document File Name</label>
                    <input type="text" class="form-control" name="file_name" required>
                </div>
                <div class="mt-3">
            <input type="text" name="file_no" class="form-control" placeholder="Assign File Number (e.g. 001)" required>
            <input type="text" name="file_name" class="form-control mt-2" placeholder="Document Name" required>
            <select name="doc_type" class="form-control mt-2">
                <option>Contracts</option>
                <option>Drawings</option>
                <option>Award Letters</option>
                <option>Profiles</option>
                <option>Others</option>
            </select>
            
        </div>
            </div>
            <div class="mb-3">
                <label for="document_file">Upload File</label>
                <input type="file" class="form-control" name="document_file" required>
            </div>
            <button type="submit" name="upload_document" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <!-- Documents Table -->
    <div class="card">
        <div class="card-header">
            <h3>Department Documents</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Doc ID</th>
                        <th>File Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['doc_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                    <td>
                        <?php
                        $view_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'View' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                        $view_stmt = $conn->prepare($view_sql);
                        $view_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                        $view_stmt->execute();
                        $view_result = $view_stmt->get_result();
                        $view_status = $view_result->num_rows > 0 ? $view_result->fetch_assoc()['status'] : 'none';

                        $edit_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'Edit' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                        $edit_stmt = $conn->prepare($edit_sql);
                        $edit_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                        $edit_stmt->execute();
                        $edit_result = $edit_stmt->get_result();
                        $edit_status = $edit_result->num_rows > 0 ? $edit_result->fetch_assoc()['status'] : 'none';

                        $delete_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'Delete' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                        $delete_stmt = $conn->prepare($delete_sql);
                        $delete_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                        $delete_stmt->execute();
                        $delete_result = $delete_stmt->get_result();
                        $delete_status = $delete_result->num_rows > 0 ? $delete_result->fetch_assoc()['status'] : 'none';

                        echo '<div class="mb-1">View: ' . requestBadge($view_status) . '</div>';
                        echo '<div class="mb-1">Edit: ' . requestBadge($edit_status) . '</div>';
                        echo '<div class="mb-1">Delete: ' . requestBadge($delete_status) . '</div>';
                        ?>
                    </td>
                    <td>
                        <?php
                        // View Button - Check permission first
                        if ($can_view) {
                            echo '<a href="../view_document.php?doc_id=' . $row['id'] . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View</a>';
                        } else {
                            // Check if there's an approved request
                            $view_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'View' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                            $view_stmt = $conn->prepare($view_sql);
                            $view_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                            $view_stmt->execute();
                            $view_result = $view_stmt->get_result();
                            $view_status = $view_result->num_rows > 0 ? $view_result->fetch_assoc()['status'] : 'none';

                            if ($view_status == 'Approved') {
                                echo '<a href="../view_document.php?doc_id=' . $row['id'] . '" class="btn btn-success btn-sm"><i class="fas fa-eye"></i> View</a>';
                            } elseif ($view_status == 'Declined') {
                                echo '<button class="btn btn-danger btn-sm" disabled><i class="fas fa-times"></i> Declined</button>';
                            } elseif ($view_status == 'Pending') {
                                echo '<button class="btn btn-warning btn-sm" disabled><i class="fas fa-clock"></i> Pending</button>';
                            } else {
                                echo '<button type="button" class="btn btn-secondary btn-sm" onclick="showRequestModal(\'View\', \'' . $row['id'] . '\')"><i class="fas fa-hand-paper"></i> Request View</button>';
                            }
                        }
                        ?>

                        <?php
                        // Edit Button - Check permission first
                        if ($can_edit) {
                            echo ' <a href="../edit_document.php?doc_id=' . $row['id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>';
                        } else {
                            // Check if there's an approved request
                            $edit_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'Edit' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                            $edit_stmt = $conn->prepare($edit_sql);
                            $edit_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                            $edit_stmt->execute();
                            $edit_result = $edit_stmt->get_result();
                            $edit_status = $edit_result->num_rows > 0 ? $edit_result->fetch_assoc()['status'] : 'none';

                            if ($edit_status == 'Approved') {
                                echo ' <a href="../edit_document.php?doc_id=' . $row['id'] . '" class="btn btn-success btn-sm"><i class="fas fa-edit"></i> Edit</a>';
                            } elseif ($edit_status == 'Declined') {
                                echo ' <button class="btn btn-danger btn-sm" disabled><i class="fas fa-times"></i> Declined</button>';
                            } elseif ($edit_status == 'Pending') {
                                echo ' <button class="btn btn-warning btn-sm" disabled><i class="fas fa-clock"></i> Pending</button>';
                            } else {
                                echo ' <button type="button" class="btn btn-secondary btn-sm" onclick="showRequestModal(\'Edit\', \'' . $row['id'] . '\')"><i class="fas fa-hand-paper"></i> Request Edit</button>';
                            }
                        }
                        ?>

                        <?php
                        // Delete Button - Check permission first
                        if ($can_delete) {
                            echo ' <a href="../delete_document.php?doc_id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</a>';
                        } else {
                            // Check if there's an approved request
                            $delete_sql = "SELECT status FROM requests WHERE doc_id = ? AND request_type = 'Delete' AND requested_by = ? ORDER BY id DESC LIMIT 1";
                            $delete_stmt = $conn->prepare($delete_sql);
                            $delete_stmt->bind_param("is", $row['id'], $_SESSION['username']);
                            $delete_stmt->execute();
                            $delete_result = $delete_stmt->get_result();
                            $delete_status = $delete_result->num_rows > 0 ? $delete_result->fetch_assoc()['status'] : 'none';

                            if ($delete_status == 'Approved') {
                                echo ' <a href="../delete_document.php?doc_id=' . $row['id'] . '" class="btn btn-success btn-sm"><i class="fas fa-trash"></i> Delete</a>';
                            } elseif ($delete_status == 'Declined') {
                                echo ' <button class="btn btn-danger btn-sm" disabled><i class="fas fa-times"></i> Declined</button>';
                            } elseif ($delete_status == 'Pending') {
                                echo ' <button class="btn btn-warning btn-sm" disabled><i class="fas fa-clock"></i> Pending</button>';
                            } else {
                                echo ' <button type="button" class="btn btn-secondary btn-sm" onclick="showRequestModal(\'Delete\', \'' . $row['id'] . '\')"><i class="fas fa-hand-paper"></i> Request Delete</button>';
                            }
                        }
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="requestForm">
                <div class="modal-body">
                    <input type="hidden" name="doc_id" id="modal_doc_id">
                    <input type="hidden" name="request_type" id="modal_request_type">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Request</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Combine prefix and suffix for doc_id
document.getElementById('doc_suffix').addEventListener('change', function() {
    const prefix = document.getElementById('doc_prefix').value;
    const suffix = this.value.padStart(3, '0');
    document.getElementById('doc_id').value = prefix + suffix;
});

// Function to show the request modal
function showRequestModal(requestType, docId) {
    document.getElementById('modal_request_type').value = requestType;
    document.getElementById('modal_doc_id').value = docId;
    document.getElementById('requestModalLabel').textContent = 'Request ' + requestType + ' Approval';
    new bootstrap.Modal(document.getElementById('requestModal')).show();
}
</script>
