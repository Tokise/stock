<?php
require_once 'config/db.php';

try {
    $conn = getDBConnection();
    
    // Read the SQL file
    $sql = file_get_contents('fix_inventory.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', 
            explode(';', $sql)
        )
    );
    
    // Execute each statement separately
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo "Executed: " . substr($statement, 0, 100) . "...<br>";
            } catch (PDOException $e) {
                echo "Warning on statement: " . substr($statement, 0, 100) . "...<br>";
                echo "Error: " . $e->getMessage() . "<br><br>";
                // Continue with next statement
                continue;
            }
        }
    }
    
    echo "<br>Database update completed!<br>";
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='inventory/index.php' class='btn btn-primary'>Go to Inventory</a><br><br>";
    echo "The following changes were made:<br>";
    echo "1. Added 'updated_by' column to products table<br>";
    echo "2. Added 'updated_at' column to products table<br>";
    echo "3. Added 'created_by' column to products table<br>";
    echo "4. Updated stock_movements table structure<br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='color: red; margin: 20px;'>";
    echo "Critical Error: " . $e->getMessage() . "<br>";
    echo "Please contact system administrator.";
    echo "</div>";
}
?> 

<!DOCTYPE html>
<html>
<head>
    <title>Fix Inventory Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            margin: 10px 0;
        }
    </style>
</head>
<body>
</body>
</html> 