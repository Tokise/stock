<?php
require_once '../../includes/permissions.php';

// Fetch statistics based on user permissions
$totalProducts = 0;
$totalSales = 0;
$lowStock = 0;
$monthlyRevenue = 0;

// Get database connection
$conn = getDBConnection();

// Get total products
if (hasPermission('view_products')) {
    $sql = "SELECT COUNT(*) FROM products";
    $totalProducts = fetchValue($sql);
}

// Get total sales
if (hasPermission('view_sales')) {
    $sql = "SELECT COUNT(*) FROM sales_orders";
    $totalSales = fetchValue($sql);
}

// Get monthly revenue
if (hasPermission('view_sales')) {
    $currentMonth = date('Y-m');
    $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM sales_orders 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
    $monthlyRevenue = fetchValue($sql, [$currentMonth]);
}

// Get low stock items
if (hasPermission('view_inventory')) {
    $sql = "SELECT COUNT(*) FROM products 
            WHERE quantity_in_stock <= reorder_level";
    $lowStock = fetchValue($sql);
}

// Get recent activities (last 10)
$recentActivities = [];

// Recent product additions
if (hasPermission('view_products')) {
    $sql = "SELECT 'product' as type, product_id as id, name as title, 
            'New product added' as description, created_at, 
            created_by as user_id, 
            (SELECT full_name FROM users WHERE user_id = products.created_by) as user_name
            FROM products 
            ORDER BY created_at DESC LIMIT 5";
    $recentProducts = fetchAll($sql);
    $recentActivities = array_merge($recentActivities, $recentProducts);
}

// Recent sales
if (hasPermission('view_sales')) {
    $sql = "SELECT 'sale' as type, sale_id as id, 
            CONCAT('Order #', sale_id) as title,
            CONCAT('Sale completed for $', total_amount) as description, 
            created_at,
            user_id as user_id,
            (SELECT full_name FROM users WHERE user_id = sales_orders.user_id) as user_name
            FROM sales_orders 
            ORDER BY created_at DESC LIMIT 5";
    $recentSales = fetchAll($sql);
    $recentActivities = array_merge($recentActivities, $recentSales);
}

// Recent stock movements
if (hasPermission('view_inventory')) {
    $sql = "SELECT 'movement' as type, sm.movement_id as id, 
            CONCAT(p.name, ' stock ', IF(sm.quantity > 0, 'increased', 'decreased')) as title,
            CONCAT(ABS(sm.quantity), ' units ', IF(sm.quantity > 0, 'added to', 'removed from'), ' inventory') as description,
            sm.created_at,
            sm.user_id as user_id,
            (SELECT full_name FROM users WHERE user_id = sm.user_id) as user_name
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.product_id
            ORDER BY sm.created_at DESC LIMIT 5";
    $recentMovements = fetchAll($sql);
    $recentActivities = array_merge($recentActivities, $recentMovements);
}

// Recent employee additions
if (hasPermission('view_employees')) {
    $sql = "SELECT 'employee' as type, u.user_id as id, 
            u.full_name as title, 
            CONCAT('New ', u.role, ' added') as description, 
            u.created_at,
            IFNULL(u.created_by, 0) as user_id,
            IFNULL((SELECT full_name FROM users WHERE user_id = u.created_by), 'System') as user_name
            FROM users u
            ORDER BY u.created_at DESC LIMIT 5";
    $recentEmployees = fetchAll($sql);
    $recentActivities = array_merge($recentActivities, $recentEmployees);
}

// Sort all activities by date (newest first)
usort($recentActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 7 most recent activities
$recentActivities = array_slice($recentActivities, 0, 7);

// Get sales data for chart (last 6 months)
$salesChartData = [];
$salesChartLabels = [];

if (hasPermission('view_sales')) {
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M', strtotime("-$i months"));
        
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM sales_orders 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        $monthlySales = fetchValue($sql, [$month]);
        
        $salesChartData[] = $monthlySales;
        $salesChartLabels[] = $monthName;
    }
}

// Get product categories data for pie chart
$categoriesData = [];
$categoriesLabels = [];

