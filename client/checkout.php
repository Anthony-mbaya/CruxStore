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
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $notes = trim($_POST['notes']);
    $payment_method = $_POST['payment_method'];
    $mpesa_phone = $_POST['mpesa_phone'] ?? null;
    
    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders
            (customer_id, total_amount, delivery_address, delivery_latitude, delivery_longitude, notes )
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total, $delivery_address, $latitude, $longitude, $notes]);
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

        // Clear cart
        unset($_SESSION['cart']);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if ($payment_method === 'mpesa') {
            header("Location: ../payments/mpesa_pay.php?order_id=$order_id&phone=$mpesa_phone");
            exit();
        }

        //$_SESSION['message'] = "Order placed successfully! Your order ID is #$order_id";
        //$_SESSION['msg_type'] = "success";
        header("Location: order_confirmation.php?id=$order_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order failed: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM delivery_stations ORDER BY name ASC");
$stations = $stmt->fetchAll();
$stationOptions = '';

foreach ($stations as $station) {
    $stationOptions .= '
        <option 
            value="'.$station['station_id'].'"
            data-lat="'.$station['latitude'].'"
            data-lng="'.$station['longitude'].'"
            data-name="'.htmlspecialchars($station['name']).'"
        >
            '.htmlspecialchars($station['name']).'
        </option>
    ';
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
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="'.htmlspecialchars($customer['username']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="'.htmlspecialchars($customer['email']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" value="'.htmlspecialchars($customer['phone']).'" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Pickup Station</label>

                            <select class="form-select" id="stationSelect" name="station_id" required>
                                <option value="">-- Select Station --</option>
                                '.$stationOptions.'
                            </select>
                        </div>

                        <input type="hidden" name="delivery_address" id="delivery_address">
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="mb-3">
                            <label class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="4"></textarea>
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
                        <input class="form-check-input" type="radio" name="payment_method" value="mpesa" checked>
                        <label class="form-check-label">
                            <img src="../assets/images/payments/mpesa-logo.png" width="80"> (Kenya Only)
                        </label>
                        <div class="mt-2">
                            <input type="tel" name="mpesa_phone" class="form-control" placeholder="2547XXXXXXXX">
                        </div>
                    </div>
                    <!-- <button type="submit" class="btn btn-success mt-3 w-100">Complete Payment</button> -->
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
</div>

<script>
document.getElementById("stationSelect").addEventListener("change", function() {
    const selected = this.options[this.selectedIndex];

    document.getElementById("delivery_address").value = selected.dataset.name || "";
    document.getElementById("latitude").value = selected.dataset.lat || "";
    document.getElementById("longitude").value = selected.dataset.lng || "";
});
</script>
';

include '../includes/main_template.php';
?>