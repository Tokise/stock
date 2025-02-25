<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in and has admin permissions
if (!isset($_SESSION['user_id']) || !hasPermission('manage_system')) {
    header("Location: ../../login/index.php");
    exit();
}

try {
    $conn = getDBConnection();
    $updates_made = false;
    $update_messages = [];
    
    // Check if payment_status column exists in sales_orders table
    $sql = "SELECT COUNT(*) 
           FROM information_schema.columns 
           WHERE table_schema = '" . DB_NAME . "' 
           AND table_name = 'sales_orders' 
           AND column_name = 'payment_status'";
    
    $has_payment_status = fetchValue($sql) > 0;
    
    if (!$has_payment_status) {
        // Add payment-related columns to sales_orders table
        $alter_sql = "ALTER TABLE sales_orders 
                     ADD COLUMN payment_status ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid' AFTER status,
                     ADD COLUMN payment_method ENUM('cash', 'e_wallet') DEFAULT NULL AFTER payment_status,
                     ADD COLUMN amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER payment_method,
                     ADD COLUMN payment_date TIMESTAMP NULL DEFAULT NULL AFTER amount_paid";
        
        executeQuery($alter_sql);
        $updates_made = true;
        $update_messages[] = "Payment fields added to sales_orders table.";
    } else {
        $update_messages[] = "Payment fields already exist in sales_orders table.";
    }
    
    // Check if status column includes 'completed'
    $sql = "SELECT COLUMN_TYPE 
           FROM information_schema.columns 
           WHERE table_schema = '" . DB_NAME . "' 
           AND table_name = 'sales_orders' 
           AND column_name = 'status'";
    
    $status_type = fetchValue($sql);
    
    if (strpos($status_type, 'completed') === false) {
        // Update status column to include 'completed'
        $alter_sql = "ALTER TABLE sales_orders
                     MODIFY COLUMN status ENUM('draft', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'";
        
        executeQuery($alter_sql);
        $updates_made = true;
        $update_messages[] = "Status column updated to include 'completed' status.";
    } else {
        $update_messages[] = "Status column already includes 'completed' status.";
    }
    
    // Check if archived column exists
    $sql = "SELECT COUNT(*) 
           FROM information_schema.columns 
           WHERE table_schema = '" . DB_NAME . "' 
           AND table_name = 'sales_orders' 
           AND column_name = 'archived'";
    
    $has_archived = fetchValue($sql) > 0;
    
    if (!$has_archived) {
        // Add archived column
        $alter_sql = "ALTER TABLE sales_orders
                     ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0";
        
        executeQuery($alter_sql);
        $updates_made = true;
        $update_messages[] = "Archived column added to sales_orders table.";
    } else {
        $update_messages[] = "Archived column already exists in sales_orders table.";
    }
    
    // Check if payment_status index exists
    $sql = "SELECT COUNT(*) 
           FROM information_schema.statistics 
           WHERE table_schema = '" . DB_NAME . "' 
           AND table_name = 'sales_orders' 
           AND index_name = 'idx_payment_status'";
    
    $has_payment_index = fetchValue($sql) > 0;
    
    if (!$has_payment_index) {
        // Add index for payment_status
        $alter_sql = "ALTER TABLE sales_orders
                     ADD INDEX idx_payment_status (payment_status)";
        
        executeQuery($alter_sql);
        $updates_made = true;
        $update_messages[] = "Index added for payment_status column.";
    } else {
        $update_messages[] = "Index for payment_status already exists.";
    }
    
    // Check if archived index exists
    $sql = "SELECT COUNT(*) 
           FROM information_schema.statistics 
           WHERE table_schema = '" . DB_NAME . "' 
           AND table_name = 'sales_orders' 
           AND index_name = 'idx_archived'";
    
    $has_archived_index = fetchValue($sql) > 0;
    
    if (!$has_archived_index) {
        // Add index for archived
        $alter_sql = "ALTER TABLE sales_orders
                     ADD INDEX idx_archived (archived)";
        
        executeQuery($alter_sql);
        $updates_made = true;
        $update_messages[] = "Index added for archived column.";
    } else {
        $update_messages[] = "Index for archived already exists.";
    }
    
    // Set status message
    if ($updates_made) {
        $status_class = "success";
        $status_message = "Database updated successfully!";
    } else {
        $status_class = "info";
        $status_message = "Database is already up to date.";
    }
    
} catch (Exception $e) {
    $status_class = "danger";
    $status_message = "Error updating database: " . $e->getMessage();
    $update_messages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Database Check</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h4>Database Check - Sales Orders Table</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-<?php echo $status_class; ?>" role="alert">
                                <h5 class="alert-heading"><?php echo $status_message; ?></h5>
                                <?php if (!empty($update_messages)): ?>
                                    <hr>
                                    <ul class="mb-0">
                                        <?php foreach ($update_messages as $message): ?>
                                            <li><?php echo $message; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Database Structure</h5>
                                <p>The sales_orders table should have the following structure:</p>
                                <ul>
                                    <li><strong>payment_status</strong>: ENUM('unpaid', 'partially_paid', 'paid')</li>
                                    <li><strong>payment_method</strong>: ENUM('cash', 'e_wallet')</li>
                                    <li><strong>amount_paid</strong>: DECIMAL(10,2)</li>
                                    <li><strong>payment_date</strong>: TIMESTAMP</li>
                                    <li><strong>archived</strong>: TINYINT(1)</li>
                                    <li><strong>status</strong>: Includes 'completed' option</li>
                                </ul>
                                
                                <p>The table should also have indexes for:</p>
                                <ul>
                                    <li><strong>idx_payment_status</strong>: For faster queries on payment status</li>
                                    <li><strong>idx_archived</strong>: For faster queries on archived status</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="../sales/index.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-1"></i> Return to Sales
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 