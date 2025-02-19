-- Get first available customer and user
SELECT @customer_id := customer_id FROM customers LIMIT 1;
SELECT @user_id := user_id FROM users LIMIT 1;

-- Get first two available products
SELECT @product_id1 := product_id, @price1 := unit_price FROM products LIMIT 1;
SELECT @product_id2 := product_id, @price2 := unit_price FROM products WHERE product_id != @product_id1 LIMIT 1;

-- Initialize variables
SET @qty1 = 2;
SET @qty2 = 1;
SET @subtotal1 = COALESCE(@qty1 * @price1, 0);
SET @subtotal2 = COALESCE(@qty2 * COALESCE(@price2, 0), 0);
SET @order_subtotal = @subtotal1 + @subtotal2;
SET @tax = ROUND(@order_subtotal * 0.10, 2);
SET @total = @order_subtotal + @tax;

-- Generate order number with timestamp
SET @order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s'));

-- Begin transaction
START TRANSACTION;

-- Insert order
INSERT INTO orders (
    customer_id,
    order_number,
    status,
    payment_method,
    payment_status,
    subtotal,
    tax,
    total,
    created_by,
    notes
) VALUES (
    @customer_id,
    @order_number,
    'processing',
    'Credit Card',
    'paid',
    @order_subtotal,
    @tax,
    @total,
    @user_id,
    'Sample order for testing'
);

-- Get the last inserted order ID
SET @last_order_id = LAST_INSERT_ID();

-- Insert order items
INSERT INTO order_items (
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
) VALUES
(@last_order_id, @product_id1, @qty1, @price1, @subtotal1);

-- Insert second item if it exists
INSERT INTO order_items (
    order_id,
    product_id,
    quantity,
    unit_price,
    subtotal
) 
SELECT 
    @last_order_id,
    @product_id2,
    @qty2,
    @price2,
    @subtotal2
WHERE @product_id2 IS NOT NULL;

-- Insert shipping information
INSERT INTO order_shipping (
    order_id,
    address,
    city,
    state,
    postal_code,
    country,
    shipping_method,
    shipping_cost,
    estimated_delivery
) VALUES (
    @last_order_id,
    '123 Main St',
    'New York',
    'NY',
    '10001',
    'United States',
    'Standard Shipping',
    15.00,
    DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)
);

-- Insert initial history record
INSERT INTO order_history (
    order_id,
    status,
    notes,
    created_by
) VALUES (
    @last_order_id,
    'processing',
    'Order created and processing',
    @user_id
);

-- Commit transaction
COMMIT; 