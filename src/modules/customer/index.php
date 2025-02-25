<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Check if user is a customer
if ($_SESSION['role'] !== 'customer') {
    header("Location: ../../login/index.php");
    exit();
}

// Check if customer_id is set
if (!isset($_SESSION['customer_id'])) {
    // Try to get customer_id from database
    $customer = fetchOne(
        "SELECT customer_id FROM customers WHERE email = (SELECT email FROM users WHERE user_id = ?)",
        [$_SESSION['user_id']]
    );
    
    if ($customer) {
        $_SESSION['customer_id'] = $customer['customer_id'];
    } else {
        // Create a new customer record
        $user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
        $customer_data = [
            'name' => $user['full_name'],
            'email' => $user['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['customer_id'] = insert('customers', $customer_data);
    }
}

// Get recent orders
$recent_orders = fetchAll(
    "SELECT so.*, COUNT(soi.order_item_id) as item_count 
     FROM sales_orders so 
     LEFT JOIN sales_order_items soi ON so.sale_id = soi.sale_id 
     WHERE so.customer_id = ? 
     GROUP BY so.sale_id 
     ORDER BY so.created_at DESC 
     LIMIT 5",
    [$_SESSION['customer_id']]
);

// Get wishlist count
$wishlist_count = fetchValue(
    "SELECT COUNT(*) FROM wishlist WHERE user_id = ?",
    [$_SESSION['user_id']]
);

// Get total orders
$total_orders = fetchValue(
    "SELECT COUNT(*) FROM sales_orders WHERE customer_id = ?",
    [$_SESSION['customer_id']]
);

// Get total spent
$total_spent = fetchValue(
    "SELECT COALESCE(SUM(grand_total), 0) FROM sales_orders WHERE customer_id = ? AND status != 'cancelled'",
    [$_SESSION['customer_id']]
);

require_once 'includes/header.php';
?>

<div class="container">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋</h1>
            <p class="text-muted">Here's what's happening with your account.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-bag-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Spent</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_spent, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Wishlist Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $wishlist_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-heart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                echo fetchValue(
                                    "SELECT COUNT(*) FROM sales_orders WHERE customer_id = ? AND status IN ('confirmed', 'processing', 'shipped')",
                                    [$_SESSION['customer_id']]
                                ); 
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-center text-muted my-4">No orders yet. Start shopping!</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['sale_id']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo $order['item_count']; ?> items</td>
                                            <td>$<?php echo number_format($order['grand_total'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['status']) {
                                                        'draft' => 'secondary',
                                                        'confirmed' => 'primary',
                                                        'processing' => 'info',
                                                        'shipped' => 'warning',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_order.php?id=<?php echo $order['sale_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <a href="catalog.php" class="btn btn-lg btn-outline-primary w-100">
                                <i class="bi bi-shop mb-2"></i><br>
                                Browse Products
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="wishlist.php" class="btn btn-lg btn-outline-danger w-100">
                                <i class="bi bi-heart mb-2"></i><br>
                                View Wishlist
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="profile.php" class="btn btn-lg btn-outline-info w-100">
                                <i class="bi bi-person mb-2"></i><br>
                                Update Profile
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="orders.php" class="btn btn-lg btn-outline-success w-100">
                                <i class="bi bi-bag mb-2"></i><br>
                                Track Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 