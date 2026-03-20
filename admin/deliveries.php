<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Deliveries Management";

// Handle status updates
if ($_POST && isset($_POST['update_status'])) {
    $deliveryId = $_POST['delivery_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE deliveries SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE delivery_id = ?");
    $stmt->execute([$newStatus, $deliveryId]);
    
    // If delivered, update actual delivery time
    if ($newStatus == 'delivered') {
        $stmt = $pdo->prepare("UPDATE deliveries SET actual_delivery_time = CURRENT_TIMESTAMP WHERE delivery_id = ?");
        $stmt->execute([$deliveryId]);
    }
    
    header("Location: deliveries.php?updated=1");
    exit();
}

// Handle deliverer assignment
if ($_POST && isset($_POST['assign_deliverer'])) {
    $deliveryId = $_POST['delivery_id'];
    $delivererId = $_POST['deliverer_id'];
    
    $stmt = $pdo->prepare("UPDATE deliveries SET deliverer_id = ?, status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE delivery_id = ?");
    $stmt->execute([$delivererId, $deliveryId]);
    
    header("Location: deliveries.php?assigned=1");
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$delivererFilter = $_GET['deliverer'] ?? 'all';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter != 'all') {
    $whereConditions[] = "d.status = ?";
    $params[] = $statusFilter;
}

if ($delivererFilter != 'all') {
    $whereConditions[] = "d.deliverer_id = ?";
    $params[] = $delivererFilter;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get deliveries
$deliveriesQuery = "SELECT d.*, o.order_id, o.total_amount, o.delivery_address,
                           u.username as customer_name, u.phone as customer_phone,
                           du.username as deliverer_name, del.vehicle_type, del.license_plate
                    FROM deliveries d
                    JOIN orders o ON d.order_id = o.order_id
                    JOIN users u ON o.customer_id = u.user_id
                    LEFT JOIN deliverers del ON d.deliverer_id = del.deliverer_id
                    LEFT JOIN users du ON del.user_id = du.user_id
                    $whereClause
                    ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($deliveriesQuery);
$stmt->execute($params);
$deliveries = $stmt->fetchAll();

// Get available deliverers
$availableDeliverers = $pdo->query("SELECT del.deliverer_id, u.username, del.vehicle_type, del.is_active
                                   FROM deliverers del
                                   JOIN users u ON del.user_id = u.user_id
                                   WHERE del.is_active = 1
                                   ORDER BY u.username")->fetchAll();

// Get delivery statistics
$stats = $pdo->query("SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                        SUM(CASE WHEN status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
                        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                      FROM deliveries")->fetch();

$content = '
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h2>Deliveries Management</h2>
        <a href="create_delivery.php" class="btn btn-success">Create New Delivery</a>
    </div>

    <!-- Alert Messages -->
    ' . (isset($_GET['updated']) ? '<div class="alert alert-success">Delivery status updated successfully!</div>' : '') . '
    ' . (isset($_GET['assigned']) ? '<div class="alert alert-success">Deliverer assigned successfully!</div>' : '') . '

    <!-- Statistics Cards -->
    <div class="row mb-4 gap-4 gap-lg-0">
        <div class="col-md-2">
            <div class="card text-center border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total</h5>
                    <h3>' . $stats['total'] . '</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">Pending</h5>
                    <h3>' . $stats['pending'] . '</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">Assigned</h5>
                    <h3>' . $stats['assigned'] . '</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">In Transit</h5>
                    <h3>' . ($stats['picked_up'] + $stats['in_transit']) . '</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">Delivered</h5>
                    <h3>' . $stats['delivered'] . '</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">Failed</h5>
                    <h3>' . $stats['failed'] . '</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="all" ' . ($statusFilter == 'all' ? 'selected' : '') . '>All Statuses</option>
                        <option value="pending" ' . ($statusFilter == 'pending' ? 'selected' : '') . '>Pending</option>
                        <option value="assigned" ' . ($statusFilter == 'assigned' ? 'selected' : '') . '>Assigned</option>
                        <option value="picked_up" ' . ($statusFilter == 'picked_up' ? 'selected' : '') . '>Picked Up</option>
                        <option value="in_transit" ' . ($statusFilter == 'in_transit' ? 'selected' : '') . '>In Transit</option>
                        <option value="delivered" ' . ($statusFilter == 'delivered' ? 'selected' : '') . '>Delivered</option>
                        <option value="failed" ' . ($statusFilter == 'failed' ? 'selected' : '') . '>Failed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Deliverer</label>
                    <select name="deliverer" class="form-select">
                        <option value="all" ' . ($delivererFilter == 'all' ? 'selected' : '') . '>All Deliverers</option>
                        ' . array_reduce($availableDeliverers, function($carry, $deliverer) use ($delivererFilter) {
                            $selected = $delivererFilter == $deliverer['deliverer_id'] ? 'selected' : '';
                            return $carry . '<option value="' . $deliverer['deliverer_id'] . '" ' . $selected . '>' . 
                                   htmlspecialchars($deliverer['username']) . ' (' . htmlspecialchars($deliverer['vehicle_type']) . ')</option>';
                        }, '') . '
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="deliveries.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Deliveries Table -->
    <div class="card">
        <div class="card-header">
            <h5>Deliveries (' . count($deliveries) . ' results)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Deliverer</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Est. Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . array_reduce($deliveries, function($carry, $delivery) use ($availableDeliverers) {
                            $statusClass = '';
                            switch ($delivery['status']) {
                                case 'delivered': $statusClass = 'success'; break;
                                case 'in_transit': 
                                case 'picked_up': $statusClass = 'info'; break;
                                case 'failed': $statusClass = 'danger'; break;
                                case 'assigned': $statusClass = 'warning'; break;
                                default: $statusClass = 'secondary';
                            }

                            $trackingBtn = in_array($delivery['status'], ['assigned', 'picked_up', 'in_transit']) 
                                ? '<a href="delivery.php?id=' . $delivery['delivery_id'] . '" class="btn btn-sm btn-info" title="Track Live Location">
                                     <i class="fas fa-map-marker-alt"></i> Track
                                   </a>' 
                                : '';

                            return $carry . '
                            <tr>
                                <td>#' . $delivery['delivery_id'] . '</td>
                                <td>
                                    <a href="view_order.php?id=' . $delivery['order_id'] . '">#' . $delivery['order_id'] . '</a>
                                    <br><small class="text-muted">$' . number_format($delivery['total_amount'], 2) . '</small>
                                </td>
                                <td>
                                    ' . htmlspecialchars($delivery['customer_name']) . '
                                    <br><small class="text-muted">' . htmlspecialchars($delivery['customer_phone']) . '</small>
                                </td>
                                <td>
                                    ' . ($delivery['deliverer_name'] ? 
                                        htmlspecialchars($delivery['deliverer_name']) . 
                                        '<br><small class="text-muted">' . htmlspecialchars($delivery['vehicle_type']) . ' - ' . htmlspecialchars($delivery['license_plate']) . '</small>'
                                        : '<span class="text-muted">Unassigned</span>') . '
                                </td>
                                <td>
                                    <small>' . htmlspecialchars(substr($delivery['delivery_address'], 0, 50)) . 
                                    (strlen($delivery['delivery_address']) > 50 ? '...' : '') . '</small>
                                </td>
                                <td>
                                    <span class="badge bg-' . $statusClass . '">' . 
                                    ucfirst(str_replace('_', ' ', $delivery['status'])) . '</span>
                                </td>
                                <td>
                                    ' . ($delivery['estimated_delivery_time'] ? 
                                        date('M j, Y H:i', strtotime($delivery['estimated_delivery_time'])) : 
                                        '<span class="text-muted">Not set</span>') . '
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                        <div class="btn-group btn-group-sm" role="group">
                                            ' . $trackingBtn . '
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateModal' . $delivery['delivery_id'] . '">
                                                <i class="fas fa-edit">Update</i>
                                            </button>
                                        </div>
                                        ' . (!$delivery['deliverer_id'] ? 
                                            '<button class="btn btn-outline-warning btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#assignModal' . $delivery['delivery_id'] . '">
                                                <i class="fas fa-user-plus"></i> Assign
                                            </button>' : '') . '
                                    </div>
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

<!-- Update Status Modals -->
' . array_reduce($deliveries, function($carry, $delivery) {
    return $carry . '
    <div class="modal fade" id="updateModal' . $delivery['delivery_id'] . '">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Delivery #' . $delivery['delivery_id'] . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="delivery_id" value="' . $delivery['delivery_id'] . '">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" ' . ($delivery['status'] == 'pending' ? 'selected' : '') . '>Pending</option>
                                <option value="assigned" ' . ($delivery['status'] == 'assigned' ? 'selected' : '') . '>Assigned</option>
                                <option value="picked_up" ' . ($delivery['status'] == 'picked_up' ? 'selected' : '') . '>Picked Up</option>
                                <option value="in_transit" ' . ($delivery['status'] == 'in_transit' ? 'selected' : '') . '>In Transit</option>
                                <option value="delivered" ' . ($delivery['status'] == 'delivered' ? 'selected' : '') . '>Delivered</option>
                                <option value="failed" ' . ($delivery['status'] == 'failed' ? 'selected' : '') . '>Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    ';
}, '') . '

<!-- Assign Deliverer Modals -->
' . array_reduce(array_filter($deliveries, function($d) { return !$d['deliverer_id']; }), function($carry, $delivery) use ($availableDeliverers) {
    return $carry . '
    <div class="modal fade" id="assignModal' . $delivery['delivery_id'] . '">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Deliverer - Delivery #' . $delivery['delivery_id'] . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="delivery_id" value="' . $delivery['delivery_id'] . '">
                        <div class="mb-3">
                            <label class="form-label">Select Deliverer</label>
                            <select name="deliverer_id" class="form-select" required>
                                <option value="">Choose deliverer...</option>
                                ' . array_reduce($availableDeliverers, function($options, $deliverer) {
                                    return $options . '<option value="' . $deliverer['deliverer_id'] . '">' . 
                                           htmlspecialchars($deliverer['username']) . ' - ' . 
                                           htmlspecialchars($deliverer['vehicle_type']) . '</option>';
                                }, '') . '
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_deliverer" class="btn btn-warning">Assign Deliverer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    ';
}, '') . '
';

include '../includes/main_template.php';
?>