<?php
require_once '../notifications/create_notification.php';

// ... existing order processing code ...

if ($order_id) {
    // Create notification for new order
    createOrderNotification(
        $order_id, 
        $_SESSION['full_name'],
        $orderData['grand_total']
    );
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order created successfully'
    ]);
}

// ... rest of existing code ...
