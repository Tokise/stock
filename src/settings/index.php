<?php
session_start();
require_once '../modules/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}

// Fetch user settings
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM settings WHERE user_id = ?";
$settings = fetchOne($sql, [$user_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    $profile_pic = $_FILES['profile_pic']['name'] ?? '';

    // Handle profile picture upload
    if ($profile_pic) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($profile_pic);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file);
    } else {
        $target_file = $settings['profile_pic'];
    }

    // Update settings in the database
    $data = [
        'dark_mode' => $dark_mode,
        'profile_pic' => $target_file
    ];
    update('settings', $data, 'user_id = ?', [$user_id]);

    // Refresh settings
    $settings = fetchOne($sql, [$user_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Settings</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: <?php echo $settings['dark_mode'] ? '#343a40' : '#f8f9fa'; ?>;
            color: <?php echo $settings['dark_mode'] ? '#f8f9fa' : '#343a40'; ?>;
        }
        .card {
            background-color: <?php echo $settings['dark_mode'] ? '#495057' : '#fff'; ?>;
        }
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background-color: #2c3e50;
            padding-top: 20px;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar-link:hover {
            background-color: #34495e;
            color: #ecf0f1;
        }
        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="px-3 mb-4 text-center">
        <h4>NexInvent</h4>
        <img src="<?php echo htmlspecialchars($settings['profile_pic'] ?? 'default.png'); ?>" alt="Profile Picture" class="profile-pic">
        <p><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>
    </div>
    <nav>
        <a href="../modules/index.php" class="sidebar-link">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="../inventory/index.php" class="sidebar-link">
            <i class="bi bi-box-seam me-2"></i> Inventory
        </a>
        <a href="../products/index.php" class="sidebar-link">
            <i class="bi bi-cart3 me-2"></i> Products
        </a>
        <a href="../sales/index.php" class="sidebar-link">
            <i class="bi bi-graph-up me-2"></i> Sales
        </a>
        <a href="../purchases/index.php" class="sidebar-link">
            <i class="bi bi-bag me-2"></i> Purchases
        </a>
        <a href="../suppliers/index.php" class="sidebar-link">
            <i class="bi bi-truck me-2"></i> Suppliers
        </a>
        <a href="../employees/index.php" class="sidebar-link">
            <i class="bi bi-people me-2"></i> Employees
        </a>
        <a href="../payroll/index.php" class="sidebar-link">
            <i class="bi bi-cash-stack me-2"></i> Payroll
        </a>
        <a href="../reports/index.php" class="sidebar-link">
            <i class="bi bi-file-earmark-text me-2"></i> Reports
        </a>
        <a href="index.php" class="sidebar-link active">
            <i class="bi bi-gear me-2"></i> Settings
        </a>
        <a href="../login/logout.php" class="sidebar-link">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </nav>
</div>

<div class="main-content">
    <div class="container-fluid">
        <h2>Settings</h2>
        <div class="card mt-4">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="dark_mode" class="form-label">Dark Mode</label>
                        <input type="checkbox" id="dark_mode" name="dark_mode" <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="profile_pic" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_pic" name="profile_pic">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
