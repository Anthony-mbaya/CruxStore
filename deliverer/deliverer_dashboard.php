<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

$pageTitle = "Deliverer Dashboard";

// Get deliverer's active deliveries
$stmt = $pdo->prepare("
    SELECT d.*, o.order_id, o.total_amount, u.username AS customer_name
    FROM deliveries d
    JOIN orders o ON d.order_id = o.order_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE d.deliverer_id = (
        SELECT deliverer_id FROM deliverers WHERE user_id = ?
    )
    AND d.status IN ('assigned', 'picked_up', 'in_transit')
    ORDER BY d.estimated_delivery_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$activeDeliveries = $stmt->fetchAll();

// Get completed deliveries count
$completedStmt = $pdo->prepare("
    SELECT COUNT(*) FROM deliveries 
    WHERE deliverer_id = (
        SELECT deliverer_id FROM deliverers WHERE user_id = ?
    )
    AND status = 'delivered'
");
$completedStmt->execute([$_SESSION['user_id']]);
$completedCount = $completedStmt->fetchColumn();

// deliverer status
$stmt = $pdo->prepare("SELECT is_active FROM deliverers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$isActive = $stmt->fetchColumn();

$content = '
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mb-3">
            <h2 class="mb-4">Active Deliveries</h2>
            
            '.((empty($activeDeliveries)) ? '
            <div class="alert alert-info">
                You currently have no active deliveries.
            </div>' : '
            <div class="list-group">
                '.array_reduce($activeDeliveries, function($carry, $delivery) {
                    $statusClass = [
                        'assigned' => 'warning',
                        'picked_up' => 'primary',
                        'in_transit' => 'info'
                    ][$delivery['status']] ?? 'secondary';
                    
                    return $carry.'
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <h5>Order #'.$delivery['order_id'].'</h5>
                            <span class="badge bg-'.$statusClass.'">'.ucfirst(str_replace('_', ' ', $delivery['status'])).'</span>
                        </div>
                        <p class="mb-1">Customer: '.htmlspecialchars($delivery['customer_name']).'</p>
                        <p class="mb-1">Amount: KSh '.number_format($delivery['total_amount'], 2).'</p>
                        <p class="mb-2">Estimated by: '.date('h:i A', strtotime($delivery['estimated_delivery_time'])).'</p>
                        <div class="d-flex gap-2">
                            <a href="deliverer_delivery_details.php?id='.$delivery['delivery_id'].'" class="btn btn-sm btn-outline-primary">Details</a>
                            <a href="deliverer_update_status.php?id='.$delivery['delivery_id'].'" class="btn btn-sm btn-outline-success">Update Status</a>
                        </div>
                    </div>';
                }, '').'
            </div>').'
        </div>
        
        <div class="col-md-4 gap-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <a href="deliverer_location.php" class="btn btn-primary w-100 mb-2">Update My Location</a>
                    <a href="deliverer_availability.php" class="btn btn-outline-secondary w-100 mb-2 d-flex justify-content-around">
                    Set Availability
                    <span class="badge rounded bg-'.($isActive ? 'success' : 'danger').'" style="font-size: 1.2rem;">
                    '.($isActive ? 'Available' : 'Not Available').'
                </span>
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Stats</h5>
                    <div class="d-flex justify-content-between">
                        <span>Active Deliveries:</span>
                        <strong>'.count($activeDeliveries).'</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Completed:</span>
                        <strong>'.$completedCount.'</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>