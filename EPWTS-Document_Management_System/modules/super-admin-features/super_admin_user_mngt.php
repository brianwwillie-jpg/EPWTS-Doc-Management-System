<?php

// include '../../includes/header.php';
include '../../config/db.php';
session_start();

// Security Check: Only Super Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super_Admin') {
    die("Access Denied. Super Admin privileges required.");
    exit();
}

// Logic to create a new user
if (isset($_POST['create_user'])) {
    $name = $_POST['name'];
    $user = $_POST['username'];
    // Securely hash the password
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    // Check if username already exists
    $check_sql = "SELECT username FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<div class='alert alert-danger'>Error: Username '$user' already exists. Please choose a different username.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $user, $pass, $role);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>User $user created successfully for $role module.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error creating user: " . $stmt->error . "</div>";
        }
    }
}
?>
<?php  ?>
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
<div class="container mt-5">
    <h2>Super Admin - User Management</h2>
    <div class="card p-4">
        <form method="POST">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
                <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Assign to Module (Role)</label>
                <select name="role" class="form-control">
                    <option value="Arch_Building">Architectural and Building Works</option>
                    <option value="Civil_Works">Civil Works</option>
                    <option value="Director">Director's Office</option>
                    <option value="Admin">Administration</option>
                    <option value="Energy">Energy Projects</option>
                    <option value="Doc_Controller">Document Controller</option>
                    <option value="Super_Admin">Super Admin</option>
                </select>
            </div>
            <button type="submit" name="create_user" class="btn btn-primary">Create System User</button>
        </form>
    </div>
</div>
<!-- Display existing Users -->
<div class="container mt-5">
    <h3>Existing System Users</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Password</th>
                <th>Role (Module)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT id, name, username, password, role FROM users";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . "**************" . "</td>";
                    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                    echo "<td>";
                    echo "<a href='edit_user.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'>Edit</a> ";
                    echo "<a href='delete_user.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>