if (hasPermission('view_products')) {
    $sql = "SELECT c.name, COUNT(p.product_id) as count
            FROM categories c
            LEFT JOIN products p ON c.category_id = p.category_id
            GROUP BY c.category_id
            ORDER BY count DESC
            LIMIT 5";
    $categories = fetchAll($sql);
    
    foreach ($categories as $category) {
        $categoriesLabels[] = $category['name'];
        $categoriesData[] = $category['count'];
    }
}

// Get recently added products
$recentlyAddedProducts = [];
if (hasPermission('view_products')) {
    $sql = "SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            ORDER BY p.created_at DESC
            LIMIT 5";
    $recentlyAddedProducts = fetchAll($sql);
}
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <div class="user-info">
                <span class="me-2"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <?php if (hasPermission('view_products')): ?>
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
            <?php endif; ?>
            
            <?php if (hasPermission('view_sales')): ?>
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
            <?php endif; ?>
            
            <?php if (hasPermission('view_inventory')): ?>
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
            <?php endif; ?>
        </div>

        <!-- Charts and Recent Activities -->
        <div class="row g-4">
            <?php if (hasPermission('view_sales')): ?>
            <div class="col-md-8">
                <div class="card card-dashboard mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Sales Overview (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="300"></canvas>
                    </div>
                </div>
                
                <?php if (hasPermission('view_products')): ?>
                <div class="card card-dashboard">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Product Categories</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoriesChart" height="250"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="col-md-4">
                <div class="card card-dashboard mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="activityFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person me-1"></i> <span id="currentFilter">All Users</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="activityFilterDropdown">
                                <li><a class="dropdown-item active" href="#" data-filter="all">All Users</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="mine">My Activities</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-2 text-muted">No recent activities found</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php 
                                // Make a copy of the activities array for filtering
                                $filteredActivities = $recentActivities;
                                
                                // Group activities by date
                                $groupedActivities = [];
                                foreach ($filteredActivities as $activity) {
                                    $date = date('Y-m-d', strtotime($activity['created_at']));
                                    if (!isset($groupedActivities[$date])) {
                                        $groupedActivities[$date] = [];
                                    }
                                    $groupedActivities[$date][] = $activity;
                                }
                                
                                // Display activities grouped by date
                                foreach ($groupedActivities as $date => $activities):
                                    // Determine date label
                                    $dateLabel = 'Today';
                                    if ($date == date('Y-m-d', strtotime('-1 day'))) {
                                        $dateLabel = 'Yesterday';
                                    } elseif ($date != date('Y-m-d')) {
                                        $dateLabel = date('F j, Y', strtotime($date));
                                    }
                                ?>
                                    <div class="activity-date px-3 py-2 bg-light">
                                        <span class="text-muted"><?php echo $dateLabel; ?></span>
                                    </div>
                                    
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="activity-item px-3 py-2 border-bottom" 
                                             data-user-id="<?php echo $activity['user_id']; ?>" 
                                             data-current-user="<?php echo $_SESSION['user_id']; ?>">
                                            <div class="d-flex">
                                                <?php
                                                // Determine icon and color based on activity type
                                                $iconClass = 'bi-box';
                                                $colorClass = 'primary';
                                                
                                                if (strpos($activity['description'], 'product') !== false) {
                                                    $iconClass = 'bi-box';
                                                    $colorClass = 'primary';
                                                } elseif (strpos($activity['description'], 'sale') !== false || strpos($activity['description'], 'order') !== false) {
                                                    $iconClass = 'bi-cart';
                                                    $colorClass = 'success';
                                                } elseif (strpos($activity['description'], 'stock') !== false || strpos($activity['description'], 'inventory') !== false) {
                                                    $iconClass = 'bi-arrow-left-right';
                                                    $colorClass = 'warning';
                                                } elseif (strpos($activity['description'], 'employee') !== false || strpos($activity['description'], 'user') !== false) {
                                                    $iconClass = 'bi-person';
                                                    $colorClass = 'info';
                                                }
                                                ?>
                                                <div class="activity-icon bg-light-<?php echo $colorClass; ?> text-<?php echo $colorClass; ?> me-3">
                                                    <i class="bi <?php echo $iconClass; ?>"></i>
                                                </div>
                                                <div class="activity-content">
                                                    <div class="d-flex justify-content-between">
                                                        <p class="mb-0 text-dark"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($activity['created_at'])); ?></small>
                                                    </div>
                                                    <small class="text-muted">by <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                
                                <div class="text-center py-2">
                                    <a href="#" class="view-all text-decoration-none">View All Activities</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (hasPermission('view_products') && !empty($recentlyAddedProducts)): ?>
                <div class="card card-dashboard">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Recently Added Products</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentlyAddedProducts as $product): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </div>
                                    <p class="mb-1">
                                        <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?> | 
                                        <strong>Price:</strong> $<?php echo number_format($product['unit_price'], 2); ?> | 
                                        <strong>Stock:</strong> <?php echo $product['quantity_in_stock']; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $time);
    }
}
?>

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
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}
.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}
.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 15px 20px;
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

