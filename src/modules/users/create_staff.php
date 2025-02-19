<?php
session_start();
require_once '../config/db.php';
require_once '../includes/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

requirePermission('manage_employees');

// Get employee details if employee_id is provided
$employee = null;
if (isset($_GET['employee_id'])) {
    $sql = "SELECT * FROM employee_details WHERE employee_id = ?";
    $employee = fetchOne($sql, [$_GET['employee_id']]);

    if (!$employee) {
        $_SESSION['error'] = "Employee not found";
        header("Location: ../employees/index.php");
        exit();
    }

    // Check if employee already has an account
    if (!empty($employee['user_id'])) {
        $_SESSION['error'] = "Employee already has an account";
        header("Location: ../employees/index.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $employee_id = $_POST['employee_id'] ?? null;
    
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } else {
        // Check if username exists
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        if (fetchValue($sql, [$username]) > 0) {
            $errors['username'] = "Username already exists";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } else {
        // Check if email exists
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        if (fetchValue($sql, [$email]) > 0) {
            $errors['email'] = "Email already exists";
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    // Validate role
    if (empty($role)) {
        $errors['role'] = "Role is required";
    } elseif (!in_array($role, ['employee', 'manager', 'admin'])) {
        $errors['role'] = "Invalid role selected";
    }
    
    // Validate full name if no employee is selected
    if (!$employee && empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $conn = getDBConnection();
            $conn->beginTransaction();
            
            // Create user account
            $user_data = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email,
                'full_name' => $employee ? $employee['full_name'] : $full_name,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id'],
                'status' => 'active'  // Add status field
            ];
            
            // Debug log
            error_log("Attempting to create user with data: " . print_r($user_data, true));
            
            $user_id = insert('users', $user_data);
            
            // If this is for an existing employee, update their record
            if ($employee) {
                update('employee_details', 
                       ['user_id' => $user_id], 
                       'employee_id = ?', 
                       [$employee['employee_id']]);
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success message and redirect
            $_SESSION['success'] = "Staff account created successfully!";
            header("Location: ../employees/index.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors['general'] = "Failed to create staff account: " . $e->getMessage();
            error_log("Staff account creation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Create Staff Account</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Create Staff Account</h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($employee): ?>
                                <div class="alert alert-info">
                                    <strong>Creating account for:</strong> <?php echo htmlspecialchars($employee['full_name']); ?><br>
                                    <strong>Position:</strong> <?php echo htmlspecialchars($employee['position']); ?><br>
                                    <strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($errors['general']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <?php if ($employee): ?>
                                    <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                <?php else: ?>
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                               id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                        <?php if (isset($errors['full_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                               id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                        <?php if (isset($errors['username'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                               id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" 
                                            id="role" name="role">
                                        <option value="">Select Role</option>
                                        <option value="employee" <?php echo ($_POST['role'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                        <option value="manager" <?php echo ($_POST['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <?php if (isset($errors['role'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['role']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                               id="password" name="password">
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                               id="confirm_password" name="confirm_password">
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        Create Staff Account
                                    </button>
                                    <a href="../employees/index.php" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 