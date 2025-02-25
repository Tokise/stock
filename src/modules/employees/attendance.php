<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Check if user has permission to manage attendance
requirePermission('manage_employees');

// Process form submission for adding attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_attendance') {
        $employee_id = $_POST['employee_id'];
        $date = $_POST['date'];
        $status = $_POST['status'];
        $time_in = !empty($_POST['time_in']) ? $_POST['date'] . ' ' . $_POST['time_in'] : null;
        $time_out = !empty($_POST['time_out']) ? $_POST['date'] . ' ' . $_POST['time_out'] : null;
        $notes = $_POST['notes'];
        
        // Check if attendance record already exists for this employee on this date
        $sql = "SELECT attendance_id FROM attendance WHERE employee_id = ? AND date = ?";
        $existing = fetchOne($sql, [$employee_id, $date]);
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE attendance SET 
                    status = ?, 
                    time_in = ?, 
                    time_out = ?, 
                    notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE attendance_id = ?";
            $params = [$status, $time_in, $time_out, $notes, $existing['attendance_id']];
            $message = "Attendance record updated successfully";
        } else {
            // Insert new record
            $sql = "INSERT INTO attendance (employee_id, date, status, time_in, time_out, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$employee_id, $date, $status, $time_in, $time_out, $notes];
            $message = "Attendance record added successfully";
        }
        
        if (executeQuery($sql, $params)) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = "Error saving attendance record";
        }
        
        header("Location: attendance.php");
        exit();
    } elseif ($_POST['action'] === 'bulk_attendance') {
        $date = $_POST['bulk_date'];
        $status = $_POST['bulk_status'];
        $employee_ids = $_POST['employee_ids'];
        $success_count = 0;
        
        foreach ($employee_ids as $employee_id) {
            // Check if attendance record already exists
            $sql = "SELECT attendance_id FROM attendance WHERE employee_id = ? AND date = ?";
            $existing = fetchOne($sql, [$employee_id, $date]);
            
            if ($existing) {
                // Update existing record
                $sql = "UPDATE attendance SET 
                        status = ?, 
                        updated_at = CURRENT_TIMESTAMP
                        WHERE attendance_id = ?";
                $params = [$status, $existing['attendance_id']];
            } else {
                // Insert new record
                $sql = "INSERT INTO attendance (employee_id, date, status) 
                        VALUES (?, ?, ?)";
                $params = [$employee_id, $date, $status];
            }
            
            if (executeQuery($sql, $params)) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success'] = "Attendance recorded for $success_count employees";
        } else {
            $_SESSION['error'] = "Error recording attendance";
        }
        
        header("Location: attendance.php");
        exit();
    }
}

// Get all employees
$sql = "SELECT employee_id, full_name, department, position FROM employee_details ORDER BY full_name";
$employees = fetchAll($sql);

// Get attendance records for the current month (default view)
$current_month = date('Y-m');
$filter_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$filter_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

$sql = "SELECT a.*, e.full_name, e.department, e.position 
        FROM attendance a
        JOIN employee_details e ON a.employee_id = e.employee_id
        WHERE DATE_FORMAT(a.date, '%Y-%m') = ?";
$params = [$filter_month];

if (!empty($filter_employee)) {
    $sql .= " AND a.employee_id = ?";
    $params[] = $filter_employee;
}

$sql .= " ORDER BY a.date DESC, e.full_name";
$attendance_records = fetchAll($sql, $params);

// Get attendance summary for the month
$sql = "SELECT e.employee_id, e.full_name, e.department, e.position,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
        COUNT(a.attendance_id) as total_days
        FROM employee_details e
        LEFT JOIN attendance a ON e.employee_id = a.employee_id AND DATE_FORMAT(a.date, '%Y-%m') = ?
        GROUP BY e.employee_id
        ORDER BY e.full_name";
$attendance_summary = fetchAll($sql, [$filter_month]);

