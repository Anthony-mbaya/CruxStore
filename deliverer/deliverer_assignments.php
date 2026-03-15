<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

$pageTitle = "My Deliveries";

// Get all deliverer's deliveries
$stmt = $pdo->prepare("
    SELECT d.*, o.order_id, o.total_amount, u.username AS customer_name,
           CASE 
               WHEN d.status = 'delivered' THEN 1
               WHEN d.status = 'failed' THEN 2
               ELSE 0
           END AS status_order
    FROM deliveries d
    JOIN orders o ON d.order_id = o.order_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE d.deliverer_id = (
        SELECT deliverer_id FROM deliverers WHERE user_id = ?
    )
    ORDER BY status_order ASC, d.estimated_delivery_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$allDeliveries = $stmt->fetchAll();

$content = '
<div class="container py-4">
    <h2 class="mb-4">My Delivery Assignments</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Est. Delivery</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.array_reduce($allDeliveries, function($carry, $delivery) {
                            $statusClass = [
                                'pending' => 'secondary',
                                'assigned' => 'warning',
                                'picked_up' => 'primary',
                                'in_transit' => 'info',
                                'delivered' => 'success',
                                'failed' => 'danger'
                            ][$delivery['status']] ?? 'dark';
                            
                            return $carry.'
                            <tr>
                                <td>#'.$delivery['order_id'].'</td>
                                <td>'.htmlspecialchars($delivery['customer_name']).'</td>
                                <td><span class="badge bg-'.$statusClass.'">'.ucfirst(str_replace('_', ' ', $delivery['status'])).'</span></td>
                                <td>'.date('M j, h:i A', strtotime($delivery['estimated_delivery_time'])).'</td>
                                <td>KSh '.number_format($delivery['total_amount'], 2).'</td>
                                <td>
                                    <a href="deliverer_delivery_details.php?id='.$delivery['delivery_id'].'" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>';
                        }, '').'
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>