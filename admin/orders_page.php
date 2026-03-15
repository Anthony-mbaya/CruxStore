<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Order Management";

// Fetch all orders with customer info
$orders = $pdo->query("
    SELECT o.*, u.username, u.email
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    ORDER BY o.order_date DESC
")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Order Management</h2>

    <!-- Orders List -->
    <div class="card">
        <div class="card-header">
            <h5>Recent Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . array_reduce($orders, function($carry, $order) {
                            $statusClass = '';
                            switch ($order['status']) {
                                case 'completed': $statusClass = 'success'; break;
                                case 'processing': $statusClass = 'info'; break;
                                case 'cancelled': $statusClass = 'danger'; break;
                                default: $statusClass = 'warning';
                            }

                            $paymentClass = $order['payment_status'] === 'paid' ? 'success' :
                                           ($order['payment_status'] === 'partial' ? 'info' : 'danger');

                            return $carry . '
                            <tr>
                                <td>#' . $order['order_id'] . '</td>
                                <td>' . htmlspecialchars($order['username']) . '<br><small>' . htmlspecialchars($order['email']) . '</small></td>
                                <td>' . date('M j, Y', strtotime($order['order_date'])) . '</td>
                                <td>' . number_format($order['total_amount'], 2) . '</td>
                                <td><span class="badge bg-' . $statusClass . '">' . ucfirst($order['status']) . '</span></td>
                                <td><span class="badge bg-' . $paymentClass . '">' . ucfirst($order['payment_status']) . '</span></td>
                                <td>
                                    <a href="view_order.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-primary">View</a>
                                    <a href="update_order.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-info">Update</a>
                                </td>
                            </tr>
                            ';
                        }, '') . '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>