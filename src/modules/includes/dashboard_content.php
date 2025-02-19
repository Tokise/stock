<?php
// Fetch some quick statistics (we'll implement these functions later)
$totalProducts = 0; // getTotalProducts();
$totalSales = 0; // getTotalSales();
$lowStock = 0; // getLowStockItems();
$monthlyRevenue = 0; // getMonthlyRevenue();
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <div class="user-info">
                <span class="me-2"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-dashboard bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Products</h6>
                                <h3 class="mb-0"><?php echo number_format($totalProducts); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Monthly Revenue</h6>
                                <h3 class="mb-0">$<?php echo number_format($monthlyRevenue, 2); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Sales</h6>
                                <h3 class="mb-0"><?php echo number_format($totalSales); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Low Stock Items</h6>
                                <h3 class="mb-0"><?php echo number_format($lowStock); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities and Charts -->
        <div class="row">
            <div class="col-md-8">
                <div class="card card-dashboard">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Sales Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-dashboard">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">New Sale</h6>
                                    <small>3 mins ago</small>
                                </div>
                                <p class="mb-1">Sale #1234 was completed</p>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Low Stock Alert</h6>
                                    <small>1 hour ago</small>
                                </div>
                                <p class="mb-1">Product XYZ is running low</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background-color: #eef2f7;
}
.main-content {
    margin-left: var(--sidebar-width);
    padding: 20px;
    background-color: #eef2f7;
}
.card-dashboard {
    border-radius: 15px;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    background-color: white;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card-dashboard:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}
.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}
.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    background-color: white !important;
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem;
}
.list-group-item {
    border: none;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1rem 1.25rem;
}
.list-group-item:last-child {
    border-bottom: none;
}
.card-title {
    color: #2c3e50;
    font-weight: 600;
}
h2 {
    color: #2c3e50;
    font-weight: 600;
}
</style> 