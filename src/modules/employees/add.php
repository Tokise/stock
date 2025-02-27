<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

requirePermission('manage_employees');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $hire_date = $_POST['hire_date'] ?? '';
    $salary = $_POST['salary'] ?? '';
    
    $errors = [];
    
    // Validate required fields
    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    }
    
    if (empty($department)) {
        $errors['department'] = "Department is required";
    }
    
    if (empty($position)) {
        $errors['position'] = "Position is required";
    }
    
    if (empty($hire_date)) {
        $errors['hire_date'] = "Hire date is required";
    } elseif (strtotime($hire_date) > time()) {
        $errors['hire_date'] = "Hire date cannot be in the future";
    }
    
    if (empty($salary)) {
        $errors['salary'] = "Salary is required";
    } elseif (!is_numeric($salary) || $salary <= 0) {
        $errors['salary'] = "Salary must be a positive number";
    }

    if (empty($errors)) {
        try {
            // Insert employee record
            $employee_data = [
                'full_name' => $full_name,
                'department' => $department,
                'position' => $position,
                'hire_date' => $hire_date,
                'salary' => $salary,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id']
            ];
            
            // Insert employee record
            $employee_id = insert('employee_details', $employee_data);
            
            // Set success message and redirect
            $_SESSION['success'] = "Employee added successfully! You can now create their account from the employee list.";
            header("Location: index.php");
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = "Failed to add employee: " . $e->getMessage();
            error_log("Add employee error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Add Employee</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5 !important;
            --primary-dark: #4338ca !important;
        }

        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #f9fafb !important;
            color: #111827 !important;
        }

        h1, h2, h3, h4, h5, .card-title {
            font-family: 'Poppins', sans-serif !important;
            font-weight: 600 !important;
            color: #111827 !important;
        }

        .main-content {
            padding: 2rem !important;
        }

        .card {
            border-radius: 1rem !important;
            border: none !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }

        .card-header {
            background: linear-gradient(145deg, var(--primary-color), var(--primary-dark)) !important;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem !important;
            border-bottom: none !important;
        }

        .card-header h4 {
            color: white !important;
            margin-bottom: 0 !important;
            font-size: 1.25rem !important;
        }

        .card-body {
            padding: 2rem !important;
        }

        .form-label {
            font-weight: 500 !important;
            color: #4b5563 !important;
            margin-bottom: 0.5rem !important;
            font-size: 0.875rem !important;
        }

        .form-control {
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            padding: 0.625rem 1rem !important;
            font-size: 0.875rem !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        }

        .form-control:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }

        .form-control.is-invalid {
            border-color: #ef4444 !important;
            box-shadow: none !important;
        }

        .invalid-feedback {
            font-size: 0.75rem !important;
            color: #ef4444 !important;
            margin-top: 0.25rem !important;
        }

        .btn {
            font-weight: 500 !important;
            padding: 0.625rem 1.25rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.2s ease-in-out !important;
        }

        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2) !important;
        }

        .btn-secondary {
            background-color: #9ca3af !important;
            border-color: #9ca3af !important;
        }

        .btn-secondary:hover {
            background-color: #6b7280 !important;
            border-color: #6b7280 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.2) !important;
        }

        .alert {
            border-radius: 0.5rem !important;
            border: none !important;
            font-size: 0.875rem !important;
            padding: 1rem !important;
            margin-bottom: 1.5rem !important;
        }

        .alert-danger {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
        }
    </style>
</head>

<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Add New Employee</h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($errors['general']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                           id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" 
                                               id="department" name="department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                                        <?php if (isset($errors['department'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['department']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="position" class="form-label">Position</label>
                                        <input type="text" class="form-control <?php echo isset($errors['position']) ? 'is-invalid' : ''; ?>" 
                                               id="position" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                                        <?php if (isset($errors['position'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['position']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="hire_date" class="form-label">Hire Date</label>
                                        <input type="date" class="form-control <?php echo isset($errors['hire_date']) ? 'is-invalid' : ''; ?>" 
                                               id="hire_date" name="hire_date" value="<?php echo htmlspecialchars($_POST['hire_date'] ?? ''); ?>">
                                        <?php if (isset($errors['hire_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['hire_date']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="salary" class="form-label">Salary</label>
                                        <input type="number" step="0.01" class="form-control <?php echo isset($errors['salary']) ? 'is-invalid' : ''; ?>" 
                                               id="salary" name="salary" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
                                        <?php if (isset($errors['salary'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['salary']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        Add Employee
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
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