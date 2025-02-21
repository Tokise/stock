<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

try {
    // Get unread notifications for user's role
    $sql = "SELECT * FROM notifications 
            WHERE for_role = ? 
            AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10";
    
    $notifications = fetchAll($sql, [$_SESSION['role']]);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
