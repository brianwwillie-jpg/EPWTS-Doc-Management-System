<?php
// 1. Start the session first
session_start();

// 2. Add security headers (optional but recommended)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// 4. Now include the header and database connection
include '../includes/header.php';
include '../config/db.php';
include '../includes/functions.php'; // ID generation Code



// 3. Add the Security Check for the user role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Doc_Controller') {
    // If the user is not logged in or does not have the Document_Controller role, redirect to login page
    header("Location: ../index.php");
    exit();
}

?>

<div class="container mt-5">
    <h3>Add New Document</h3>
    <form action="upload_logic.php" method="POST" enctype="multipart/form-data">
        <label>Select Department:</label><br>
        <div class="form-check form-check-inline">
            <input type="radio" name="section" value="AB" required> Architectural/Building
        </div>
        <div class="form-check form-check-inline">
            <input type="radio" name="section" value="CW"> Civil Works
        </div>
        <div class="form-check form-check-inline">
            <input type="radio" name="section" value="AD"> Administration
        </div>
        <div class="form-check form-check-inline">
            <input type="radio" name="section" value="EP"> Energy Projects
        </div>
        <div class="form-check form-check-inline">
            <input type="radio" name="section" value="DO"> Director's Office
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
            <input type="file" name="doc_file" class="form-control mt-2" required>
            <button type="submit" class="btn btn-primary mt-3">Upload Document</button>
        </div>
    </form>
</div>
<!-- DIsplay in table the uploaded materials and documents -->
<div class="container mt-5">
    <h3>Uploaded Documents</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Document ID</th>
                <th>File Name</th>
                <th>Section</th>
                <th>Upload Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM documents";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['doc_id'] . "</td>";
                    echo "<td>" . $row['file_name'] . "</td>";
                    echo "<td>" . $row['section'] . "</td>";
                    echo "<td>" . $row['upload_date'] . "</td>";
                    echo "<td><a href='" . $row['file_path'] . "' target='_blank' class='btn btn-sm btn-info'>View</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No documents uploaded yet.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
