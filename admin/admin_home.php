<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Admin Dashboard";

// Get stats
$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$deliveriesCount = $pdo->query("SELECT COUNT(*) FROM deliveries")->fetchColumn();

// Get active deliveries count
$activeDeliveriesCount = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status IN ('assigned', 'picked_up', 'in_transit')")->fetchColumn();

$recentOrders = $pdo->query("SELECT o.order_id, o.total_amount, o.status, u.username
                             FROM orders o JOIN users u ON o.customer_id = u.user_id
                             ORDER BY o.order_date DESC LIMIT 5")->fetchAll();

// Get recent deliveries
$recentDeliveries = $pdo->query("SELECT d.delivery_id, d.status, o.order_id, u.username as customer_name, du.username as deliverer_name
                                 FROM deliveries d
                                 JOIN orders o ON d.order_id = o.order_id
                                 JOIN users u ON o.customer_id = u.user_id
                                 LEFT JOIN deliverers del ON d.deliverer_id = del.deliverer_id
                                 LEFT JOIN users du ON del.user_id = du.user_id
                                 ORDER BY d.created_at DESC LIMIT 5")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Admin Dashboard</h2>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <p class="card-text display-4">' . $productsCount . '</p>
                    <a href="products.php" class="text-white">View Products</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <p class="card-text display-4">' . $ordersCount . '</p>
                    <a href="orders_page.php" class="text-white">View Orders</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Deliveries</h5>
                    <p class="card-text display-4">' . $deliveriesCount . '</p>
                    <small class="text-white">(' . $activeDeliveriesCount . ' active)</small><br>
                    <a href="deliveries.php" class="text-white">Manage Deliveries</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Customers</h5>
                    <p class="card-text display-4">' . $customersCount . '</p>
                    <a href="customers.php" class="text-white">View Customers</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Row -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-6">
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
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . array_reduce($recentOrders, function($carry, $order) {
                                    $statusClass = '';
                                    switch ($order['status']) {
                                        case 'completed': $statusClass = 'success'; break;
                                        case 'processing': $statusClass = 'info'; break;
                                        case 'cancelled': $statusClass = 'danger'; break;
                                        default: $statusClass = 'warning';
                                    }

                                    return $carry . '
                                    <tr>
                                        <td>#' . $order['order_id'] . '</td>
                                        <td>' . htmlspecialchars($order['username']) . '</td>
                                        <td>Ksh.' . number_format($order['total_amount'], 2) . '</td>
                                        <td><span class="badge bg-' . $statusClass . '">' . ucfirst($order['status']) . '</span></td>
                                        <td><a href="orders_page.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-primary">View</a></td>
                                    </tr>
                                    ';
                                }, '') . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Deliveries -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Deliveries</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Delivery ID</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Deliverer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . array_reduce($recentDeliveries, function($carry, $delivery) {
                                    $statusClass = '';
                                    switch ($delivery['status']) {
                                        case 'delivered': $statusClass = 'success'; break;
                                        case 'in_transit': 
                                        case 'picked_up': $statusClass = 'info'; break;
                                        case 'failed': $statusClass = 'danger'; break;
                                        case 'assigned': $statusClass = 'warning'; break;
                                        default: $statusClass = 'secondary';
                                    }

                                    return $carry . '
                                    <tr>
                                        <td>#' . $delivery['delivery_id'] . '</td>
                                        <td>#' . $delivery['order_id'] . '</td>
                                        <td>' . htmlspecialchars($delivery['customer_name']) . '</td>
                                        <td>' . ($delivery['deliverer_name'] ? htmlspecialchars($delivery['deliverer_name']) : 'Unassigned') . '</td>
                                        <td><span class="badge bg-' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $delivery['status'])) . '</span></td>
                                    </tr>
                                    ';
                                }, '') . '
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a href="deliveries.php" class="btn btn-primary btn-sm">View All Deliveries</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>