<?php
session_start();
require_once '../config/db.php';
require_once '../includes/permissions.php';

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
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
<?php include '../includes/header.php'; ?>

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

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#purchasesTable').DataTable({
        "order": [[2, "desc"]],
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