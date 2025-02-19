<?php
require_once 'includes/header.php';

// Get wishlist items with product details
$wishlist_items = fetchAll(
    "SELECT w.*, p.*, c.name as category_name 
     FROM wishlist w 
     JOIN products p ON w.product_id = p.product_id 
     LEFT JOIN categories c ON p.category_id = c.category_id 
     WHERE w.user_id = ? 
     ORDER BY w.created_at DESC",
    [$_SESSION['user_id']]
);
?>

<div class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">My Wishlist</h1>
                <p class="text-muted">Manage your wishlist items and add them to cart.</p>
            </div>
        </div>

        <!-- Wishlist Items -->
        <div class="row">
            <?php if (empty($wishlist_items)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-heart text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">Your wishlist is empty</h5>
                            <p class="text-muted mb-4">Browse our products and add items to your wishlist.</p>
                            <a href="catalog.php" class="btn btn-primary">
                                Browse Products
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <!-- Product Image Placeholder -->
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm remove-btn"
                                            data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($item['category_name']); ?> | 
                                    SKU: <?php echo htmlspecialchars($item['sku']); ?>
                                </p>
                                <p class="card-text">
                                    <?php echo substr(htmlspecialchars($item['description']), 0, 100) . '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-primary mb-0">$<?php echo number_format($item['unit_price'], 2); ?></h5>
                                    <span class="badge bg-<?php echo $item['quantity_in_stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $item['quantity_in_stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <button type="button" 
                                        class="btn btn-primary w-100" 
                                        onclick="addToCart(<?php echo $item['product_id']; ?>)"
                                        <?php echo $item['quantity_in_stock'] <= 0 ? 'disabled' : ''; ?>>
                                    Add to Cart
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
    // Handle remove from wishlist
    $('.remove-btn').click(function() {
        const btn = $(this);
        const productId = btn.data('product-id');
        const card = btn.closest('.col-md-6');
        
        Swal.fire({
            title: 'Remove from Wishlist?',
            text: 'Are you sure you want to remove this item from your wishlist?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/toggle_wishlist.php',
                    method: 'POST',
                    data: { product_id: productId },
                    success: function(response) {
                        if (response.success) {
                            card.fadeOut(300, function() {
                                $(this).remove();
                                if ($('.card').length === 0) {
                                    location.reload();
                                }
                            });
                            
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
            }
        });
    });
});

function addToCart(productId) {
    // Implement cart functionality
    Swal.fire({
        icon: 'info',
        title: 'Coming Soon',
        text: 'Shopping cart functionality will be available soon!'
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 