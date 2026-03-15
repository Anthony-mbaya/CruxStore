<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['message'] = "Your cart is empty!";
    $_SESSION['msg_type'] = "danger";
    header("Location: cart.php");
    exit();
}

$pageTitle = "Checkout";

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

// Get cart items
$cartItems = [];
$total = 0;

$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id IN ($placeholders)");
$stmt->execute(array_keys($_SESSION['cart']));
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['product_id']];
    $subtotal = $product['price'] * $quantity;
    $total += $subtotal;

    $cartItems[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $notes = trim($_POST['notes']);
    $payment_method = $_POST['payment_method'];
    $mpesa_phone = $_POST['mpesa_phone'] ?? null;

    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders
            (customer_id, total_amount, delivery_address, notes)
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total, $delivery_address, $notes]);
        $order_id = $pdo->lastInsertId();

        // Add order items
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items
                (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $order_id,
                $item['product']['product_id'],
                $item['quantity'],
                $item['product']['price']
            ]);

            // Update stock
            $stmt = $pdo->prepare("UPDATE products
                SET stock_quantity = stock_quantity - ?
                WHERE product_id = ?");
            $stmt->execute([$item['quantity'], $item['product']['product_id']]);
        }

        $pdo->commit();

        if ($payment_method === 'mpesa') {
            header("Location: ../payments/mpesa_pay.php?order_id=$order_id&phone=$mpesa_phone");
            exit();
        }

        // Clear cart
        unset($_SESSION['cart']);

        $_SESSION['message'] = "Order placed successfully! Your order ID is #$order_id";
        $_SESSION['msg_type'] = "success";
        header("Location: order_confirmation.php?id=$order_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order failed: " . $e->getMessage();
    }
}

$content = '
<div class="container py-3">
    <div class="row">
        <!-- Delivery Information Column -->
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5>Delivery Information</h5>
                </div>
                <div class="card-body">
                    '.((isset($error)) ? '<div class="alert alert-danger">'.$error.'</div>' : '').'
                    <form method="POST" action="../client/checkout.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" value="'.htmlspecialchars($customer['username']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="'.htmlspecialchars($customer['email']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" value="'.htmlspecialchars($customer['phone']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="6" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                        </div>
                </div>
            </div>
        </div>

        <!-- Payment Info & Order Summary Column -->
        <div class="col-md-4">
            <!-- Payment Info -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5>Select Payment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="mpesa" value="mpesa" checked>
                        <label class="form-check-label" for="mpesa">
                            <img src="../assets/images/payments/mpesa-logo.png" width="80"> (Kenya Only)
                        </label>
                        <div id="mpesa-details" class="mt-2">
                            <input type="tel" name="mpesa_phone" class="form-control" placeholder="2547XXXXXXXX">
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                        <label class="form-check-label" for="paypal">
                            <img src="../assets/images/payments/paypal-logo.png" width="80"> (International)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success mt-3 w-100">Complete Payment</button>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5>Order Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        '.array_reduce($cartItems, function($carry, $item) {
                            return $carry.'
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                '.htmlspecialchars($item['product']['name']).'
                                <span class="badge bg-primary rounded-pill">'.$item['quantity'].'</span>
                            </li>';
                        }, '').'
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>KSh '.number_format($total, 2).'</strong>
                        </li>
                    </ul>

                    <div class="d-grid gap-2">
                        <button type="submit" name="place_order" class="btn btn-primary w-100">Place Order</button>
                        <a href="cart.php" class="btn btn-outline-secondary w-100">Back to Cart</a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';


include '../includes/main_template.php';
?>