<?php
function createNotification($type, $title, $message, $referenceId, $forRole) {
    $data = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'reference_id' => $referenceId,
        'for_role' => $forRole,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('notifications', $data);
}

function createOrderNotification($orderId, $customerName, $total) {
    $title = "New Order #" . $orderId;
    $message = "New order from {$customerName} - Total: $" . number_format($total, 2);
    
    // Create notification for admin & manager
    createNotification('order', $title, $message, $orderId, 'admin');
    createNotification('order', $title, $message, $orderId, 'manager');
    
    // Update dashboard statistics
    updateDashboardStats($orderId);
}

function updateDashboardStats($orderId) {
    // Get order details
    $order = fetchOne("SELECT * FROM orders WHERE order_id = ?", [$orderId]);
    
    if ($order) {
        // Update sales count
        $sql = "UPDATE dashboard_stats SET 
                total_sales = total_sales + 1,
                monthly_revenue = monthly_revenue + ?
                WHERE MONTH(stat_date) = MONTH(CURRENT_DATE)";
        
        executeQuery($sql, [$order['total']]);
    }
}
