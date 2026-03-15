<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Your Orders";

// Get customer's orders with delivery information
$stmt = $pdo->prepare("
    SELECT o.*, 
           d.status AS delivery_status,
           d.delivery_id,
           dl.vehicle_type,
           u.username AS deliverer_name
    FROM orders o
    LEFT JOIN deliveries d ON o.order_id = d.order_id
    LEFT JOIN deliverers dl ON d.deliverer_id = dl.deliverer_id
    LEFT JOIN users u ON dl.user_id = u.user_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$content = '
<div class="container py-5">
    <h2 class="mb-4">Your Orders</h2>

    '.((empty($orders)) ? '
    <div class="alert alert-info">
        You haven\'t placed any orders yet. <a href="products.php" class="alert-link">Browse our products</a> to get started.
    </div>' : '
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                '.array_reduce($orders, function($carry, $order) use ($pdo) {
                    // Get item count for this order
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order['order_id']]);
                    $itemCount = $stmt->fetchColumn();

                    // Order status styling
                    $orderStatusClass = '';
                    switch ($order['status']) {
                        case 'completed': $orderStatusClass = 'success'; break;
                        case 'processing': $orderStatusClass = 'info'; break;
                        case 'cancelled': $orderStatusClass = 'danger'; break;
                        default: $orderStatusClass = 'warning';
                    }

                    // Delivery status styling and text
                    $deliveryStatusText = 'Not scheduled';
                    $deliveryStatusClass = 'secondary';
                    
                    if ($order['delivery_status']) {
                        switch ($order['delivery_status']) {
                            case 'pending':
                                $deliveryStatusText = 'Awaiting assignment';
                                $deliveryStatusClass = 'warning';
                                break;
                            case 'assigned':
                                $deliveryStatusText = 'Assigned to '.htmlspecialchars($order['deliverer_name']).' ('.$order['vehicle_type'].')';
                                $deliveryStatusClass = 'primary';
                                break;
                            case 'picked_up':
                                $deliveryStatusText = 'Picked up by '.htmlspecialchars($order['deliverer_name']);
                                $deliveryStatusClass = 'info';
                                break;
                            case 'in_transit':
                                $deliveryStatusText = 'On the way';
                                $deliveryStatusClass = 'info';
                                break;
                            case 'delivered':
                                $deliveryStatusText = 'Delivered';
                                $deliveryStatusClass = 'success';
                                break;
                            case 'failed':
                                $deliveryStatusText = 'Delivery failed';
                                $deliveryStatusClass = 'danger';
                                break;
                            default:
                                $deliveryStatusText = ucfirst($order['delivery_status']);
                        }
                    }

                    // Track button (only show if delivery exists and is in progress)
                    $trackButton = '';
                    if ($order['delivery_id'] && in_array($order['delivery_status'], ['assigned', 'picked_up', 'in_transit'])) {
                        $trackButton = '<a href="../delivery/delivery.php?id='.$order['delivery_id'].'" class="btn btn-sm btn-outline-primary">Track</a>';
                    }

                    return $carry.'
                    <tr>
                        <td>#'.$order['order_id'].'</td>
                        <td>'.date('M j, Y', strtotime($order['order_date'])).'</td>
                        <td>'.$itemCount.'</td>
                        <td>KSh '.number_format($order['total_amount'], 2).'</td>
                        <td><span class="badge bg-'.$orderStatusClass.'">'.ucfirst($order['status']).'</span></td>
                        <td><span class="badge bg-'.$deliveryStatusClass.'">'.$deliveryStatusText.'</span></td>
                        <td>'.$trackButton.'</td>
                    </tr>';
                }, '').'
            </tbody>
        </table>
    </div>').'
</div>';

include '../includes/main_template.php';
?>