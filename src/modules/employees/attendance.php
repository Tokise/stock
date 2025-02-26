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

// Process attendance form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employee_id = $_POST['employee_id'] ?? 0;
        $date = $_POST['date'] ?? '';
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        $status = $_POST['status'] ?? 'present';
        $notes = $_POST['notes'] ?? '';
        
        // Validate inputs
        if (empty($employee_id) || empty($date)) {
            throw new Exception("Employee and date are required");
        }
        
        // Combine date with time for timestamps
        $time_in_timestamp = !empty($time_in) ? date('Y-m-d H:i:s', strtotime("$date $time_in")) : null;
        $time_out_timestamp = !empty($time_out) ? date('Y-m-d H:i:s', strtotime("$date $time_out")) : null;
        
        // Insert attendance record
        $attendance_data = [
            'employee_id' => $employee_id,
            'date' => $date,
            'time_in' => $time_in_timestamp,
            'time_out' => $time_out_timestamp,
            'status' => $status,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        insert('attendance', $attendance_data);
        
        $_SESSION['success'] = "Attendance recorded successfully!";
        header("Location: attendance.php");
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

// Get attendance records with employee names
$sql = "SELECT a.*, e.full_name as employee_name 
        FROM attendance a 
        JOIN employee_details e ON a.employee_id = e.employee_id 
        ORDER BY a.date DESC, e.full_name";
$attendance_records = fetchAll($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Attendance Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .attendance-stats {
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
        
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-late {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-calendar-check me-2"></i> Attendance Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
                    <i class="bi bi-plus-circle me-2"></i> Record Attendance
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
            
            <!-- Attendance Records Table -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Attendance Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attendanceTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                        <td>
                                            <?php 
                                            echo $record['time_in'] 
                                                ? date('h:i A', strtotime($record['time_in'])) 
                                                : '-'; 
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $record['time_out'] 
                                                ? date('h:i A', strtotime($record['time_out'])) 
                                                : '-'; 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $record['status']; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info edit-attendance" 
                                                        data-id="<?php echo $record['attendance_id']; ?>"
                                                        data-employee="<?php echo $record['employee_id']; ?>"
                                                        data-date="<?php echo $record['date']; ?>"
                                                        data-timein="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>"
                                                        data-timeout="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>"
                                                        data-status="<?php echo $record['status']; ?>"
                                                        data-notes="<?php echo htmlspecialchars($record['notes'] ?? ''); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-attendance"
                                                        data-id="<?php echo $record['attendance_id']; ?>"
                                                        data-employee="<?php echo htmlspecialchars($record['employee_name']); ?>"
                                                        data-date="<?php echo date('M d, Y', strtotime($record['date'])); ?>">
                                                    <i class="bi bi-trash"></i>
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
    
    <!-- Record Attendance Modal -->
    <div class="modal fade" id="recordAttendanceModal" tabindex="-1" aria-labelledby="recordAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordAttendanceModalLabel">Record Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['employee_id']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required 
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="time_in" name="time_in">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="time_out" name="time_out">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttendanceModalLabel">Edit Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="ajax/update_attendance.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_attendance_id" name="attendance_id">
                        
                        <div class="mb-3">
                            <label for="edit_employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="edit_employee_id" name="employee_id" required>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['employee_id']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="edit_time_in" name="time_in">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="edit_time_out" name="time_out">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAttendanceModalLabel">Delete Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="ajax/delete_attendance.php">
                    <div class="modal-body">
                        <input type="hidden" id="delete_attendance_id" name="attendance_id">
                        <p>Are you sure you want to delete the attendance record for:</p>
                        <p class="fw-bold" id="delete_employee_name"></p>
                        <p>Date: <span id="delete_attendance_date"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Record</button>
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
            $('#attendanceTable').DataTable({
                order: [[0, 'desc']], // Sort by date descending
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            // Edit attendance
            $('.edit-attendance').click(function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee');
                const date = $(this).data('date');
                const timeIn = $(this).data('timein');
                const timeOut = $(this).data('timeout');
                const status = $(this).data('status');
                const notes = $(this).data('notes');
                
                $('#edit_attendance_id').val(id);
                $('#edit_employee_id').val(employee);
                $('#edit_date').val(date);
                $('#edit_time_in').val(timeIn);
                $('#edit_time_out').val(timeOut);
                $('#edit_status').val(status);
                $('#edit_notes').val(notes);
                
                $('#editAttendanceModal').modal('show');
            });
            
            // Delete attendance
            $('.delete-attendance').click(function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee');
                const date = $(this).data('date');
                
                $('#delete_attendance_id').val(id);
                $('#delete_employee_name').text(employee);
                $('#delete_attendance_date').text(date);
                
                $('#deleteAttendanceModal').modal('show');
            });
            
            // Status change handler
            $('#status').change(function() {
                const status = $(this).val();
                if (status === 'absent') {
                    $('#time_in, #time_out').val('').prop('disabled', true);
                } else {
                    $('#time_in, #time_out').prop('disabled', false);
                }
            });
            
            $('#edit_status').change(function() {
                const status = $(this).val();
                if (status === 'absent') {
                    $('#edit_time_in, #edit_time_out').val('').prop('disabled', true);
                } else {
                    $('#edit_time_in, #edit_time_out').prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html> 