// Get months for filter dropdown (last 12 months)
$months = [];
for ($i = 0; $i < 12; $i++) {
    $month_value = date('Y-m', strtotime("-$i months"));
    $month_label = date('F Y', strtotime("-$i months"));
    $months[$month_value] = $month_label;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Attendance Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #0d6efd;
            color: #0d6efd;
            background-color: transparent;
        }
        .status-badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }
        .attendance-calendar .day {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 2px;
            font-size: 0.9rem;
        }
        .attendance-calendar .present {
            background-color: rgba(25, 135, 84, 0.2);
            color: #198754;
        }
        .attendance-calendar .absent {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        .attendance-calendar .late {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        .attendance-calendar .no-record {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
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
                <h2 class="mb-0">Attendance Management</h2>
                <div>
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                        <i class="bi bi-plus-lg me-2"></i>Record Attendance
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkAttendanceModal">
                        <i class="bi bi-people me-2"></i>Bulk Attendance
                    </button>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month" onchange="this.form.submit()">
                                <?php foreach ($months as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $filter_month === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" onchange="this.form.submit()">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['employee_id']; ?>" <?php echo $filter_employee == $employee['employee_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="attendanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">
                        <i class="bi bi-bar-chart me-2"></i>Monthly Summary
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab" aria-controls="records" aria-selected="false">
                        <i class="bi bi-list-check me-2"></i>Attendance Records
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="attendanceTabsContent">
                <!-- Monthly Summary Tab -->
                <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Attendance Summary for <?php echo date('F Y', strtotime($filter_month)); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="summaryTable">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Present Days</th>
                                            <th>Absent Days</th>
                                            <th>Late Days</th>
                                            <th>Attendance Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_summary as $summary): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($summary['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($summary['department']); ?></td>
                                                <td><?php echo htmlspecialchars($summary['position']); ?></td>
                                                <td><?php echo $summary['present_days']; ?></td>
                                                <td><?php echo $summary['absent_days']; ?></td>
                                                <td><?php echo $summary['late_days']; ?></td>
                                                <td>
                                                    <?php 
                                                    $attendance_rate = $summary['total_days'] > 0 
                                                        ? round(($summary['present_days'] + $summary['late_days']) / $summary['total_days'] * 100) 
                                                        : 0;
                                                    
                                                    $badge_class = 'bg-success';
                                                    if ($attendance_rate < 80) {
                                                        $badge_class = 'bg-danger';
                                                    } elseif ($attendance_rate < 90) {
                                                        $badge_class = 'bg-warning';
                                                    }
                                                    ?>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?php echo $badge_class; ?>" role="progressbar" 
                                                             style="width: <?php echo $attendance_rate; ?>%;" 
                                                             aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo $attendance_rate; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="?month=<?php echo $filter_month; ?>&employee_id=<?php echo $summary['employee_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records Tab -->
                <div class="tab-pane fade" id="records" role="tabpanel" aria-labelledby="records-tab">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Attendance Records for <?php echo date('F Y', strtotime($filter_month)); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="recordsTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee</th>
                                            <th>Status</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('d M Y (D)', strtotime($record['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                                <td>
                                                    <?php if ($record['status'] === 'present'): ?>
                                                        <span class="badge bg-success status-badge">Present</span>
                                                    <?php elseif ($record['status'] === 'absent'): ?>
                                                        <span class="badge bg-danger status-badge">Absent</span>
                                                    <?php elseif ($record['status'] === 'late'): ?>
                                                        <span class="badge bg-warning status-badge">Late</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                                <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary edit-attendance" 
                                                            data-id="<?php echo $record['attendance_id']; ?>"
                                                            data-employee="<?php echo $record['employee_id']; ?>"
                                                            data-date="<?php echo $record['date']; ?>"
                                                            data-status="<?php echo $record['status']; ?>"
                                                            data-time-in="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>"
                                                            data-time-out="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>"
                                                            data-notes="<?php echo htmlspecialchars($record['notes'] ?? ''); ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
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
        </div>
    </div>
    
    <!-- Add Attendance Modal -->
    <div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-labelledby="addAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAttendanceModalLabel">Record Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="attendanceForm">
                    <input type="hidden" name="action" value="add_attendance">
                    <input type="hidden" name="attendance_id" id="attendance_id" value="">
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select class="form-select" id="employee_id_modal" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>">
                                            <?php echo htmlspecialchars($employee['full_name']); ?> (<?php echo htmlspecialchars($employee['department']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="time_in" name="time_in">
                            </div>
                            <div class="col-md-4">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="time_out" name="time_out">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bulk Attendance Modal -->
    <div class="modal fade" id="bulkAttendanceModal" tabindex="-1" aria-labelledby="bulkAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkAttendanceModalLabel">Bulk Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="bulk_attendance">
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bulk_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="bulk_date" name="bulk_date" required max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="bulk_status" class="form-label">Status</label>
                                <select class="form-select" id="bulk_status" name="bulk_status" required>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Employees</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select_all">
                                            <label class="form-check-label fw-bold" for="select_all">
                                                Select All Employees
                                            </label>
                                        </div>
                                    </div>
                                    <hr>
                                    <?php foreach ($employees as $employee): ?>
                                        <div class="form-check">
                                            <input class="form-check-input employee-checkbox" type="checkbox" 
                                                   name="employee_ids[]" value="<?php echo $employee['employee_id']; ?>" 
                                                   id="employee_<?php echo $employee['employee_id']; ?>">
                                            <label class="form-check-label" for="employee_<?php echo $employee['employee_id']; ?>">
                                                <?php echo htmlspecialchars($employee['full_name']); ?> - <?php echo htmlspecialchars($employee['department']); ?> (<?php echo htmlspecialchars($employee['position']); ?>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
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
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#summaryTable').DataTable({
                "order": [[0, "asc"]]
            });
            
            $('#recordsTable').DataTable({
                "order": [[0, "desc"]]
            });
            
            // Initialize date picker
            flatpickr("#date", {
                maxDate: "today"
            });
            
            flatpickr("#bulk_date", {
                maxDate: "today"
            });
            
            // Select all checkbox
            $('#select_all').change(function() {
                $('.employee-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Edit attendance
            $('.edit-attendance').click(function() {
                const modal = $('#addAttendanceModal');
                
                // Set form values
                $('#employee_id_modal').val($(this).data('employee'));
                $('#date').val($(this).data('date'));
                $('#status').val($(this).data('status'));
                $('#time_in').val($(this).data('time-in'));
                $('#time_out').val($(this).data('time-out'));
                $('#notes').val($(this).data('notes'));
                
                // Show modal
                modal.modal('show');
            });
            
            // Status change handler
            $('#status').change(function() {
                if ($(this).val() === 'absent') {
                    $('#time_in, #time_out').val('').prop('disabled', true);
                } else {
                    $('#time_in, #time_out').prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html> 