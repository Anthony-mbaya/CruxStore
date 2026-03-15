<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

// Verify the required parameter
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Validate the product exists
    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && isset($_SESSION['cart'][$product_id])) {
        // Remove the item from cart
        unset($_SESSION['cart'][$product_id]);

        $_SESSION['message'] = "Item removed from cart successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Invalid product or item not in cart!";
        $_SESSION['msg_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Invalid request!";
    $_SESSION['msg_type'] = "danger";
}

// Redirect back to cart page
header("Location: cart.php");
exit();
?>