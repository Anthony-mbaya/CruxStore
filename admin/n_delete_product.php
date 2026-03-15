<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details (for image deletion)
$stmt = $pdo->prepare("SELECT image_url FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if ($product) {
    // Delete product image if it exists
    if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
        unlink('../' . $product['image_url']);
    }
    
    // Delete product from database
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    if ($stmt->execute([$product_id])) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete product.";
        $_SESSION['msg_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['msg_type'] = "danger";
}

header("Location: products.php");
exit();
?>