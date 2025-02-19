<?php
session_start();
require_once '../config/db.php';
require_once '../includes/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

requirePermission('view_employees');

// Get employee list with user account information
$sql = "SELECT e.*, 
        u.username, u.email, u.role, u.status
        FROM employee_details e 
        LEFT JOIN users u ON e.user_id = u.user_id 
        ORDER BY e.employee_id DESC";
$employees = fetchAll($sql);

// Get statistics
$total_employees = count($employees);
$active_accounts = 0;
$managers = 0;
$regular_employees = 0;

foreach ($employees as $emp) {
    if (!empty($emp['username'])) {
        $active_accounts++;
        if ($emp['role'] === 'manager') {
            $managers++;
        } else {
            $regular_employees++;
        }
    }
}

// Get existing user accounts that are not linked to employees
$sql = "SELECT u.user_id, u.username, u.email, u.role
        FROM users u 
        LEFT JOIN employee_details e ON u.user_id = e.user_id 
        WHERE e.employee_id IS NULL 
        AND u.role IN ('manager', 'employee')
        ORDER BY u.user_id DESC";
$standalone_accounts = fetchAll($sql);

// Add these to our counts
foreach ($standalone_accounts as $account) {
    if ($account['role'] === 'manager') {
        $managers++;
    } elseif ($account['role'] === 'employee') {
        $regular_employees++;
    }
    $active_accounts++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Employee Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .stat-card h5 {
            font-size: 1.1rem;
            margin: 0;
            opacity: 0.9;
        }
        .table-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.25em 0.5em;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Employee Management</h2>
                <?php if (hasPermission('manage_employees')): ?>
                    <div>
                    
                        <!--<a href="../users/create_staff.php" class="btn btn-success">
                            <i class="bi bi-person-plus me-2"></i>Create Staff Account
                        </a>-->
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_employees; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Accounts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_accounts; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Managers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $managers; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-workspace fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Regular Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $regular_employees; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-badge fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Employees Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Employee List</h6>
                    <?php if (hasPermission('manage_employees')): ?>
                        <div>
                            <a href="add.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Add New Employee
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="employeesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Account Status</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['employee_id']; ?></td>
                                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td>
                                        <?php if (!empty($employee['username'])): ?>
                                            <?php if ($employee['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">No Account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($employee['role'])): ?>
                                            <span class="badge bg-primary"><?php echo ucfirst($employee['role']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($employee['username']) && hasPermission('manage_employees')): ?>
                                            <a href="../users/create_staff.php?employee_id=<?php echo $employee['employee_id']; ?>" 
                                               class="btn btn-sm btn-success" title="Create Staff Account">
                                                <i class="bi bi-person-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('manage_employees')): ?>
                                            <a href="edit.php?id=<?php echo $employee['employee_id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit Employee">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete Employee"
                                                    onclick="confirmDelete(<?php echo $employee['employee_id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            $('#employeesTable').DataTable({
                "order": [[0, "desc"]]
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
        
        function confirmDelete(employeeId) {
            if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                window.location.href = 'delete.php?id=' + employeeId;
            }
        }
    </script>
</body>
</html> 