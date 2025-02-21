<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/db.php';

try {
    // Test database connection
    $conn = getDBConnection();
    echo "Database connection successful<br>";

    // Check if tables exist
    $tables = ['customers', 'categories', 'products', 'sales_orders', 'sales_order_items', 'stock_movements'];
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE ?";
        $exists = fetchValue($sql, [$table]);
        echo "$table table: " . ($exists ? "exists" : "missing") . "<br>";
    }

    // Check if test data exists
    $customer = fetchOne("SELECT * FROM customers LIMIT 1");
    echo "Test customer: " . ($customer ? "exists" : "missing") . "<br>";

    $category = fetchOne("SELECT * FROM categories LIMIT 1");
    echo "Test category: " . ($category ? "exists" : "missing") . "<br>";

    $product = fetchOne("SELECT * FROM products LIMIT 1");
    echo "Test product: " . ($product ? "exists" : "missing") . "<br>";

    if ($product) {
        echo "Product details: <pre>" . print_r($product, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} 