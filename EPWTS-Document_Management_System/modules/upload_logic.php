<?php
include '../includes/header.php';
include '../config/db.php';
include '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prefix = $_POST['section'];
    $file_no = $_POST['file_no'];
    $doc_id = generateDocID($prefix, $file_no); // Uses function from Step 2
    
    $file_name = $_FILES['doc_file']['name'];
    $target = "../uploads/" . basename($file_name);

    // Check if doc_id already exists
    $check_sql = "SELECT doc_id FROM documents WHERE doc_id = '$doc_id'";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        echo "Error: Document ID '$doc_id' already exists. Please use a different file number.";
    } elseif (move_uploaded_file($_FILES['doc_file']['tmp_name'], $target)) {
        $sql = "INSERT INTO documents (doc_id, file_name, file_path, section) 
                VALUES ('$doc_id', '$file_name', '$target', '$prefix')";
        if ($conn->query($sql)) {
            echo "Success! Document ID: " . $doc_id;
        } else {
            echo "Error inserting document: " . $conn->error;
        }
    } else {
        echo "Error uploading file.";
    }
}
?>
<div class="container mt-5">
    <a href="doc_controller.php" class="btn btn-secondary">Back to Document Controller</a>
</div>

