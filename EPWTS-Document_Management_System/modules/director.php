<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';


// Redirect if not Director
if ($_SESSION['role'] != 'Director') { header("Location: ../index.php"); 
    
exit(); 
}

// Handle uploading documents
if (isset($_POST['upload_document'])) {
    $doc_id = $_POST['doc_id'];
    $file_name = $_POST['file_name'];
    $doc_type = $_POST['doc_type'];
    $signature = $_SESSION['username'];
    $upload_date = date('Y-m-d');
    $expiry_date = $_POST['expiry_date'];
    $description = $_POST['description'];
    
    $file = $_FILES['document_file']['name'];
    $target = "../uploads/" . basename($file);
    
    // Check if doc_id already exists
    $check_sql = "SELECT doc_id FROM documents WHERE doc_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $doc_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo "<div class='alert alert-danger'>Error: Document ID '$doc_id' already exists. Please use a different ID.</div>";
    } else if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
        $insert_sql = "INSERT INTO documents (doc_id, file_name, doc_type, signature, upload_date, expiry_date, description, file_path) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssssss", $doc_id, $file_name, $doc_type, $signature, $upload_date, $expiry_date, $description, $target);
        
        if ($insert_stmt->execute()) {
            echo "<div class='alert alert-success'>Document uploaded successfully with ID: $doc_id</div>";
        } else {
            echo "<div class='alert alert-danger'>Error uploading document: " . $insert_stmt->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error moving uploaded file.</div>";
    }
}

// Handle Approval/Decline Actions for view/delete requests
if (isset($_POST['action'])) {
    $req_id = $_POST['request_id'];
    $status = ($_POST['action'] == 'approve') ? 'Approved' : 'Declined';
    
    $update_sql = "UPDATE requests SET status = '$status' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $req_id);
    $update_stmt->execute();
}

