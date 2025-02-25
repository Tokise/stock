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

// Process payroll generation if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payroll'])) {
    try {
        // Validate inputs
        $employee_id = $_POST['employee_id'] ?? 0;
        $pay_period_start = $_POST['pay_period_start'] ?? '';
        $pay_period_end = $_POST['pay_period_end'] ?? '';
        $basic_salary = $_POST['basic_salary'] ?? 0;
        $deductions = $_POST['deductions'] ?? 0;
        $bonuses = $_POST['bonuses'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (empty($employee_id) || empty($pay_period_start) || empty($pay_period_end) || empty($basic_salary)) {
            throw new Exception("All required fields must be filled");
        }
        
        // Calculate net salary
        $net_salary = $basic_salary - $deductions + $bonuses;
        
        // Insert payroll record
        $payroll_data = [
            'employee_id' => $employee_id,
            'pay_period_start' => $pay_period_start,
            'pay_period_end' => $pay_period_end,
            'basic_salary' => $basic_salary,
            'deductions' => $deductions,
            'bonuses' => $bonuses,
            'net_salary' => $net_salary,
            'status' => 'pending',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $payroll_id = insert('payroll', $payroll_data);
        
        $_SESSION['success'] = "Payroll generated successfully!";
        header("Location: payroll.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Process payroll payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $payroll_id = $_POST['payroll_id'] ?? 0;
        $payment_date = date('Y-m-d');
        
        if (empty($payroll_id)) {
            throw new Exception("Invalid payroll ID");
        }
        
        // Update payroll status
        $update_data = [
            'status' => 'paid',
            'payment_date' => $payment_date,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        update('payroll', $update_data, 'payroll_id = ?', [$payroll_id]);
        
        $_SESSION['success'] = "Payment processed successfully!";
        header("Location: payroll.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get all employees
$sql = "SELECT e.*, u.email 
        FROM employee_details e 
        LEFT JOIN users u ON e.user_id = u.user_id 
        ORDER BY e.full_name";
$employees = fetchAll($sql);

// Get payroll records with employee names
$sql = "SELECT p.*, e.full_name as employee_name 
        FROM payroll p 
        JOIN employee_details e ON p.employee_id = e.employee_id 
        ORDER BY p.pay_period_end DESC, e.full_name";
$payroll_records = fetchAll($sql);

// Calculate payroll statistics
$total_pending = 0;
$total_paid = 0;
$current_month_total = 0;
$current_month = date('Y-m');

foreach ($payroll_records as $record) {
    if ($record['status'] === 'pending') {
        $total_pending += $record['net_salary'];
    } else {
        $total_paid += $record['net_salary'];
    }
    
    // Check if payment is from current month
    $payment_month = date('Y-m', strtotime($record['pay_period_end']));
    if ($payment_month === $current_month) {
        $current_month_total += $record['net_salary'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Payroll Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .payroll-stats {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            height: 100%;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-paid {
            background-color: #28a745;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-cash-coin me-2"></i> Payroll Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generatePayrollModal">
                    <i class="bi bi-plus-circle me-2"></i> Generate Payroll
                </button>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Payroll Statistics -->
            <div class="payroll-stats">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value">$<?php echo number_format($current_month_total, 2); ?></div>
                                    <div class="stat-label">Current Month Payroll</div>
                                </div>
                                <div class="stat-icon text-primary">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value">$<?php echo number_format($total_pending, 2); ?></div>
                                    <div class="stat-label">Pending Payments</div>
                                </div>
                                <div class="stat-icon text-warning">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value">$<?php echo number_format($total_paid, 2); ?></div>
                                    <div class="stat-label">Total Paid</div>
                                </div>
                                <div class="stat-icon text-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payroll Records Table -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Payroll Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="payrollTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Pay Period</th>
                                    <th>Basic Salary</th>
                                    <th>Deductions</th>
                                    <th>Bonuses</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payroll_records as $record): ?>
                                    <tr>
                                        <td><?php echo $record['payroll_id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                        <td>
                                            <?php 
                                            echo date('M d, Y', strtotime($record['pay_period_start'])); 
                                            echo ' - ';
                                            echo date('M d, Y', strtotime($record['pay_period_end']));
                                            ?>
                                        </td>
                                        <td>$<?php echo number_format($record['basic_salary'], 2); ?></td>
                                        <td>$<?php echo number_format($record['deductions'], 2); ?></td>
                                        <td>$<?php echo number_format($record['bonuses'], 2); ?></td>
                                        <td>$<?php echo number_format($record['net_salary'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $record['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $record['payment_date'] 
                                                ? date('M d, Y', strtotime($record['payment_date'])) 
                                                : '-'; 
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info view-payroll" 
                                                        data-id="<?php echo $record['payroll_id']; ?>"
                                                        data-employee="<?php echo htmlspecialchars($record['employee_name']); ?>"
                                                        data-start="<?php echo $record['pay_period_start']; ?>"
                                                        data-end="<?php echo $record['pay_period_end']; ?>"
                                                        data-basic="<?php echo $record['basic_salary']; ?>"
                                                        data-deductions="<?php echo $record['deductions']; ?>"
                                                        data-bonuses="<?php echo $record['bonuses']; ?>"
                                                        data-net="<?php echo $record['net_salary']; ?>"
                                                        data-status="<?php echo $record['status']; ?>"
                                                        data-payment="<?php echo $record['payment_date']; ?>"
                                                        data-notes="<?php echo htmlspecialchars($record['notes']); ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                
                                                <?php if ($record['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success process-payment" 
                                                        data-id="<?php echo $record['payroll_id']; ?>"
                                                        data-employee="<?php echo htmlspecialchars($record['employee_name']); ?>"
                                                        data-amount="<?php echo $record['net_salary']; ?>">
                                                    <i class="bi bi-credit-card"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-sm btn-primary print-payslip"
                                                        data-id="<?php echo $record['payroll_id']; ?>">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </div>
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
    
    <!-- Generate Payroll Modal -->
    <div class="modal fade" id="generatePayrollModal" tabindex="-1" aria-labelledby="generatePayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generatePayrollModalLabel">Generate Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['employee_id']; ?>" 
                                            data-salary="<?php echo $employee['salary']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?> 
                                        (<?php echo htmlspecialchars($employee['position']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pay_period_start" class="form-label">Pay Period Start</label>
                                <input type="date" class="form-control" id="pay_period_start" name="pay_period_start" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pay_period_end" class="form-label">Pay Period End</label>
                                <input type="date" class="form-control" id="pay_period_end" name="pay_period_end" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="deductions" class="form-label">Deductions</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="deductions" name="deductions" value="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="bonuses" class="form-label">Bonuses</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="bonuses" name="bonuses" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="net_salary_preview" class="form-label">Net Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="net_salary_preview" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="generate_payroll" class="btn btn-primary">Generate Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Payroll Modal -->
    <div class="modal fade" id="viewPayrollModal" tabindex="-1" aria-labelledby="viewPayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPayrollModalLabel">Payroll Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Employee</h6>
                            <p id="view_employee" class="fw-bold"></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Pay Period</h6>
                            <p id="view_period"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <h6>Basic Salary</h6>
                            <p id="view_basic" class="fw-bold"></p>
                        </div>
                        <div class="col-md-3">
                            <h6>Deductions</h6>
                            <p id="view_deductions" class="text-danger"></p>
                        </div>
                        <div class="col-md-3">
                            <h6>Bonuses</h6>
                            <p id="view_bonuses" class="text-success"></p>
                        </div>
                        <div class="col-md-3">
                            <h6>Net Salary</h6>
                            <p id="view_net" class="fw-bold fs-5"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Status</h6>
                            <p id="view_status"></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Date</h6>
                            <p id="view_payment"></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Notes</h6>
                        <p id="view_notes"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="print_from_view">
                        <i class="bi bi-printer me-1"></i> Print Payslip
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1" aria-labelledby="processPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processPaymentModalLabel">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="payment_payroll_id" name="payroll_id">
                        
                        <div class="alert alert-info">
                            <p class="mb-0">You are about to process payment for <strong id="payment_employee"></strong>.</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="payment_amount" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_payment" class="btn btn-success">
                            <i class="bi bi-credit-card me-1"></i> Process Payment
                        </button>
                    </div>
                </form>
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
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#payrollTable').DataTable({
                order: [[0, 'desc']], // Sort by ID descending
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            // Employee selection change handler
            $('#employee_id').change(function() {
                const salary = $(this).find(':selected').data('salary') || 0;
                $('#basic_salary').val(salary);
                updateNetSalary();
            });
            
            // Update net salary on input change
            $('#basic_salary, #deductions, #bonuses').on('input', function() {
                updateNetSalary();
            });
            
            function updateNetSalary() {
                const basic = parseFloat($('#basic_salary').val()) || 0;
                const deductions = parseFloat($('#deductions').val()) || 0;
                const bonuses = parseFloat($('#bonuses').val()) || 0;
                const net = basic - deductions + bonuses;
                $('#net_salary_preview').val(net.toFixed(2));
            }
            
            // View payroll details
            $('.view-payroll').click(function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee');
                const start = new Date($(this).data('start')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const end = new Date($(this).data('end')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const basic = $(this).data('basic');
                const deductions = $(this).data('deductions');
                const bonuses = $(this).data('bonuses');
                const net = $(this).data('net');
                const status = $(this).data('status');
                const payment = $(this).data('payment') ? new Date($(this).data('payment')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not paid yet';
                const notes = $(this).data('notes');
                
                $('#view_employee').text(employee);
                $('#view_period').text(`${start} - ${end}`);
                $('#view_basic').text(`$${parseFloat(basic).toFixed(2)}`);
                $('#view_deductions').text(`$${parseFloat(deductions).toFixed(2)}`);
                $('#view_bonuses').text(`$${parseFloat(bonuses).toFixed(2)}`);
                $('#view_net').text(`$${parseFloat(net).toFixed(2)}`);
                $('#view_status').html(`<span class="badge bg-${status === 'pending' ? 'warning' : 'success'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
                $('#view_payment').text(payment);
                $('#view_notes').text(notes || 'No notes');
                
                // Set the payroll ID for printing
                $('#print_from_view').data('id', id);
                
                $('#viewPayrollModal').modal('show');
            });
            
            // Process payment
            $('.process-payment').click(function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee');
                const amount = $(this).data('amount');
                
                $('#payment_payroll_id').val(id);
                $('#payment_employee').text(employee);
                $('#payment_amount').val(parseFloat(amount).toFixed(2));
                
                $('#processPaymentModal').modal('show');
            });
            
            // Print payslip
            $('.print-payslip, #print_from_view').click(function() {
                const id = $(this).data('id');
                window.open(`payslip.php?id=${id}`, '_blank');
            });
        });
    </script>
</body>
</html> 