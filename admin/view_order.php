<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "View Order";

// Get order ID
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order info
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['message'] = "Order not found!";
    $_SESSION['msg_type'] = "danger";
    header("Location: orders_page.php");
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Order Details</h2>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Order Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Order ID:</strong> #' . $order['order_id'] . '</p>
            <p><strong>Customer:</strong> ' . htmlspecialchars($order['username']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
            <p><strong>Date:</strong> ' . date('M j, Y', strtotime($order['order_date'])) . '</p>
            <p><strong>Status:</strong> ' . ucfirst($order['status']) . '</p>
            <p><strong>Payment Status:</strong> ' . ucfirst($order['payment_status']) . '</p>
            <p><strong>Total Amount:</strong> ' . number_format($order['total_amount'], 2) . '</p>
        </div>
    </div>


            <a href="orders_page.php" class="btn btn-secondary mt-3">Back to Orders</a>
        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>