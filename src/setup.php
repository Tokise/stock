<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'modules/config/db.php';

try {
    // Check database connection
    $conn = getDBConnection();
    echo "Database connection successful!<br><br>";

    // Check if admin user exists
    $sql = "SELECT COUNT(*) FROM users WHERE username = 'admin'";
    $adminExists = fetchValue($sql) > 0;

    if (!$adminExists) {
        // Create default admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $adminData = [
            'username' => 'admin',
            'password' => $password,
            'email' => 'admin@nexinvent.local',
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $admin_id = insert('users', $adminData);

        if ($admin_id) {
            echo "Admin user created successfully!<br>";
            echo "Username: admin<br>";
            echo "Password: admin123<br>";
            echo "User ID: " . $admin_id . "<br><br>";
            echo "<a href='login/index.php' class='btn btn-primary'>Go to Login</a>";
        } else {
            echo "Failed to create admin user.<br>";
        }
    } else {
        echo "Admin user already exists.<br><br>";
        echo "<a href='login/index.php' class='btn btn-primary'>Go to Login</a>";
    }

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