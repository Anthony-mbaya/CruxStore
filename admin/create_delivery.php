<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Create Delivery";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_delivery'])) {

    $order_id = $_POST['order_id'];
    $deliverer_id = !empty($_POST['deliverer_id']) ? $_POST['deliverer_id'] : NULL;
    $estimated_delivery_time = !empty($_POST['estimated_delivery_time']) ? $_POST['estimated_delivery_time'] : NULL;

    $status = $deliverer_id ? "assigned" : "pending";

    $stmt = $pdo->prepare("
        INSERT INTO deliveries 
        (order_id, deliverer_id, status, estimated_delivery_time, created_at, updated_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    if ($stmt->execute([$order_id, $deliverer_id, $status, $estimated_delivery_time])) {
        header("Location: deliveries.php?created=1");
        exit();
    } else {
        $error = "Failed to create delivery.";
    }
}

// Fetch orders without deliveries
$orders = $pdo->query("
    SELECT o.order_id, o.total_amount, u.username
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    LEFT JOIN deliveries d ON o.order_id = d.order_id
    WHERE d.delivery_id IS NULL
    ORDER BY o.order_date DESC
")->fetchAll();

// Fetch available deliverers
$deliverers = $pdo->query("
    SELECT del.deliverer_id, u.username, del.vehicle_type
    FROM deliverers del
    JOIN users u ON del.user_id = u.user_id
    WHERE del.is_active = 1
    ORDER BY u.username
")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Create Delivery</h2>

    <div class="card">
        <div class="card-header">
            <h5>New Delivery</h5>
        </div>

        <div class="card-body">

            ' . ($error ? '<div class="alert alert-danger">'.$error.'</div>' : '') . '

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Select Order</label>
                    <select name="order_id" class="form-select" required>
                        <option value="">Choose order...</option>
                        ' . array_reduce($orders, function($carry, $order) {
                            return $carry . '<option value="'.$order['order_id'].'">
                                Order #'.$order['order_id'].' - Ksh.'.number_format($order['total_amount'],2).' ('.
                                htmlspecialchars($order['username']).')
                            </option>';
                        }, '') . '
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign Deliverer (Optional)</label>
                    <select name="deliverer_id" class="form-select">
                        <option value="">Unassigned</option>
                        ' . array_reduce($deliverers, function($carry, $d) {
                            return $carry . '<option value="'.$d['deliverer_id'].'">'.
                                htmlspecialchars($d['username']).' - '.htmlspecialchars($d['vehicle_type']).'
                            </option>';
                        }, '') . '
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Estimated Delivery Time</label>
                    <input type="datetime-local" name="estimated_delivery_time" class="form-control">
                </div>

                <button type="submit" name="create_delivery" class="btn btn-success">
                    Create Delivery
                </button>

                <a href="deliveries.php" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>