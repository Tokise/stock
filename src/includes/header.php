<?php
// Get the current page title
function getPageTitle() {
    $current_page = basename(dirname($_SERVER['PHP_SELF']));
    $titles = [
        'modules' => 'Dashboard',
        'inventory' => 'Inventory Management',
        'products' => 'Products',
        'sales' => 'Sales Management',
        'purchases' => 'Purchase Orders',
        'suppliers' => 'Suppliers',
        'employees' => 'Employee Management',
        'payroll' => 'Payroll',
        'reports' => 'Reports & Analytics',
        'settings' => 'System Settings'
    ];
    
    return 'NexInvent - ' . ($titles[$current_page] ?? 'Stock Management System');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle(); ?></title>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- ApexCharts -->
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/stock/assets/css/styles.css" rel="stylesheet">

    <!-- DataTables with Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Additional Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
<?php include 'sidebar.php'; ?>