/* Timeline styles */
.activity-timeline {
    position: relative;
}

.activity-date {
    font-size: 0.85rem;
    font-weight: 500;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.activity-item {
    position: relative;
    transition: background-color 0.2s ease;
}

.activity-item:hover {
    background-color: rgba(0,0,0,0.01);
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Light background colors for icons */
.bg-light-primary {
    background-color: rgba(13, 110, 253, 0.1);
}

.bg-light-success {
    background-color: rgba(25, 135, 84, 0.1);
}

.bg-light-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.bg-light-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

.bg-light-info {
    background-color: rgba(13, 202, 240, 0.1);
}

.activity-content {
    flex-grow: 1;
    min-width: 0;
}

.view-all {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--bs-primary);
}

.view-all:hover {
    text-decoration: underline !important;
}

.no-activities-message {
    padding: 2rem 0;
}

/* Remove old timeline styles */
.timeline-container,
.timeline-item,
.timeline-dot,
.timeline-line,
.timeline-content {
    /* These styles will be removed */
}
</style> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity filter functionality
    const filterLinks = document.querySelectorAll('.dropdown-menu .dropdown-item');
    const currentFilterText = document.getElementById('currentFilter');
    const activityItems = document.querySelectorAll('.activity-item');
    
    filterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state
            filterLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Update dropdown button text
            currentFilterText.textContent = this.textContent;
            
            // Filter activities
            const filterType = this.getAttribute('data-filter');
            const currentUserId = '<?php echo $_SESSION['user_id']; ?>';
            
            activityItems.forEach(item => {
                if (filterType === 'all') {
                    item.style.display = '';
                } else if (filterType === 'mine') {
                    const itemUserId = item.getAttribute('data-user-id');
                    item.style.display = (itemUserId === currentUserId) ? '' : 'none';
                }
            });
            
            // Check if any activities are visible in each date group
            document.querySelectorAll('.activity-date').forEach(dateHeader => {
                let hasVisibleActivities = false;
                let nextElement = dateHeader.nextElementSibling;
                
                // Check all activities until the next date header
                while (nextElement && !nextElement.classList.contains('activity-date')) {
                    if (nextElement.classList.contains('activity-item') && 
                        nextElement.style.display !== 'none') {
                        hasVisibleActivities = true;
                        break;
                    }
                    nextElement = nextElement.nextElementSibling;
                }
                
                // Hide date header if no visible activities
                dateHeader.style.display = hasVisibleActivities ? '' : 'none';
            });
            
            // Show "no activities" message if all are filtered out
            const noActivitiesMessage = document.querySelector('.card-body .text-center');
            const hasVisibleActivities = Array.from(activityItems).some(item => item.style.display !== 'none');
            
            if (!hasVisibleActivities && filterType === 'mine') {
                // Create message if it doesn't exist
                if (!noActivitiesMessage) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'text-center py-4 no-activities-message';
                    messageDiv.innerHTML = `
                        <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                        <p class="mt-2 text-muted">No activities found for you</p>
                    `;
                    document.querySelector('.activity-timeline').appendChild(messageDiv);
                } else if (noActivitiesMessage.classList.contains('no-activities-message')) {
                    noActivitiesMessage.style.display = '';
                }
            } else {
                // Hide message if it exists
                document.querySelectorAll('.no-activities-message').forEach(msg => {
                    msg.style.display = 'none';
                });
            }
        });
    });
});
</script> 