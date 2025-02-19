<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "src/modules/config/db.php";

try {
    $conn = getDBConnection();
    $user = fetchOne("SELECT * FROM users WHERE username = ?", ["admin"]);
    if ($user) {
        echo "Admin user found:\n";
        echo "User ID: " . $user["user_id"] . "\n";
        echo "Username: " . $user["username"] . "\n";
        echo "Status: " . $user["status"] . "\n";
        echo "Password Hash: " . $user["password"] . "\n";
        
        // Test password verification
        $test_pass = "admin123";
        $verify = password_verify($test_pass, $user["password"]);
        echo "\nPassword verification test:\n";
        echo "Test password: " . $test_pass . "\n";
        echo "Verification result: " . ($verify ? "VALID" : "INVALID") . "\n";
    } else {
        echo "No admin user found in database.\n";
    }

    // Check tables
    $tables = ['users', 'permissions', 'role_permissions'];
    foreach ($tables as $table) {
        $count = fetchValue("SELECT COUNT(*) FROM $table");
        echo "<br>Number of records in $table: $count<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 