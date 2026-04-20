<?php
include '../includes/header.php';
include '../config/db.php';
include '../includes/functions.php'; // For any utility functions
session_start();

// Check if user is logged in and has Energy role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Energy') {
    header("Location: ../index.php");
    exit();
}

// Define section-specific variables
$section = 'Energy';
$prefix = 'EP';

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
        echo "<div class='alert alert-danger'>Error: Document ID '$doc_id' already exists. Please use a different suffix.</div>";
    } else if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
        $insert_sql = "INSERT INTO documents (doc_id, file_name, file_path, section) 
                       VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $doc_id, $file_name, $target, $section);
        
        if ($insert_stmt->execute()) {
            echo "<div class='alert alert-success'>Document uploaded successfully with ID: $doc_id</div>";
        } else {
            echo "<div class='alert alert-danger'>Error uploading document: " . $insert_stmt->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error moving uploaded file.</div>";
    }
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
        echo "<div class='alert alert-success'>Request submitted successfully. Waiting for director's approval.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error submitting request: " . $stmt->error . "</div>";
    }
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

// Fetch request statuses for actions
$request_statuses_sql = "SELECT doc_id, request_type, status FROM requests WHERE requested_by = ?";
$request_statuses_stmt = $conn->prepare($request_statuses_sql);
$request_statuses_stmt->bind_param("s", $_SESSION['username']);
$request_statuses_stmt->execute();
$request_statuses_result = $request_statuses_stmt->get_result();
$request_statuses = [];
while ($status_row = $request_statuses_result->fetch_assoc()) {
    $request_statuses[$status_row['doc_id']][$status_row['request_type']] = $status_row['status'];
}
?>

<div class="container mt-5">
    <h2>Energy Projects - Document Management</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>. You can upload and view documents in your department only. To edit, view, or delete, you must request approval from the Director.</p>
    
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
            </div>
            <div class="mb-3">
                <label for="document_file">Upload File</label>
                <input type="file" class="form-control" name="document_file" required>
            </div>
            <button type="submit" name="upload_document" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <!-- Notifications Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Notifications</h3>
        </div>
        <div class="card-body">
            <?php if ($notifications->num_rows > 0): ?>
                <ul class="list-group">
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($notif['request_type']); ?> Request</strong> for "<?php echo htmlspecialchars($notif['file_name']); ?>" has been 
                            <span class="badge bg-<?php echo $notif['status'] == 'Approved' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($notif['status']); ?></span>.
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No new notifications.</p>
            <?php endif; ?>
        </div>
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
                            // Determine the class based on status
                            $statusClass = 'badge bg-secondary'; // Default
                            if (isset($row['status'])) {
                                if ($row['status'] == 'Approved') $statusClass = 'badge bg-success';
                                elseif ($row['status'] == 'Declined') $statusClass = 'badge bg-danger';
                                elseif ($row['status'] == 'Pending') $statusClass = 'badge bg-warning text-dark';
                            }
                            echo "<span class='$statusClass'>" . (isset($row['status']) ? $row['status'] : 'N/A') . "</span>";
                        ?>
                    </td>
                    <td>
                        <?php 
                        $doc_id = $row['id'];
                        $edit_status = isset($request_statuses[$doc_id]['Edit']) ? $request_statuses[$doc_id]['Edit'] : null;
                        $view_status = isset($request_statuses[$doc_id]['View']) ? $request_statuses[$doc_id]['View'] : null;
                        $delete_status = isset($request_statuses[$doc_id]['Delete']) ? $request_statuses[$doc_id]['Delete'] : null;
                        ?>
                        
                        <!-- Edit Button -->
                        <?php if ($edit_status == 'Approved'): ?>
                            <a href="../edit_document.php?doc_id=<?php echo $doc_id; ?>" class="btn btn-success btn-sm">Edit</a>
                        <?php elseif ($edit_status == 'Pending'): ?>
                            <button class="btn btn-warning btn-sm" disabled>Edit Pending</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-warning btn-sm" onclick="showRequestModal('Edit', '<?php echo $doc_id; ?>')">Request Edit</button>
                        <?php endif; ?>
                        
                        <!-- View Button -->
                        <?php if ($view_status == 'Approved'): ?>
                            <a href="../view_document.php?doc_id=<?php echo $doc_id; ?>" class="btn btn-success btn-sm">View</a>
                        <?php elseif ($view_status == 'Pending'): ?>
                            <button class="btn btn-warning btn-sm" disabled>View Pending</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="showRequestModal('View', '<?php echo $doc_id; ?>')">Request View</button>
                        <?php endif; ?>
                        
                        <!-- Delete Button -->
                        <?php if ($delete_status == 'Approved'): ?>
                            <a href="../delete_document.php?doc_id=<?php echo $doc_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this document?')">Delete</a>
                        <?php elseif ($delete_status == 'Pending'): ?>
                            <button class="btn btn-warning btn-sm" disabled>Delete Pending</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-danger btn-sm" onclick="showRequestModal('Delete', '<?php echo $doc_id; ?>')">Request Delete</button>
                        <?php endif; ?>
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
