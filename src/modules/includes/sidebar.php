<?php
// Get current page for active menu highlighting
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', trim($current_path, '/'));
$current_section = isset($path_parts[3]) ? $path_parts[3] : '';
$current_subsection = isset($path_parts[4]) ? $path_parts[4] : '';

// Function to check if a path is active
if (!function_exists('isPathActive')) {
    function isPathActive($section, $subsection = '') {
        global $current_section, $current_subsection;
        if (empty($subsection)) {
            return $current_section === $section;
        }
        return $current_section === $section && $current_subsection === $subsection;
    }
}
?>

<style>
:root {
    --sidebar-width: 250px;
}
.sidebar {
    width: var(--sidebar-width);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    background-color: #f8f9fa;
    padding-top: 20px;
    color: #2c3e50;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 5px rgba(0,0,0,0.2);
}
.main-content {
    margin-left: var(--sidebar-width);
    padding: 20px;
}
.sidebar-link {
    color: #2c3e50;
    text-decoration: none;
    padding: 12px 20px;
    display: block;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}
.sidebar-link:hover, .sidebar-link.active {
    background-color: #e9ecef;
    color: #1a237e;
    text-decoration: none;
    border-left: 3px solid #2ecc71;
}
.sidebar-brand {
    padding: 20px;
    text-align: center;
    margin-bottom: 15px;
    background-color: transparent;
    box-shadow: none;
}
.sidebar-brand img {
    width: 180px;
    height: auto;
    margin-bottom: 0.5rem;
}
.sidebar-user {
    padding: 15px 20px;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: auto;
    background-color: rgba(0,0,0,0.05);
}
.nav-section {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.nav-header {
    padding: 10px 20px;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 500;
}
.sidebar-content {
    flex: 1;
    overflow-y: auto;
    -ms-overflow-style: none;  /* Hide scrollbar for IE and Edge */
    scrollbar-width: none;     /* Hide scrollbar for Firefox */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.sidebar-content::-webkit-scrollbar {
    display: none;
}
.logout-section {
    padding: 15px 20px;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: 15px;
}
.logout-section .btn {
    color: #2c3e50;
    border-color: #2c3e50;
}
.logout-section .btn:hover {
    background-color: #2c3e50;
    color: white;
}
</style>

<div class="sidebar">
    <div class="sidebar-brand">
        <a href="/stock/src/modules/dashboard/index.php">
            <img src="/stock/assets/LOGO.png" alt="NexInvent Logo" class="img-fluid">
        </a>
    </div>
    
    <div class="sidebar-content">
        <div class="nav-section">
            <div class="nav-header">Main Navigation</div>
            <nav>
                <a href="../dashboard/index.php" class="sidebar-link <?php echo isPathActive('dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="../inventory/index.php" class="sidebar-link <?php echo isPathActive('inventory') ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam me-2"></i> Inventory
                </a>
                <a href="../orders/create.php" class="sidebar-link <?php echo isPathActive('orders') ? 'active' : ''; ?>">
                    <i class="bi bi-cart4 me-2"></i> Orders
                </a>    
                <a href="../categories/index.php" class="sidebar-link <?php echo isPathActive('categories') ? 'active' : ''; ?>">
                    <i class="bi bi-tags me-2"></i> Categories
                </a>    
                <a href="../products/index.php" class="sidebar-link <?php echo isPathActive('products') || (isPathActive('products')) ? 'active' : ''; ?>">
                    <i class="bi bi-cart3 me-2"></i> Products
                </a>
                <a href="../sales/index.php" class="sidebar-link <?php echo isPathActive('sales') || (isPathActive('sales')) ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up me-2"></i> Sales
                </a>
                <a href="../purchases/index.php" class="sidebar-link <?php echo isPathActive('purchases') ? 'active' : ''; ?>">
                    <i class="bi bi-bag me-2"></i> Purchases
                </a>
            </nav>
        </div>

        <div class="nav-section">
            <div class="nav-header">Management</div>
            <nav>
                <a href="../suppliers/index.php" class="sidebar-link <?php echo isPathActive('suppliers') ? 'active' : ''; ?>">
                    <i class="bi bi-truck me-2"></i> Suppliers
                </a>
                <a href="../employees/index.php" class="sidebar-link <?php echo isPathActive('employees') ? 'active' : ''; ?>">
                    <i class="bi bi-people me-2"></i> Employees
                </a>
                <a href="../payroll/index.php" class="sidebar-link <?php echo isPathActive('payroll') ? 'active' : ''; ?>">
                    <i class="bi bi-cash-stack me-2"></i> Payroll
                </a>
            </nav>
        </div>

        <div class="nav-section">
            <div class="nav-header">Reports & Settings</div>
            <nav>
                <a href="../reports/index.php" class="sidebar-link <?php echo isPathActive('reports') ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text me-2"></i> Reports
                </a>
                <a href="../settings/settings.php" class="sidebar-link <?php echo isPathActive('settings') ? 'active' : ''; ?>">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </nav>
        </div>
    </div>

    <div class="logout-section">
        <a href="../../login/logout.php" class="btn btn-outline-dark w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div> 