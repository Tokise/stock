<?php
session_start();
require_once '../../config/db.php';
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../login/index.php");
    exit();
}

// Fetch cart items
$sql = "SELECT sc.*, p.name, p.sku, p.unit_price, p.quantity_in_stock,
               (sc.quantity * p.unit_price) as subtotal
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.product_id
        WHERE sc.user_id = ?";

$cart_items = fetchAll($sql, [$_SESSION['user_id']]);
?>

<div class="main-content">
    <div class="container">
        <h2 class="mb-4">Shopping Cart</h2>

        <?php if (empty($cart_items)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Your cart is empty</h4>
                    <p class="text-muted">Browse our products and add items to your cart.</p>
                    <a href="catalog.php" class="btn btn-primary">
                        Browse Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm quantity-input" 
                                                           value="<?php echo $item['quantity']; ?>"
                                                           min="1"
                                                           max="<?php echo $item['quantity_in_stock']; ?>"
                                                           data-product-id="<?php echo $item['product_id']; ?>"
                                                           style="width: 80px;">
                                                </td>
                                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger remove-item"
                                                            data-product-id="<?php echo $item['product_id']; ?>">
                                                        <i class="bi bi-trash"></i>
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
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span class="cart-subtotal">$0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong class="cart-total">$0.00</strong>
                            </div>
                            <button class="btn btn-primary w-100" onclick="checkout()">
                                Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    updateCartTotals();

    // Handle quantity changes
    $('.quantity-input').change(function() {
        const productId = $(this).data('product-id');
        const quantity = $(this).val();
        
        updateCartItem(productId, quantity);
    });

    // Handle remove item
    $('.remove-item').click(function() {
        const productId = $(this).data('product-id');
        removeCartItem(productId);
    });
});

function updateCartItem(productId, quantity) {
    $.ajax({
        url: 'ajax/cart_handler.php',
        method: 'POST',
        data: {
            action: 'update',
            product_id: productId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function removeCartItem(productId) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'Are you sure you want to remove this item?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Remove'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/cart_handler.php',
                method: 'POST',
                data: {
                    action: 'remove',
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
}

function updateCartTotals() {
    $.get('ajax/cart_handler.php', { action: 'get' }, function(response) {
        if (response.success) {
            $('.cart-subtotal').text('$' + response.total.toFixed(2));
            $('.cart-total').text('$' + response.total.toFixed(2));
        }
    });
}

function checkout() {
    window.location.href = 'checkout.php';
}
</script>
