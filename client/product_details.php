<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$product_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['msg_type'] = "danger";
    header("Location: products.php");
    exit();
}

$pageTitle = $product['name'];

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = $_POST['quantity'] ?? 1;

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add/update item in cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

     $stmt = $pdo->prepare("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    $stmt->execute([$_SESSION['user_id'], $product_id]);

    $_SESSION['message'] = "Product added to cart!";
    $_SESSION['msg_type'] = "success";
    header("Location: cart.php");
    exit();
}

$content = '
<div class="container py-5">
    <div class="row">
        <div class="col-md-6 d-flex justify-content-center">
            <img src="../'.htmlspecialchars($product['image_url']).'"
            class="img-fluid rounded shadow-lg"
            alt="'.htmlspecialchars($product['name']).'"
            style="max-width: 100%; height: 400px; object-fit: cover;" >
        </div>

        <div class="col-md-6">
            <h2>'.htmlspecialchars($product['name']).'</h2>
            <p class="text-muted">Category: '.htmlspecialchars($product['category']).'</p>
            <div class="d-flex align-items-center mb-3">
                <span class="h3 me-3">KSh '.number_format($product['price'], 2).'</span>
                <span class="badge '.($product['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger').'">
                    '.($product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock').'
                </span>
            </div>

            <p>'.nl2br(htmlspecialchars($product['description'])).'</p>

            <form method="POST" class="mt-4">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="quantity" class="col-form-label">Quantity:</label>
                    </div>
                    <div class="col-auto">
                        <input type="number" id="quantity" name="quantity" min="1" max="'.min($product['stock_quantity'], 10).'" value="1" class="form-control">
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="add_to_cart" class="btn btn-primary" '.($product['stock_quantity'] <= 0 ? 'disabled' : '').'>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-4">
                <a href="client_home.php" class="btn btn-outline-secondary">Back to Products</a>
                <a href="cart.php" class="btn btn-outline-primary ms-2">View Cart</a>
            </div>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>