<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

function createNotification($type, $title, $message, $referenceId, $forRole) {
    try {
        $data = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'for_role' => $forRole,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        return insert('notifications', $data);
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

function createOrderNotification($orderId, $customerName, $total) {
    try {
        $title = "New Order #" . $orderId;
        $message = "New order from {$customerName} - Total: $" . number_format($total, 2);
        
        // Create notification for admin & manager
        createNotification('order', $title, $message, $orderId, 'admin');
        createNotification('order', $title, $message, $orderId, 'manager');
        
        // Update dashboard stats
        updateDashboardStats($orderId, $total);
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating order notification: " . $e->getMessage());
        return false;
    }
}

function updateDashboardStats($orderId, $total) {
    try {
        $today = date('Y-m-d');
        
        // Check if stats exist for today
        $stats = fetchOne(
            "SELECT * FROM dashboard_stats WHERE stat_date = ?", 
            [$today]
        );
        
        if ($stats) {
            execute(
                "UPDATE dashboard_stats 
                 SET total_sales = total_sales + 1,
                     monthly_revenue = monthly_revenue + ?
                 WHERE stat_date = ?",
                [$total, $today]
            );
        } else {
            insert('dashboard_stats', [
                'stat_date' => $today,
                'total_sales' => 1,
                'monthly_revenue' => $total
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating dashboard stats: " . $e->getMessage());
        return false;
    }
}

function getNotifications($role, $limit = 10) {
    return fetchAll(
        "SELECT * FROM notifications 
         WHERE for_role = ? 
         ORDER BY created_at DESC 
         LIMIT ?",
        [$role, $limit]
    );
}

function markNotificationAsRead($notificationId) {
    return execute(
        "UPDATE notifications SET is_read = 1 WHERE notification_id = ?",
        [$notificationId]
    );
}

function getUnreadCount($role) {
    return fetchOne(
        "SELECT COUNT(*) as count FROM notifications 
         WHERE for_role = ? AND is_read = 0",
        [$role]
    )['count'];
}