// Get documents for status color codes - Approved (green), Declined (red), Pending (yellow), Default (black)
function getStatusColor($status) {
    switch($status) {
        case 'Approved': 
            return 'badge bg-success';
        case 'Declined': 
            return 'badge bg-danger';
        case 'Pending': 
            return 'badge bg-warning text-dark';
        default: 
            return 'badge bg-secondary';
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container mt-5">
    <h1>Director's Module</h1>
    
    <!-- Upload Documents Form -->
    <div class="card mb-4 p-4">
        <h3>Upload Document</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="doc_id">Document ID Number (e.g., DR260405) + Your 3-digit suffix:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="doc_prefix" value="DR260405" readonly>
                        <input type="text" class="form-control" id="doc_suffix" name="doc_suffix" placeholder="001" maxlength="3" required>
                    </div>
                    <input type="hidden" id="doc_id" name="doc_id" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="file_name">Document File Name</label>
                    <input type="text" class="form-control" name="file_name" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="doc_type">Document Type</label>
                    <select class="form-control" name="doc_type" required>
                        <option value="">Select Document Type</option>
                        <option value="Contracts">Contracts</option>
                        <option value="Drawings">Drawings</option>
                        <option value="Award Letters">Award Letters</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="document_file">Upload File</label>
                    <input type="file" class="form-control" name="document_file" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="description">General Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="upload_document" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <!-- Search and Filter Section -->
    <div class="card mb-4 p-4">
        <h3>Search and Filter Documents</h3>
        <form method="GET" class="row">
            <div class="col-md-3 mb-3">
                <label for="search_field">Search Field</label>
                <select class="form-control" name="search_field" id="search_field">
                    <option value="file_name">File Name</option>
                    <option value="doc_id">Document ID</option>
                    <option value="upload_date">Upload Date</option>
                    <option value="doc_type">Document Type</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="search_value">Search Value</label>
                <input type="text" class="form-control" name="search_value" id="search_value" placeholder="Enter search term">
            </div>
            <div class="col-md-3 mb-3">
                <label for="doc_type_filter">Document Type Filter</label>
                <select class="form-control" name="doc_type_filter" id="doc_type_filter">
                    <option value="">All Types</option>
                    <option value="Contracts">Contracts</option>
                    <option value="Drawings">Drawings</option>
                    <option value="Award Letters">Award Letters</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>

    <!-- Documents Table -->
    <div class="card mb-4">
        <h3 class="card-header">All Documents</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Document ID</th>
                        <th>File Name</th>
                        <th>Document Type</th>
                        <th>Signature</th>
                        <th>Upload Date</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM documents WHERE 1=1";
                    
                    if (isset($_GET['search_field']) && isset($_GET['search_value']) && !empty($_GET['search_value'])) {
                        $search_field = $_GET['search_field'];
                        $search_value = $_GET['search_value'];
                        $sql .= " AND $search_field LIKE ?";
                    }
                    
                    if (isset($_GET['doc_type_filter']) && !empty($_GET['doc_type_filter'])) {
                        $doc_type_filter = $_GET['doc_type_filter'];
                        $sql .= " AND doc_type = ?";
                    }
                    
                    $stmt = $conn->prepare($sql);
                    
                    if (isset($_GET['search_field']) && isset($_GET['search_value']) && !empty($_GET['search_value']) && isset($_GET['doc_type_filter']) && !empty($_GET['doc_type_filter'])) {
                        $search_value = "%" . $_GET['search_value'] . "%";
                        $doc_type_filter = $_GET['doc_type_filter'];
                        $stmt->bind_param("ss", $search_value, $doc_type_filter);
                    } elseif (isset($_GET['search_field']) && isset($_GET['search_value']) && !empty($_GET['search_value'])) {
                        $search_value = "%" . $_GET['search_value'] . "%";
                        $stmt->bind_param("s", $search_value);
                    } elseif (isset($_GET['doc_type_filter']) && !empty($_GET['doc_type_filter'])) {
                        $doc_type_filter = $_GET['doc_type_filter'];
                        $stmt->bind_param("s", $doc_type_filter);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($doc = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($doc['doc_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($doc['file_name']) . "</td>";
                            echo "<td>" . htmlspecialchars(isset($doc['doc_type']) ? $doc['doc_type'] : 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars(isset($doc['signature']) ? $doc['signature'] : 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars(isset($doc['upload_date']) ? $doc['upload_date'] : date('Y-m-d')) . "</td>";
                            echo "<td>" . htmlspecialchars(isset($doc['expiry_date']) ? $doc['expiry_date'] : 'N/A') . "</td>";
                            echo "<td>";
                            echo "<a href='../edit_document.php?doc_id=" . $doc['id'] . "' class='btn btn-sm btn-warning'>Edit</a> ";
                            echo "<a href='../view_document.php?doc_id=" . $doc['id'] . "' class='btn btn-sm btn-info'>View</a> ";
                            echo "<a href='../delete_document.php?doc_id=" . $doc['id'] . "' class='btn btn-sm btn-danger'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No documents found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notifications Section (View/Delete Requests) -->
    <div class="card">
        <h3 class="card-header">Pending Requests (View/Delete Approvals)</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Division</th>
                        <th>Requested By</th>
                        <th>Document ID</th>
                        <th>Request Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $requests_sql = "SELECT r.*, d.file_name, d.doc_id FROM requests r JOIN documents d ON r.doc_id = d.id WHERE r.status = 'Pending'";
                    $requests = $conn->query($requests_sql);
                    
                    if ($requests && $requests->num_rows > 0) {
                        while ($req = $requests->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($req['requested_by']) . "</td>";
                           
                            echo "<td>" . htmlspecialchars($req['doc_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($req['request_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($req['reason']) . "</td>";
                            echo "<td><span class='badge bg-warning text-dark'>Pending</span></td>";
                            echo "<td>";
                            echo "<form method='POST' style='display:inline;'>";
                            echo "<input type='hidden' name='request_id' value='" . $req['id'] . "'>";
                            echo "<button name='action' value='approve' class='btn btn-success btn-sm'>Approve</button> ";
                            echo "<button name='action' value='decline' class='btn btn-danger btn-sm'>Decline</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No pending requests.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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
</script>
