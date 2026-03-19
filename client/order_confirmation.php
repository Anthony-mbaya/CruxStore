<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['message'] = "Order not found!";
    $_SESSION['msg_type'] = "danger";
    header("Location: orders.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$pageTitle = "Order Confirmation #$order_id";

$content = '
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 text-success mb-3">Thank You!</h1>
        <p class="lead">Your order has been placed successfully.</p>
        <p>Your order ID is: <strong>#'.$order_id.'</strong></p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order Date:</strong> '.date('F j, Y \a\t g:i a', strtotime($order['order_date'])).'</p>
                    <p><strong>Status:</strong> <span class="badge bg-primary">'.ucfirst($order['status']).'</span></p>
                    <p><strong>Total Amount:</strong> KSh '.number_format($order['total_amount'], 2).'</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Delivery Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> '.htmlspecialchars($order['username']).'</p>
                    <p><strong>Email:</strong> '.htmlspecialchars($order['email']).'</p>
                    <p><strong>Phone:</strong> '.htmlspecialchars($order['phone']).'</p>
                    <p><strong>Delivery Address:</strong><br>'.nl2br(htmlspecialchars($order['delivery_address'])).'</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Order Items</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        '.array_reduce($items, function($carry, $item) {
                            return $carry.'
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <img src="../'.htmlspecialchars($item['image_url']).'" alt="Product image" width="60" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">'.htmlspecialchars($item['name']).'</h6>
                                        <small>'.htmlspecialchars($item['quantity']).' x KSh '.number_format($item['unit_price'], 2).'</small>
                                    </div>
                                    <div class="text-end">
                                        <strong>KSh '.number_format($item['quantity'] * $item['unit_price'], 2).'</strong>
                                    </div>
                                </div>
                            </li>';
                        }, '').'
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>KSh '.number_format($order['total_amount'], 2).'</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="client_home.php" class="btn btn-primary">Continue Shopping</a>
        <a href="orders.php" class="btn btn-outline-secondary ms-2">View Your Orders</a>
    </div>
</div>';

include '../includes/main_template.php';
?>