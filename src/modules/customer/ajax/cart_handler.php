<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $response['error'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

try {
    $action = $_REQUEST['action'] ?? ''; // Changed from $_POST to $_REQUEST to handle both POST and GET
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    switch ($action) {
        case 'add':
            if ($product_id > 0) {
                $user_id = $_SESSION['user_id'];
                $cart_item = fetchOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);

                if ($cart_item) {
                    // Update quantity if item already in cart
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    execute("UPDATE cart SET quantity = ? WHERE cart_id = ?", [$new_quantity, $cart_item['cart_id']]);
                } else {
                    // Insert new item into cart
                    $cart_data = [
                        'user_id' => $user_id,
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    insert('cart', $cart_data);
                }

                // Get updated cart count
                $cart_count = fetchOne("SELECT COUNT(*) as count FROM cart WHERE user_id = ?", [$user_id])['count'];

                $response['success'] = true;
                $response['cart_count'] = $cart_count;
                $response['message'] = 'Item added to cart successfully';
            } else {
                $response['error'] = 'Invalid action or product ID';
            }
            break;

        case 'update':
            $product_id = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            // Verify stock
            $product = fetchOne("SELECT * FROM products WHERE product_id = ?", [$product_id]);
            if (!$product || $product['quantity_in_stock'] < $quantity) {
                throw new Exception('Insufficient stock');
            }

            // Update quantity
            $sql = "UPDATE shopping_cart SET quantity = ? 
                   WHERE user_id = ? AND product_id = ?";
            
            execute($sql, [$quantity, $_SESSION['user_id'], $product_id]);
            break;

        case 'remove':
            $product_id = $_POST['product_id'] ?? null;
            
            $sql = "DELETE FROM shopping_cart 
                   WHERE user_id = ? AND product_id = ?";
            
            execute($sql, [$_SESSION['user_id'], $product_id]);
            break;

        case 'get':
            $sql = "SELECT sc.*, p.name, p.sku, p.unit_price, p.quantity_in_stock,
                          (sc.quantity * p.unit_price) as subtotal
                   FROM shopping_cart sc
                   JOIN products p ON sc.product_id = p.product_id
                   WHERE sc.user_id = ?";
            
            $cart_items = fetchAll($sql, [$_SESSION['user_id']]);
            
            $total = 0;
            foreach ($cart_items as &$item) {
                $total += $item['subtotal'];
            }
            
            echo json_encode([
                'success' => true,
                'items' => $cart_items,
                'total' => $total,
                'count' => count($cart_items)
            ]);
            exit;

        default:
            throw new Exception('Invalid action');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
