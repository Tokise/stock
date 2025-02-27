<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Check if user has permission to manage purchases
requirePermission('manage_purchases');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Purchase Orders</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Add Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5 !important;
            --primary-dark: #4338ca !important;
            --success-color: #22c55e !important;
            --warning-color: #f59e0b !important;
            --danger-color: #ef4444 !important;
            --info-color: #3b82f6 !important;
        }

        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #f9fafb !important;
            color: #1f2937 !important;
        }

        h1, h2, h3, h4, h5, .modal-title {
            font-family: 'Poppins', sans-serif !important;
            font-weight: 600 !important;
            color: #111827 !important;
        }

        .card {
            border-radius: 1rem !important;
            border: none !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            transition: transform 0.2s ease-in-out !important;
        }

        .card:hover {
            transform: translateY(-2px) !important;
        }

        .btn {
            font-weight: 500 !important;
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

        .table {
            font-size: 0.875rem !important;
        }

        .table thead th {
            background-color: #f8fafc !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.05em !important;
            padding: 1rem !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .table tbody td {
            padding: 1rem !important;
            vertical-align: middle !important;
        }

        .badge {
            font-weight: 500 !important;
            padding: 0.5em 0.75em !important;
            border-radius: 0.375rem !important;
        }

        .modal-content {
            border-radius: 1rem !important;
            border: none !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
        }

        .modal-header {
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem !important;
        }

        .modal-footer {
            background-color: #f8fafc !important;
            border-top: 1px solid #e2e8f0 !important;
            border-radius: 0 0 1rem 1rem !important;
            padding: 1.25rem !important;
        }

        .form-label {
            font-weight: 500 !important;
            color: #4b5563 !important;
            margin-bottom: 0.5rem !important;
        }

        

      

        .btn-sm {
            padding: 0.375rem 0.75rem !important;
            font-size: 0.875rem !important;
        }

        .main-content {
            padding: 2rem !important;
        }

      
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>
<?php include '../../includes/header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Purchase Orders</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </button>
        </div>

        <!-- Purchase Orders Table -->
        <div class="card">
            <div class="card-body">
                <table id="purchasesTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>PO-2024-001</td>
                            <td>Sample Supplier</td>
                            <td>2024-02-19</td>
                            <td>$1,500.00</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewPurchase('PO-2024-001')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editPurchase('PO-2024-001')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deletePurchase('PO-2024-001')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Purchase Order Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPurchaseForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="supplier" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier" name="supplier" required>
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-select" required>
                                                <option value="">Select Product</option>
                                            </select>
                                        </td>
                                        <td><input type="number" class="form-control" min="1" required></td>
                                        <td><input type="number" class="form-control" min="0" step="0.01" required></td>
                                        <td>$0.00</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addItem()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePurchase()">Create Purchase Order</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#purchasesTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25
    });
});

function addItem() {
    // Add new row to items table
    const newRow = `
        <tr>
            <td>
                <select class="form-select" required>
                    <option value="">Select Product</option>
                </select>
            </td>
            <td><input type="number" class="form-control" min="1" required></td>
            <td><input type="number" class="form-control" min="0" step="0.01" required></td>
            <td>$0.00</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#itemsTable tbody').append(newRow);
}

function removeItem(button) {
    $(button).closest('tr').remove();
}

function savePurchase() {
    // Implement save functionality
    showLoading('Creating purchase order...');
    // Add AJAX call here
}

function viewPurchase(poNumber) {
    // Implement view functionality
    showLoading('Loading purchase order details...');
    // Add AJAX call here
}

function editPurchase(poNumber) {
    // Implement edit functionality
    showLoading('Loading purchase order...');
    // Add AJAX call here
}

function deletePurchase(poNumber) {
    showConfirm('Are you sure you want to delete this purchase order?', function() {
        showLoading('Deleting purchase order...');
        // Add AJAX call here
    });
}
</script>

</body>
</html>