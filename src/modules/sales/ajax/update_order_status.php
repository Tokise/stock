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
if (!hasPermission('manage_sales') && !hasPermission('process_payments')) {
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage sales']);
    exit();
}

try {
    // Validate required fields
    if (!isset($_POST['sale_id']) || !is_numeric($_POST['sale_id'])) {
        throw new Exception('Invalid sale ID');
    }

    if (!isset($_POST['status']) || empty($_POST['status'])) {
        throw new Exception('Status is required');
    }

    $sale_id = (int)$_POST['sale_id'];
    $status = $_POST['status'];

    // Validate status
    $valid_statuses = ['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    // Get current order
    $sql = "SELECT * FROM sales_orders WHERE sale_id = ?";
    $order = fetchOne($sql, [$sale_id]);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // If changing to completed, check if payment is complete
    if ($status === 'completed' && ($order['payment_status'] !== 'paid')) {
        throw new Exception('Cannot mark as completed: Order is not fully paid');
    }

    // Update order status
    $update_data = [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // If status is completed and payment is not marked as paid, update payment status
    if ($status === 'completed' && $order['payment_status'] !== 'paid') {
        $update_data['payment_status'] = 'paid';
        $update_data['amount_paid'] = $order['grand_total'];
        $update_data['payment_date'] = date('Y-m-d H:i:s');
        $update_data['payment_method'] = $order['payment_method'] ?? 'cash';
    }

    update('sales_orders', $update_data, 'sale_id = ?', [$sale_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 