
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPWTS DMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #d9d9d9;">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #004566 !important; border-bottom: 4px solid #3c6e71;">
        <div class="container-fluid">
            <!-- Left: User Welcome Message -->
            <div class="navbar-text text-white d-flex align-items-center" style="font-weight: 600;">
                <?php if (isset($_SESSION['name'])): ?>
                    <i class="fas fa-user-circle" style="font-size: 1.5rem; margin-right: 0.5rem; color: #ffffff;"></i>
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <?php endif; ?>
            </div>

            <div class ="logo">
                <a href ="../assets/images/logo/ptws.jpg></a>
                </div>
            <div class="navbar-brand mx-auto text-center">
                <h4 class="mb-0" style="color: #ffffff; font-weight: 700; letter-spacing: 0.5px;">
                    Enga Provincial Works and Technical Services
                </h4>
                <small class="text-white" style="font-weight: 500; opacity: 0.9;">Document Management System (DMS)</small>
            </div>

            <!-- Right: Logout Button (Only when logged in) -->
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['name'])): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm" href="../logout.php" style="border-radius: 6px; font-weight: 600; border: 2px solid #ffffff; padding: 0.5rem 1rem;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm" href="../logout.php" style="border-radius: 6px; font-weight: 600; border: 2px solid #ffffff; padding: 0.5rem 1rem;">
                            <i class="fas fa-sign-in-alt"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>
            </div>
        </div>
    </nav>
