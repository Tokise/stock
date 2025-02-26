<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT 
            u.*,
            COALESCE(e.full_name, u.full_name) as full_name,
            e.position,
            e.department,
            e.employee_id
        FROM users u 
        LEFT JOIN employee_details e ON u.user_id = e.user_id 
        WHERE u.user_id = ?";

try {
    // Using the existing database functions from db.php
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../../login/logout.php");
        exit();
    }

    // If employee details don't exist, create them
    if (!isset($user['employee_id'])) {
        $employee_data = [
            'user_id' => $user_id,
            'full_name' => $user['full_name'],
            'department' => 'Default Department',
            'position' => 'Staff Member',
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Insert employee details
            $employee_id = insert('employee_details', $employee_data);
            
            // Refresh user data after insert
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to create employee details: " . $e->getMessage());
        }
    }

    // Debug line to check if we're getting the data (you can remove this later)
    error_log("User data: " . print_r($user, true));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $_SESSION['error'] = "Current password is incorrect.";
        } else if ($new_password !== '' && $new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
        } else {
            // Update email
            $sql = "UPDATE users SET email = ? WHERE user_id = ?";
            execute($sql, [$email, $user_id]);
            
            // Update password if provided
            if ($new_password !== '') {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE user_id = ?";
                execute($sql, [$hashed_password, $user_id]);
            }
            
            $_SESSION['success'] = "Account settings updated successfully.";
            header("Location: settings.php");
            exit();
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Account Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .profile-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            margin-right: 1rem;
        }
        .profile-text {
            flex: 1;
        }
        .profile-label {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        .profile-value {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .settings-card {
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
        }
        .settings-card .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 10px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            padding: 0.8rem 2rem;
            border-radius: 10px;
        }
        .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php include(__DIR__ . '/../../includes/alerts.php'); ?>
            
            <div class="profile-header mb-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-grow-1">
                        <h2 class="mb-0"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></h2>
                        <div class="text-white-50">
                            <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-info">
                    <div class="profile-item">
                        <div class="profile-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="profile-text">
                            <div class="profile-label">Position</div>
                            <div class="profile-value"><?php echo htmlspecialchars($user['position'] ?? 'No Position Set'); ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="profile-text">
                            <div class="profile-label">Department</div>
                            <div class="profile-value"><?php echo htmlspecialchars($user['department'] ?? 'No Department Set'); ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="profile-text">
                            <div class="profile-label">Email</div>
                            <div class="profile-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="profile-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="profile-text">
                            <div class="profile-label">Role</div>
                            <div class="profile-value"><?php echo ucfirst($user['role'] ?? 'User'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Settings Form Card -->
                <div class="col-md-12 mb-4">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0">Update Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control border-start-0" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control border-start-0" id="current_password" 
                                               name="current_password" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-key"></i>
                                            </span>
                                            <input type="password" class="form-control border-start-0" id="new_password" 
                                                   name="new_password">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-key-fill"></i>
                                            </span>
                                            <input type="password" class="form-control border-start-0" id="confirm_password" 
                                                   name="confirm_password">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
