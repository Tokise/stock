<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permissions.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    // If user is logged in but not a customer, log them out first
    if (isset($_SESSION['user_id'])) {
        // Clear all session data
        $_SESSION = array();
        session_destroy();
    }
    header("Location: ../../../login/index.php");
    exit();
}

// Get customer profile and details
try {
    // Get customer details
    $customer = fetchOne(
        "SELECT c.* FROM customers c 
         INNER JOIN users u ON u.email = c.email 
         WHERE u.user_id = ?", 
        [$_SESSION['user_id']]
    );

    if (!$customer) {
        // Create customer record if it doesn't exist
        $user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
        $customer_data = [
            'name' => $user['full_name'],
            'email' => $user['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $customer_id = insert('customers', $customer_data);
        $customer = fetchOne("SELECT * FROM customers WHERE customer_id = ?", [$customer_id]);
    }

    $_SESSION['customer_id'] = $customer['customer_id'];

    // Get or create customer profile
    $customer_profile = fetchOne(
        "SELECT * FROM customer_profiles WHERE user_id = ?", 
        [$_SESSION['user_id']]
    );

    if (!$customer_profile) {
        $profile_data = [
            'user_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        insert('customer_profiles', $profile_data);
        $customer_profile = fetchOne(
            "SELECT * FROM customer_profiles WHERE user_id = ?", 
            [$_SESSION['user_id']]
        );
    }
} catch (Exception $e) {
    error_log("Error in customer header: " . $e->getMessage());
    // Clear session and redirect to login if there's an error
    $_SESSION = array();
    session_destroy();
    header("Location: ../../../login/index.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Customer Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }
        
        body {
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        
        .customer-navbar {
            background-color: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .customer-navbar .navbar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .customer-navbar .navbar-brand img {
            height: 40px;
            width: auto;
            margin-right: 0.5rem;
        }
        
        .customer-navbar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.5rem 1rem;
            transition: color 0.3s;
        }
        
        .customer-navbar .nav-link:hover {
            color: white;
        }
        
        .customer-navbar .nav-link.active {
            color: white;
            font-weight: bold;
        }
        
        .main-content {
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }
        
        .card {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem 1.25rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fc;
        }
        
        .cart-icon {
            position: relative;
            font-size: 1.2rem;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg customer-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="../../../assets/LOGO.png" alt="NexInvent Logo">
              
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'catalog.php' ? 'active' : ''; ?>" href="catalog.php">
                            <i class="bi bi-grid"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <i class="bi bi-bag"></i> My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'wishlist.php' ? 'active' : ''; ?>" href="wishlist.php">
                            <i class="bi bi-heart"></i> Wishlist
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="bi bi-cart3"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge d-none">
                                0
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="../../login/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>