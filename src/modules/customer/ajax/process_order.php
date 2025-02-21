<?php
require_once '../../../config/db.php';
require_once '../../includes/functions.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get cart items
    $cart_items = fetchAll(
        "SELECT c.*, p.unit_price, p.quantity_in_stock 
         FROM cart c 
         JOIN products p ON c.product_id = p.product_id 
         WHERE c.user_id = ?",
        [$_SESSION['user_id']]
    );

    if (empty($cart_items)) {
        throw new Exception('Cart is empty');
    }

    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        // Verify stock
        if ($item['quantity'] > $item['quantity_in_stock']) {
            throw new Exception("Insufficient stock for some items");
        }
        $total += $item['quantity'] * $item['unit_price'];
    }

    // Create order
    $order_id = insert('orders', [
        'user_id' => $_SESSION['user_id'],
        'order_date' => date('Y-m-d H:i:s'),
        'status' => 'pending',
        'shipping_address' => $_POST['shipping_address'],
        'notes' => $_POST['notes'] ?? '',
        'total_amount' => $total
    ]);

    // Create order items and update stock
    foreach ($cart_items as $item) {
        // Add order item
        insert('order_items', [
            'order_id' => $order_id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price']
        ]);

        // Update stock
        execute(
            "UPDATE products 
             SET quantity_in_stock = quantity_in_stock - ? 
             WHERE product_id = ?",
            [$item['quantity'], $item['product_id']]
        );
    }

    // Clear cart
    execute("DELETE FROM cart WHERE user_id = ?", [$_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
