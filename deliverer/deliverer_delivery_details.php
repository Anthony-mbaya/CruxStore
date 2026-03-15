<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

if (!isset($_GET['id']) {
    header("Location: deliverer_dashboard.php");
    exit();
}

$deliveryId = $_GET['id'];

// Get delivery details
$stmt = $pdo->prepare("
    SELECT d.*, o.*, u.username AS customer_name, u.phone AS customer_phone,
           dl.vehicle_type, dl.license_plate
    FROM deliveries d
    JOIN orders o ON d.order_id = o.order_id
    JOIN users u ON o.customer_id = u.user_id
    JOIN deliverers dl ON d.deliverer_id = dl.deliverer_id
    WHERE d.delivery_id = ?
    AND dl.user_id = ?
");
$stmt->execute([$deliveryId, $_SESSION['user_id']]);
$delivery = $stmt->fetch();

if (!$delivery) {
    $_SESSION['message'] = "Delivery not found or not authorized";
    $_SESSION['msg_type'] = "danger";
    header("Location: deliverer_dashboard.php");
    exit();
}

$pageTitle = "Delivery Details #" . $delivery['delivery_id'];

// Get order items
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$delivery['order_id']]);
$items = $itemsStmt->fetchAll();

$content = '
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Delivery Details</h2>
        <a href="deliverer_assignments.php" class="btn btn-outline-secondary">Back to All Deliveries</a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        '.array_reduce($items, function($carry, $item) {
                            return $carry.'
                            <div class="list-group-item">
                                <div class="d-flex">
                                    <img src="../'.htmlspecialchars($item['image_url']).'" alt="'.htmlspecialchars($item['name']).'" 
                                         class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                    <div>
                                        <h6>'.htmlspecialchars($item['name']).'</h6>
                                        <div class="d-flex justify-content-between">
                                            <span>Qty: '.$item['quantity'].'</span>
                                            <span>KSh '.number_format($item['unit_price'], 2).' each</span>
                                        </div>
                                        <div class="text-end fw-bold">
                                            KSh '.number_format($item['quantity'] * $item['unit_price'], 2).'
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }, '').'
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Delivery Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Pickup Location</h6>
                            <p>Nakuru CBD</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Destination</h6>
                            <p>'.$delivery['delivery_address'].'</p>
                        </div>
                    </div>
                    
                    <div id="map" style="height: 300px; width: 100%;" class="mb-3"></div>
                    
                    <div class="alert alert-info">
                        <strong>Customer Notes:</strong> '.($delivery['notes'] ? htmlspecialchars($delivery['notes']) : 'None').'
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Delivery Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Current Status</h6>
                        <span class="badge bg-'.([
                            'pending' => 'secondary',
                            'assigned' => 'warning',
                            'picked_up' => 'primary',
                            'in_transit' => 'info',
                            'delivered' => 'success',
                            'failed' => 'danger'
                        ][$delivery['status']] ?? 'dark').'">'.ucfirst(str_replace('_', ' ', $delivery['status'])).'</span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Estimated Delivery Time</h6>
                        <p>'.date('F j, Y \a\t h:i A', strtotime($delivery['estimated_delivery_time'])).'</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Customer Contact</h6>
                        <p>'.htmlspecialchars($delivery['customer_name']).'</p>
                        <p><a href="tel:'.$delivery['customer_phone'].'" class="text-decoration-none">'.$delivery['customer_phone'].'</a></p>
                    </div>
                    
                    <a href="deliverer_update_status.php?id='.$delivery['delivery_id'].'" class="btn btn-primary w-100">Update Status</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Your Vehicle</h5>
                </div>
                <div class="card-body">
                    <p><strong>Type:</strong> '.$delivery['vehicle_type'].'</p>
                    <p><strong>License Plate:</strong> '.$delivery['license_plate'].'</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize map (you'll need to add your map API key)
function initMap() {
    const pickup = { lat: -1.04684, lng: 37.08591 };
    const destination = { lat: '.$delivery['destination_latitude'].', lng: '.$delivery['destination_longitude'].' };
    
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: pickup
    });
    
    new google.maps.Marker({ position: pickup, map, title: "Pickup Location" });
    new google.maps.Marker({ position: destination, map, title: "Destination" });
    
    // Add route if you have DirectionsService enabled
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>';

include '../includes/main_template.php';
?>