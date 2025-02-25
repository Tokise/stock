<?php
session_start();
require_once '../../../config/db.php';
require_once '../../../includes/permissions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
    exit();
}

// Check if user has permission
if (!hasPermission('manage_sales')) {
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage sales']);
    exit();
}

try {
    // Validate required fields
    if (!isset($_POST['sale_id']) || !is_numeric($_POST['sale_id'])) {
        throw new Exception('Invalid sale ID');
    }

    $sale_id = (int)$_POST['sale_id'];

    // Get current order
    $sql = "SELECT * FROM sales_orders WHERE sale_id = ?";
    $order = fetchOne($sql, [$sale_id]);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // For now, we'll just mark the order as archived
    // In a real application, you might move it to an archive table
    $update_data = [
        'archived' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // First, check if the archived column exists
    $check_column = "SELECT COUNT(*) 
                   FROM information_schema.columns 
                   WHERE table_schema = '" . DB_NAME . "' 
                   AND table_name = 'sales_orders' 
                   AND column_name = 'archived'";
    
    $has_archived = fetchValue($check_column) > 0;
    
    if (!$has_archived) {
        // Add archived column if it doesn't exist
        $alter_sql = "ALTER TABLE sales_orders ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0";
        executeQuery($alter_sql);
    }

    update('sales_orders', $update_data, 'sale_id = ?', [$sale_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Order archived successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 