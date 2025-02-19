<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login/index.php");
    exit();
}

// Check if user has permission to view sales
requirePermission('view_sales');

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header("Location: ../index.php");
    exit();
}

// Fetch order details with customer and employee information
$order = fetchOne("
    SELECT o.*, 
           c.name as customer_name, 
           c.email as customer_email,
           c.phone as customer_phone,
           u.full_name as employee_name,
           u.email as employee_email
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN users u ON o.created_by = u.user_id
    WHERE o.order_id = ?
", [$order_id]);

if (!$order) {
    header("Location: ../index.php");
    exit();
}

// Fetch order items with product details
$order_items = fetchAll("
    SELECT oi.*, 
           p.name as product_name,
           p.sku,
           p.unit_price as current_price
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
", [$order_id]);

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['unit_price'];
}

$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?> - NexInvent</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .order-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .action-buttons .btn {
            margin-right: 0.5rem;
        }
        
        @media print {
            .sidebar, .action-buttons, .btn {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>
<?php include '../../includes/header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Order #<?php echo $order_id; ?></h2>
            <div class="action-buttons">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="exportPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <a href="../index.php" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Order Status -->
                <div class="order-info">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Order Status</h5>
                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($order['updated_at'])); ?></p>
                            <p class="mb-1"><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">$<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Tax (10%):</strong></td>
                                        <td class="text-end">$<?php echo number_format($tax, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Customer Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    </div>
                </div>

                <!-- Order Processing -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Processing</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Processed By:</strong> <?php echo htmlspecialchars($order['employee_name']); ?></p>
                        <p class="mb-1"><strong>Employee Email:</strong> <?php echo htmlspecialchars($order['employee_email']); ?></p>
                        <hr>
                        <div class="d-grid gap-2">
                            <?php if ($order['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success" onclick="updateOrderStatus('processing')">
                                    <i class="bi bi-check-circle"></i> Process Order
                                </button>
                            <?php elseif ($order['status'] === 'processing'): ?>
                                <button type="button" class="btn btn-success" onclick="updateOrderStatus('completed')">
                                    <i class="bi bi-check-circle"></i> Complete Order
                                </button>
                            <?php endif; ?>
                            <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                <button type="button" class="btn btn-danger" onclick="updateOrderStatus('cancelled')">
                                    <i class="bi bi-x-circle"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateOrderStatus(status) {
    if (!confirm('Are you sure you want to update the order status to ' + status + '?')) {
        return;
    }

    showLoading('Updating order status...');

    $.ajax({
        url: 'process_order.php',
        type: 'POST',
        data: {
            order_id: <?php echo $order_id; ?>,
            status: status,
            action: 'update_status'
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showSuccess('Order status updated successfully', function() {
                    location.reload();
                });
            } else {
                showError(response.error || 'Failed to update order status');
            }
        },
        error: function() {
            hideLoading();
            showError('Failed to update order status');
        }
    });
}

function exportPDF() {
    showLoading('Generating PDF...');
    window.location.href = 'export_pdf.php?id=<?php echo $order_id; ?>';
    setTimeout(hideLoading, 1000);
}
</script>

</body>
</html> 