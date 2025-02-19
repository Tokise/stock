<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'StockManagementProject/src/modules/config/db.php';

try {
    // Check database connection
    $conn = getDBConnection();
    echo "Database connection successful!<br><br>";

    // Check if test customer exists
    $customer = fetchOne("SELECT u.*, c.customer_id, c.name as customer_name, cp.profile_id 
                         FROM users u 
                         LEFT JOIN customers c ON c.email = u.email
                         LEFT JOIN customer_profiles cp ON cp.user_id = u.user_id
                         WHERE u.username = 'customer'");
    
    if ($customer) {
        echo "Test customer found:<br>";
        echo "User ID: " . $customer['user_id'] . "<br>";
        echo "Username: " . $customer['username'] . "<br>";
        echo "Email: " . $customer['email'] . "<br>";
        echo "Role: " . $customer['role'] . "<br>";
        echo "Status: " . $customer['status'] . "<br>";
        echo "Customer ID: " . ($customer['customer_id'] ?? 'Not linked') . "<br>";
        echo "Profile ID: " . ($customer['profile_id'] ?? 'Not created') . "<br>";
        
        // Test password verification
        $test_password = 'customer123';
        $is_password_valid = password_verify($test_password, $customer['password']);
        echo "<br>Password verification test:<br>";
        echo "Testing password: " . $test_password . "<br>";
        echo "Password verification result: " . ($is_password_valid ? "VALID" : "INVALID") . "<br>";
        
        // Update password if verification fails
        if (!$is_password_valid) {
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<br>Updating customer password...<br>";
            executeQuery("UPDATE users SET password = ? WHERE user_id = ?", [$new_hash, $customer['user_id']]);
            echo "Password updated successfully!<br>";
        }

        // Create customer record if not exists
        if (!$customer['customer_id']) {
            echo "<br>Creating customer record...<br>";
            $customer_data = [
                'name' => $customer['full_name'],
                'email' => $customer['email'],
                'phone' => '1234567890',
                'address' => '123 Test Street',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $customer_id = insert('customers', $customer_data);
            echo "Customer record created with ID: " . $customer_id . "<br>";
        }

        // Create customer profile if not exists
        if (!$customer['profile_id']) {
            echo "<br>Creating customer profile...<br>";
            $profile_data = [
                'user_id' => $customer['user_id'],
                'default_shipping_address' => '123 Test Street',
                'default_billing_address' => '123 Test Street',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $profile_id = insert('customer_profiles', $profile_data);
            echo "Customer profile created with ID: " . $profile_id . "<br>";
        }
    } else {
        echo "No test customer found. Creating new customer account...<br>";
        
        // Create user account
        $password = password_hash('customer123', PASSWORD_DEFAULT);
        $userData = [
            'username' => 'customer',
            'password' => $password,
            'email' => 'customer@test.com',
            'full_name' => 'Test Customer',
            'role' => 'customer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $user_id = insert('users', $userData);
        echo "User account created with ID: " . $user_id . "<br>";

        // Create customer record
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $customer_id = insert('customers', $customerData);
        echo "Customer record created with ID: " . $customer_id . "<br>";

        // Create customer profile
        $profileData = [
            'user_id' => $user_id,
            'default_shipping_address' => '123 Test Street',
            'default_billing_address' => '123 Test Street',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $profile_id = insert('customer_profiles', $profileData);
        echo "Customer profile created with ID: " . $profile_id . "<br>";
    }

    echo "<br><hr><br>";
    echo "<strong>Test Customer Login Credentials:</strong><br>";
    echo "Username: customer<br>";
    echo "Password: customer123<br>";
    echo "<br><a href='StockManagementProject/src/login/index.php' class='btn btn-primary'>Go to Login Page</a>";

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
    <title>Test Customer Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-body">
            <h1 class="card-title">Test Customer Setup</h1>
            <hr>
            <!-- PHP output will appear here -->
        </div>
    </div>
</body>
</html> 