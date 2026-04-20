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

// ============= HANDLE ACTIONS =============

// Delete Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $doc_id = intval($_POST['doc_id']);
    
    // Get document details for audit log
    $audit_sql = "SELECT doc_id, file_name FROM documents WHERE id = ?";
    $audit_stmt = $conn->prepare($audit_sql);
    $audit_stmt->bind_param("i", $doc_id);
    $audit_stmt->execute();
    $audit_result = $audit_stmt->get_result();
    $audit_row = $audit_result->fetch_assoc();
    
    // Delete document
    $sql_delete = "DELETE FROM documents WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $doc_id);
    
    if ($stmt->execute()) {
        $success_message = "Document '{$audit_row['file_name']}' ({$audit_row['doc_id']}) has been deleted.";
    } else {
        $error_message = "Error deleting document: " . htmlspecialchars($conn->error);
    }
    $stmt->close();
    $audit_stmt->close();
}

// Get filter values
$filter_section = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : '';
$filter_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
$filter_search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$filter_expiry = isset($_GET['expiry']) ? htmlspecialchars($_GET['expiry']) : '';

// ============= BUILD QUERY WITH FILTERS =============
$sql = "SELECT id, doc_id, file_name, section, doc_type, uploaded_by, upload_date, expiry_date, description, file_path FROM documents WHERE 1=1";
$where_clauses = [];

