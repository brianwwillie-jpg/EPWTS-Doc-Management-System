<?php


include "config/db.php";
session_start();


if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$user'");
    if ($row = $result->fetch_assoc()) { 
        if (password_verify($pass, $row['password'])) {
            session_start();
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on Role
            switch($row['role']) {
                case 'Super_Admin': header("Location: modules/super_admin.php"); break;
                case 'Director': header("Location: modules/director.php"); break;
                case 'Doc_Controller': header("Location: modules/doc_controller.php"); break;
                case 'Arch_Building': header("Location: modules/arch_building.php"); break;
                case 'Civil_Works': header("Location: modules/civil_works.php"); break;
                case 'Energy': header("Location: modules/energy_projects.php"); break;
                // Add cases for other modules...
                default: header("Location: dashboard.php");
            }
        } else { echo "Invalid Password."; }
    } else { echo "User not found."; }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPWTS DMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient-primary">
    <!-- Hero Section -->
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
        <div class="row text-center">
            <div class="col-12">
                <!-- Company Logo -->
                <div class="mb-4">
                    <img src="assets/images/logo/ptws.jpg" alt="EPWTS Logo" class="img-fluid" style="max-width: 200px; height: auto; border-radius: 50%; background: #ffffff; padding: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.3);" onerror="this.style.display='none';">
                    <!-- Fallback if logo not found -->
                    <div class="display-4 text-white font-weight-bold" id="logo-fallback" style="display: none;">EPWTS</div>
                </div>
                <!-- Company Description -->
                <h1><b>Enga Provincial Works and Technical Services</b></h1>
                <h3><b>Document Management System - DMS</b></h3>
                <p>EPWTS Document Management System (DMS) is a centralized platform designed to streamline the organization, storage, and retrieval of project-related documents. With robust security features and user-friendly interfaces, our DMS ensures that all stakeholders can efficiently manage their documents while maintaining strict access controls. Whether it's contracts, drawings, or reports, EPWTS DMS provides a secure and efficient solution for handling all your project documentation needs.</p>
                <!-- Login Button -->
                <button type="button" class="btn btn-light btn-lg px-5 py-3" data-bs-toggle="modal" data-bs-target="#loginModal" style="font-weight: 600; border-radius: 8px;">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Access System
                </button>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header" style="background-color: #004566; border-bottom: 3px solid #3c6e71;">
                    <h5 class="modal-title" id="loginModalLabel"><i class="fas fa-lock me-2"></i>EPWTS DMS Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label" style="color: #353535; font-weight: 600;">Username</label>
                            <input type="text" name="username" class="form-control" id="username" placeholder="Enter your username" required style="border: 2px solid #d9d9d9; border-radius: 6px;">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label" style="color: #353535; font-weight: 600;">Password</label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required style="border: 2px solid #d9d9d9; border-radius: 6px;">
                        </div>
                        <div class="d-grid">
                            <button name="login" class="btn btn-primary btn-lg" style="background-color: #3c6e71; border: none; font-weight: 600; border-radius: 8px;">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show fallback logo if image fails to load
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.querySelector('img[alt="EPWTS Logo"]');
            const fallback = document.getElementById('logo-fallback');
            if (logoImg && logoImg.style.display === 'none') {
                fallback.style.display = 'block';
            }
        });
    </script>
</body>
</html>