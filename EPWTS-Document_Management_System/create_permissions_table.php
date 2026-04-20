<?php
include 'config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_type ENUM('view', 'edit', 'delete', 'approve') NOT NULL,
    granted_by VARCHAR(50) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (user_id, permission_type)
)";

if ($conn->query($sql) === TRUE) {
    echo 'Permissions table created successfully';
} else {
    echo 'Error creating table: ' . $conn->error;
}

$conn->close();
?>