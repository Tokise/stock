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

// Check if payroll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid payroll ID");
}

$payroll_id = (int)$_GET['id'];

// Get payroll details with employee information
$sql = "SELECT p.*, e.full_name, e.position, e.department, e.hire_date, u.email
        FROM payroll p
        JOIN employee_details e ON p.employee_id = e.employee_id
        LEFT JOIN users u ON e.user_id = u.user_id
        WHERE p.payroll_id = ?";

$payroll = fetchOne($sql, [$payroll_id]);

if (!$payroll) {
    die("Payroll record not found");
}

// Format dates
$pay_period_start = date('M d, Y', strtotime($payroll['pay_period_start']));
$pay_period_end = date('M d, Y', strtotime($payroll['pay_period_end']));
$payment_date = $payroll['payment_date'] ? date('M d, Y', strtotime($payroll['payment_date'])) : 'Pending';
$hire_date = date('M d, Y', strtotime($payroll['hire_date']));

// Calculate years of service
$hire_timestamp = strtotime($payroll['hire_date']);
$now = time();
$years_of_service = floor(($now - $hire_timestamp) / (60 * 60 * 24 * 365));

// Get company information from settings
$sql = "SELECT * FROM settings WHERE setting_key IN ('company_name', 'company_address', 'company_phone', 'company_email')";
$settings_result = fetchAll($sql);

$company = [];
foreach ($settings_result as $setting) {
    $company[$setting['setting_key']] = $setting['setting_value'];
}

// Default company info if not set
$company_name = $company['company_name'] ?? 'NexInvent';
$company_address = $company['company_address'] ?? '123 Business St, City, Country';
$company_phone = $company['company_phone'] ?? '+1 (555) 123-4567';
$company_email = $company['company_email'] ?? 'info@nexinvent.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo htmlspecialchars($payroll['full_name']); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payslip-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .payslip-header {
            background-color: #2c3e50;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        
        .payslip-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .payslip-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .payslip-body {
            padding: 30px;
        }
        
        .company-info, .employee-info {
            margin-bottom: 30px;
        }
        
        .info-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .payment-details {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .payment-row:last-child {
            border-bottom: none;
        }
        
        .payment-label {
            font-weight: 500;
        }
        
        .payment-value {
            font-weight: bold;
        }
        
        .net-salary {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .payslip-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }
        
        @media print {
            body {
                background-color: #fff;
            }
            
            .payslip-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <div class="payslip-header">
            <div class="payslip-title">PAYSLIP</div>
            <div class="payslip-subtitle">Pay Period: <?php echo $pay_period_start; ?> - <?php echo $pay_period_end; ?></div>
        </div>
        
        <div class="payslip-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="company-info">
                        <h5 class="mb-3">Company Information</h5>
                        <div class="info-label">Company Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($company_name); ?></div>
                        
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($company_address); ?></div>
                        
                        <div class="info-label">Contact</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($company_phone); ?><br>
                            <?php echo htmlspecialchars($company_email); ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="employee-info">
                        <h5 class="mb-3">Employee Information</h5>
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($payroll['full_name']); ?></div>
                        
                        <div class="info-label">Position</div>
                        <div class="info-value"><?php echo htmlspecialchars($payroll['position']); ?> (<?php echo htmlspecialchars($payroll['department']); ?>)</div>
                        
                        <div class="info-label">Hire Date</div>
                        <div class="info-value"><?php echo $hire_date; ?> (<?php echo $years_of_service; ?> years)</div>
                        
                        <?php if (!empty($payroll['email'])): ?>
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($payroll['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="payment-details">
                <h5 class="mb-3">Payment Details</h5>
                
                <div class="payment-row">
                    <div class="payment-label">Basic Salary</div>
                    <div class="payment-value">$<?php echo number_format($payroll['basic_salary'], 2); ?></div>
                </div>
                
                <?php if ($payroll['deductions'] > 0): ?>
                <div class="payment-row">
                    <div class="payment-label">Deductions</div>
                    <div class="payment-value text-danger">-$<?php echo number_format($payroll['deductions'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($payroll['bonuses'] > 0): ?>
                <div class="payment-row">
                    <div class="payment-label">Bonuses</div>
                    <div class="payment-value text-success">+$<?php echo number_format($payroll['bonuses'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="payment-row">
                    <div class="payment-label">Net Salary</div>
                    <div class="payment-value net-salary">$<?php echo number_format($payroll['net_salary'], 2); ?></div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $payroll['status']; ?>">
                            <?php echo ucfirst($payroll['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-label">Payment Date</div>
                    <div class="info-value"><?php echo $payment_date; ?></div>
                </div>
            </div>
            
            <?php if (!empty($payroll['notes'])): ?>
            <div class="mb-4">
                <div class="info-label">Notes</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($payroll['notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="payslip-footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on <?php echo date('F d, Y h:i A'); ?></p>
        </div>
    </div>
    
    <button class="btn btn-primary print-button" onclick="window.print()">
        <i class="bi bi-printer me-2"></i> Print Payslip
    </button>
</body>
</html> 