if (!empty($filter_section)) {
    $where_clauses[] = "section = '" . $conn->real_escape_string($filter_section) . "'";
}
if (!empty($filter_type)) {
    $where_clauses[] = "doc_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if (!empty($filter_search)) {
    $where_clauses[] = "(file_name LIKE '%" . $conn->real_escape_string($filter_search) . "%' OR doc_id LIKE '%" . $conn->real_escape_string($filter_search) . "%')";
}
if ($filter_expiry === 'expired') {
    $where_clauses[] = "expiry_date < CURDATE()";
} elseif ($filter_expiry === 'expiring') {
    $where_clauses[] = "expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

if (!empty($where_clauses)) {
    $sql .= " AND " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY upload_date DESC";
$result = $conn->query($sql);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_docs,
    SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired_docs,
    SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
    COUNT(DISTINCT section) as total_sections,
    COUNT(DISTINCT uploaded_by) as total_uploaders
FROM documents";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get unique sections for filter
$sections_sql = "SELECT DISTINCT section FROM documents ORDER BY section";
$sections_result = $conn->query($sections_sql);

// Get unique document types
$types_sql = "SELECT DISTINCT doc_type FROM documents WHERE doc_type IS NOT NULL ORDER BY doc_type";
$types_result = $conn->query($types_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPWTS DMS - Super Admin Document Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
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
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stats-card h5 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .table-wrapper {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .table-wrapper table {
            margin-bottom: 0;
        }
        
        .table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .section-badge {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .section-AB { background-color: #e7f3ff; color: #0056b3; }
        .section-CW { background-color: #fff3e0; color: #e65100; }
        .section-AD { background-color: #e8f5e9; color: #00695c; }
        .section-EP { background-color: #f3e5f5; color: #6a1b9a; }
        .section-DO { background-color: #fce4ec; color: #880e4f; }
        
        .status-expired {
            background-color: #ffebee;
            color: #c62828;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-expiring {
            background-color: #fff8e1;
            color: #e65100;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .btn-sm {
            padding: 0.4rem 0.7rem;
            font-size: 0.85rem;
        }
        
        .no-documents {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-documents i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="header-section">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> Document Management System</h2>
                    <small class="opacity-75">Complete visibility and control over all system documents</small>
                </div>
                <a href="../super_admin.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Success/Error Messages -->
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h5><i class="bi bi-file-text"></i> Total Documents</h5>
                    <div class="number"><?php echo $stats['total_docs'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #dc3545;">
                    <h5><i class="bi bi-exclamation-circle"></i> Expired</h5>
                    <div class="number" style="color: #dc3545;"><?php echo $stats['expired_docs'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #ffc107;">
                    <h5><i class="bi bi-clock-history"></i> Expiring Soon</h5>
                    <div class="number" style="color: #ffc107;"><?php echo $stats['expiring_soon'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #17a2b8;">
                    <h5><i class="bi bi-diagram-3"></i> Sections</h5>
                    <div class="number" style="color: #17a2b8;"><?php echo $stats['total_sections'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Documents</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or ID..." 
                           value="<?php echo $filter_search; ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="section">
                        <option value="">All Sections</option>
                        <?php 
                        if ($sections_result && $sections_result->num_rows > 0) {
                            while ($sec = $sections_result->fetch_assoc()) {
                                $selected = ($filter_section === $sec['section']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($sec['section']) . "' $selected>" . htmlspecialchars($sec['section']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php 
                        if ($types_result && $types_result->num_rows > 0) {
                            while ($type = $types_result->fetch_assoc()) {
                                $selected = ($filter_type === $type['doc_type']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($type['doc_type']) . "' $selected>" . htmlspecialchars($type['doc_type']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="expiry">
                        <option value="">All Dates</option>
                        <option value="expired" <?php echo ($filter_expiry === 'expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="expiring" <?php echo ($filter_expiry === 'expiring') ? 'selected' : ''; ?>>Expiring Soon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                    <a href="?" class="btn btn-secondary w-100 mt-2"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Documents Table -->
        <div class="table-wrapper">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 12%">Document ID</th>
                        <th style="width: 18%">File Name</th>
                        <th style="width: 10%">Section</th>
                        <th style="width: 12%">Type</th>
                        <th style="width: 12%">Uploaded By</th>
                        <th style="width: 11%">Upload Date</th>
                        <th style="width: 11%">Status</th>
                        <th style="width: 15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $counter = 1;
                        while ($row = $result->fetch_assoc()) {
                            $expiry_date = !empty($row['expiry_date']) ? strtotime($row['expiry_date']) : null;
                            $today = strtotime(date('Y-m-d'));
                            $is_expired = $expiry_date && $expiry_date < $today;
                            $is_expiring_soon = $expiry_date && ($expiry_date > $today && $expiry_date < strtotime('+30 days')) ? true : false;
                            
                            echo "<tr>";
                            echo "<td><strong>" . $counter++ . "</strong></td>";
                            echo "<td><span style='font-family: monospace; font-weight: 600;'>" . htmlspecialchars($row['doc_id']) . "</span></td>";
                            echo "<td>" . htmlspecialchars(substr($row['file_name'], 0, 25)) . (strlen($row['file_name']) > 25 ? '...' : '') . "</td>";
                            
                            // Section badge
                            $section_class = 'section-' . htmlspecialchars($row['section']);
                            echo "<td><span class='section-badge $section_class'>" . htmlspecialchars($row['section']) . "</span></td>";
                            
                            echo "<td>" . htmlspecialchars($row['doc_type'] ?? 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($row['uploaded_by'] ?? 'System') . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($row['upload_date'])) . "</td>";
                            
                            // Status indicator
                            echo "<td>";
                            if ($is_expired) {
                                echo "<span class='status-expired'><i class='bi bi-exclamation-circle'></i> Expired</span>";
                            } elseif ($is_expiring_soon) {
                                echo "<span class='status-expiring'><i class='bi bi-calendar-event'></i> Expiring Soon</span>";
                            } elseif (!$expiry_date) {
                                echo "<span style='color: #6c757d; font-weight: 500;'><i class='bi bi-infinity'></i> No Expiry</span>";
                            } else {
                                echo "<span style='color: #28a745; font-weight: 500;'><i class='bi bi-check-circle'></i> Valid</span>";
                            }
                            echo "</td>";
                            
                            echo "<td>";
                            echo "<div class='action-buttons'>";
                            echo "<a href='" . htmlspecialchars($row['file_path']) . "' target='_blank' class='btn btn-sm btn-info' title='View Document'><i class='bi bi-eye'></i></a>";
                            echo "<a href='../../edit_document.php?id=" . $row['id'] . "' class='btn btn-sm btn-warning' title='Edit Document'><i class='bi bi-pencil'></i></a>";
                            echo "<button class='btn btn-sm btn-danger' data-bs-toggle='modal' data-bs-target='#deleteModal' onclick=\"setDeleteId(" . $row['id'] . ", '" . htmlspecialchars(addslashes($row['file_name'])) . "')\" title='Delete Document'><i class='bi bi-trash'></i></button>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>";
                        echo "<div class='no-documents'>";
                        echo "<i class='bi bi-inbox'></i>";
                        echo "<p class='mt-3'><strong>No documents found</strong></p>";
                        echo "<p class='text-muted'>Try adjusting your filters or check back later.</p>";
                        echo "</div>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to delete:</p>
                <p class="alert alert-light"><strong id="docNameToDelete">-</strong></p>
                <p class="text-danger"><i class="bi bi-exclamation-circle"></i> <strong>This action cannot be undone</strong> and will permanently remove the document from the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="doc_id" id="deleteDocId">
                    <button type="submit" class="btn btn-danger">Delete Document</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setDeleteId(docId, docName) {
    document.getElementById('deleteDocId').value = docId;
    document.getElementById('docNameToDelete').textContent = docName;
}
</script>