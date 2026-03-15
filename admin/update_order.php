<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Update Order";
$error = '';
$success = '';

// Get order ID
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order
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

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {

    $status = trim($_POST['status']);
    $payment_status = trim($_POST['payment_status']);

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = ?, payment_status = ?
        WHERE order_id = ?
    ");

    if ($stmt->execute([$status, $payment_status, $order_id])) {
        $_SESSION['message'] = "Order updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: orders_page.php");
        exit();
    } else {
        $error = "Failed to update order.";
    }
}

$content = '
<div class="container">
    <h2 class="my-4">Update Order</h2>

    <div class="card">
        <div class="card-header">
            <h5>Update Order Status</h5>
        </div>

        <div class="card-body">

            ' . ($error ? '<div class="alert alert-danger">'.$error.'</div>' : '') . '

            <p><strong>Order ID:</strong> #' . $order['order_id'] . '</p>
            <p><strong>Customer:</strong> ' . htmlspecialchars($order['username']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Order Status</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" '.($order['status']=='pending'?'selected':'').'>Pending</option>
                        <option value="processing" '.($order['status']=='processing'?'selected':'').'>Processing</option>
                        <option value="completed" '.($order['status']=='completed'?'selected':'').'>Completed</option>
                        <option value="cancelled" '.($order['status']=='cancelled'?'selected':'').'>Cancelled</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-control" required>
                        <option value="pending" '.($order['payment_status']=='pending'?'selected':'').'>Pending</option>
                        <option value="partial" '.($order['payment_status']=='partial'?'selected':'').'>Partial</option>
                        <option value="paid" '.($order['payment_status']=='paid'?'selected':'').'>Paid</option>
                    </select>
                </div>

                <button type="submit" name="update_order" class="btn btn-primary">
                    Update Order
                </button>

                <a href="orders_page.php" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>