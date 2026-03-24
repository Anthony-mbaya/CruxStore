<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
/*
if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}
*/
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

$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category = ? 
    AND product_id != ?
    LIMIT 4
");
$stmt->execute([$product['category'], $product['product_id']]);
$relatedProducts = $stmt->fetchAll();

$content = '
<div class="container py-4 py-md-5">
    <div class="row g-4 align-items-start">

        <!-- IMAGE -->
        <div class="col-12 col-md-6 text-center">
            <img src="'.htmlspecialchars($product['image_url']).'"
                 class="img-fluid rounded-4 shadow-sm"
                 alt="'.htmlspecialchars($product['name']).'"
                 style="max-height: 400px; object-fit: cover;">
        </div>

        <!-- DETAILS -->
        <div class="col-12 col-md-6">
            <h3 class="fw-bold mb-2">'.htmlspecialchars($product['name']).'</h3>
            <p class="text-muted small mb-2">
                Category: '.htmlspecialchars($product['category']).'
            </p>
            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                <span class="fs-3 fw-bold text-primary">
                    KSh '.number_format($product['price'], 2).'
                </span>
                <span class="badge '.($product['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger').'">
                    '.($product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock').'
                </span>
            </div>
            <p class="text-muted">
                '.nl2br(htmlspecialchars($product['description'])).'
            </p>


            <!-- ACTIONS -->
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <a href="login.php" class="btn btn-outline-primary rounded-3">Login to proceed</a>
            </div>

        </div>
    </div>

    <!-- RELATED PRODUCTS -->
   '.(!empty($relatedProducts) ? '
<div class="container mt-5">
    <h5 class="fw-bold mb-3">Related Products</h5>
    <div class="row row-cols-1 row-cols-md-4 g-3">
        '.array_reduce($relatedProducts, function($carry, $item) {
            return $carry.'
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="'.htmlspecialchars($item['image_url']).'" 
                         class="card-img-top" 
                         style="height: 180px; object-fit: cover;">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1">'.htmlspecialchars($item['name']).'</h6>
                        <small class="text-muted">KSh '.number_format($item['price'], 2).'</small>
                    </div>
                    <div class="card-footer bg-white border-0 p-2">
                        <a href="product_details.php?id='.$item['product_id'].'" 
                           class="btn btn-sm btn-outline-primary w-100">
                           View
                        </a>
                    </div>
                </div>
            </div>';
        }, '').'
    </div>
</div>
' : '').'

</div> ';

include 'includes/main_template.php';
?>