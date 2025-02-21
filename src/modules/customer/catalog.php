<?php
require_once 'includes/header.php';

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Build query
$query = "SELECT p.*, c.name as category_name, 
          CASE WHEN w.wishlist_id IS NOT NULL THEN 1 ELSE 0 END as is_wishlisted
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN wishlist w ON w.product_id = p.product_id AND w.user_id = ?
          WHERE p.quantity_in_stock > 0";
$params = [$_SESSION['user_id']];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($category) {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
}

// Add sorting
$query .= match($sort) {
    'price_asc' => " ORDER BY p.unit_price ASC",
    'price_desc' => " ORDER BY p.unit_price DESC",
    'name_desc' => " ORDER BY p.name DESC",
    default => " ORDER BY p.name ASC"
};

$products = fetchAll($query, $params);
?>

<div class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Product Catalog</h1>
                <p class="text-muted">Browse our products and add them to your wishlist.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search products...">
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort" class="form-label">Sort By</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No products found matching your criteria.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card h-100">
                            <!-- Product Image Placeholder -->
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($product['category_name']); ?> | 
                                    SKU: <?php echo htmlspecialchars($product['sku']); ?>
                                </p>
                                <p class="card-text">
                                    <?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-primary mb-0">$<?php echo number_format($product['unit_price'], 2); ?></h5>
                                    <button type="button" 
                                            class="btn btn-outline-<?php echo $product['is_wishlisted'] ? 'danger' : 'secondary'; ?> btn-sm wishlist-btn"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-is-wishlisted="<?php echo $product['is_wishlisted']; ?>">
                                        <i class="bi bi-heart<?php echo $product['is_wishlisted'] ? '-fill' : ''; ?>"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <button type="button" class="btn btn-primary w-100 mb-2" 
                                        onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                    Add to Cart
                                </button>
                                <button type="button" class="btn btn-success w-100" 
                                        onclick="buyNow(<?php echo $product['product_id']; ?>)">
                                    Buy Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle wishlist toggle
    $('.wishlist-btn').click(function() {
        const btn = $(this);
        const productId = btn.data('product-id');
        const isWishlisted = btn.data('is-wishlisted');
        
        $.ajax({
            url: 'ajax/toggle_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            success: function(response) {
                if (response.success) {
                    if (isWishlisted) {
                        btn.removeClass('btn-outline-danger').addClass('btn-outline-secondary');
                        btn.find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                        btn.data('is-wishlisted', 0);
                    } else {
                        btn.removeClass('btn-outline-secondary').addClass('btn-outline-danger');
                        btn.find('i').removeClass('bi-heart').addClass('bi-heart-fill');
                        btn.data('is-wishlisted', 1);
                    }
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong! Please try again.'
                });
            }
        });
    });
});

function buyNow(productId) {
    addToCart(productId, 1, function() {
        window.location.href = 'checkout.php';
    });
}

function addToCart(productId, quantity = 1, callback = null) {
    $.ajax({
        url: 'ajax/cart_handler.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'add',
            product_id: productId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                updateCartBadge(response.cart_count);
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    if (callback) callback();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Failed to add item to cart'
                });
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to add item to cart';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }
    });
}

function updateCartBadge(count) {
    const badge = $('.cart-badge');
    if (count > 0) {
        badge.text(count).removeClass('d-none');
    } else {
        badge.addClass('d-none');
    }
}

// Initialize cart badge on page load
$(document).ready(function() {
    $.get('ajax/cart_handler.php', { action: 'get' }, function(response) {
        if (response.success) {
            updateCartBadge(response.count);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>