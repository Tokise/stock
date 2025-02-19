<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nexinvent');

try {
    // First try to connect to MySQL without database
    $conn = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "Database created or already exists.\n";
    
    // Select the database
    $conn->exec("USE " . DB_NAME);
    
    // Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'staff', 'customer') NOT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        last_login DATETIME,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Users table created or already exists.\n";

    // Create customers table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Customers table created or already exists.\n";

    // Create customer_profiles table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS customer_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    $conn->exec($sql);
    echo "Customer profiles table created or already exists.\n";
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        // Create admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, full_name, role, status) 
                VALUES ('admin', :password, 'admin@nexinvent.local', 'System Administrator', 'admin', 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $password]);
        echo "Admin user created successfully.\n";
    } else {
        // Update admin password
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password WHERE username = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $password]);
        echo "Admin password updated successfully.\n";
    }
    
    // Create test customer account if it doesn't exist
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'customer1'");
    $stmt->execute();
    $customerExists = $stmt->fetchColumn() > 0;
    
    if (!$customerExists) {
        // Create customer user
        $password = password_hash('customer123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, full_name, role, status) 
                VALUES ('customer1', :password, 'customer1@example.com', 'Test Customer', 'customer', 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $password]);
        
        // Get the user_id of the newly created customer
        $user_id = $conn->lastInsertId();
        
        // Create customer profile
        $sql = "INSERT INTO customer_profiles (user_id, phone, address) 
                VALUES (:user_id, '1234567890', '123 Test Street')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        
        // Create customer record
        $sql = "INSERT INTO customers (name, email, phone, address) 
                VALUES ('Test Customer', 'customer1@example.com', '1234567890', '123 Test Street')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        echo "Test customer account created successfully.\n";
    }
    
    // Verify admin account
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "\nAdmin account details:\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        
        // Verify password
        $verify = password_verify('admin123', $admin['password']);
        echo "Password verification: " . ($verify ? "VALID" : "INVALID") . "\n";
    }
    
    // Show test customer account details
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'customer1'");
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "\nTest Customer account details:\n";
        echo "Username: customer1\n";
        echo "Password: customer123\n";
        echo "Status: " . $customer['status'] . "\n";
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?> 