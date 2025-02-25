<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if there's a success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'customer') {
        header("Location: ../modules/customer/index.php");
    } else {
        header("Location: ../modules/dashboard/index.php");
    }
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/db.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Get user by username
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = fetchOne($sql, [$username]);
        
        if (!$user) {
            $error = "Invalid username or password";
        } elseif ($user['status'] !== 'active') {
            $error = "Your account is not active. Please contact support.";
        } elseif (!password_verify($password, $user['password'])) {
            error_log("Login failed for user $username - Password verification failed");
            error_log("Provided password hash: " . password_hash($password, PASSWORD_DEFAULT));
            error_log("Stored password hash: " . $user['password']);
            $error = "Invalid username or password";
        } else {
            // Start transaction
            $conn = getDBConnection();
            $conn->beginTransaction();

            try {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                if ($user['role'] === 'customer') {
                    // Check if customer record exists by email
                    $customer = fetchOne("SELECT customer_id FROM customers WHERE email = ?", [$user['email']]);
                    
                    if (!$customer) {
                        // Only create customer record if it doesn't exist
                        $customer_data = [
                            'name' => $user['full_name'],
                            'email' => $user['email'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        $customer_id = insert('customers', $customer_data);
                        $_SESSION['customer_id'] = $customer_id;
                    } else {
                        $_SESSION['customer_id'] = $customer['customer_id'];
                    }

                    // Check if customer profile exists
                    $profile = fetchOne("SELECT * FROM customer_profiles WHERE user_id = ?", [$user['user_id']]);
                    if (!$profile) {
                        $profile_data = [
                            'user_id' => $user['user_id'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insert('customer_profiles', $profile_data);
                    }
                }
                
                // Update last login timestamp
                executeQuery("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?", [$user['user_id']]);
                
                // Commit transaction
                $conn->commit();
                
                // Clear any existing error messages
                unset($error);
                
                // Redirect based on role
                if ($user['role'] === 'customer') {
                    header("Location: ../modules/customer/index.php");
                } else {
                    header("Location: ../modules/dashboard/index.php");
                }
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Login error: " . $e->getMessage());
                $error = "An error occurred during login. Please try again.";
            }
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NexInvent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            width: 50%;
            background-color: white;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-radius: 15px;
            padding: 2.5rem;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-container img {
            width: 250px;
            height: auto;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 2px rgba(13, 71, 161, 0.25);
            border-color: #0d47a1;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            color: #0d47a1;
        }
        .btn-primary {
            padding: 0.75rem;
            border-radius: 8px;
            width: 100%;
            background: linear-gradient(to right, #1a237e, #0d47a1);
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #0d47a1, #01579b);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 71, 161, 0.2);
        }
        .form-label {
            color: #0d47a1;
            font-weight: 500;
        }
        a {
            color: #0d47a1;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            color: #1a237e;
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="../../assets/LOGO.png" alt="NexInvent Logo">
        </div>
      
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        <div class="text-center mt-3">
            <p class="mb-0">Don't have an account? <a href="../register/index.php">Register here</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>