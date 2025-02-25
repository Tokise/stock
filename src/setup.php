<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

try {
    // Check database connection
    $conn = getDBConnection();
    echo "Database connection successful!<br><br>";

    // Create test passwords with consistent hashing
    $admin_password = 'admin123';
    $customer_password = 'customer123';
    $hash_admin = password_hash($admin_password, PASSWORD_DEFAULT);
    $hash_customer = password_hash($customer_password, PASSWORD_DEFAULT);

    // Reset admin password if exists, create if not
    $sql = "SELECT user_id FROM users WHERE username = 'admin'";
    $admin = fetchOne($sql);

    if ($admin) {
        // Update existing admin password
        $sql = "UPDATE users SET password = ? WHERE username = 'admin'";
        execute($sql, [$hash_admin]);
        echo "Admin password reset successfully!<br>";
    } else {
        // Create new admin user
        $adminData = [
            'username' => 'admin',
            'password' => $hash_admin,
            'email' => 'admin@nexinvent.local',
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $admin_id = insert('users', $adminData);
        echo "Admin user created successfully!<br>";
    }

    // Reset customer password if exists, create if not
    $sql = "SELECT user_id FROM users WHERE username = 'customer'";
    $customer = fetchOne($sql);

    if ($customer) {
        // Update existing customer password
        $sql = "UPDATE users SET password = ? WHERE username = 'customer'";
        execute($sql, [$hash_customer]);
        echo "Customer password reset successfully!<br>";
    } else {
        // Create new customer user
        $customerData = [
            'username' => 'customer',
            'password' => $hash_customer,
            'email' => 'customer@example.com',
            'full_name' => 'Test Customer',
            'role' => 'customer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $customer_id = insert('users', $customerData);
        echo "Customer user created successfully!<br>";
    }

    echo "<br>You can now login with these credentials:<br><br>";
    echo "<strong>Admin Account:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br><br>";
    echo "<strong>Customer Account:</strong><br>";
    echo "Username: customer<br>";
    echo "Password: customer123<br><br>";
    echo "<a href='login/index.php' class='btn btn-primary'>Go to Login</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-body">
            <h1 class="card-title">NexInvent Setup</h1>
            <hr>
            <!-- PHP output will appear here -->
        </div>
    </div>
</body>
</html>