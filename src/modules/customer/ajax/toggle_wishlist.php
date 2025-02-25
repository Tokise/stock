<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db.php';
require_once '../../../includes/permissions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

try {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];

    // Check if product exists
    $product = fetchOne("SELECT product_id FROM products WHERE product_id = ?", [$product_id]);
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Check if product is already in wishlist
    $wishlist_item = fetchOne(
        "SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id]
    );

    if ($wishlist_item) {
        // Remove from wishlist
        executeQuery(
            "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        $message = 'Product removed from wishlist';
    } else {
        // Add to wishlist
        executeQuery(
            "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)",
            [$user_id, $product_id]
        );
        $message = 'Product added to wishlist';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 