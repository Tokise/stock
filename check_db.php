<?php
require_once 'StockManagementProject/src/modules/config/db.php';

try {
    // Use the existing connection from db.php
    $conn = getDBConnection();
    
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Database Tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  * {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 