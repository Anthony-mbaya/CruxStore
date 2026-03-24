<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'deliverer_function.php';
redirectIfNotAuthorized('deliverer');

// Check if user is logged in and is a deliverer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliverer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$deliverer_id = getDelivererIdByUserId($pdo, $user_id);

if (!$deliverer_id) {
    die("Deliverer profile not found. Please contact admin.");
}

// Get real data
$todayDeliveries = getTodayDeliveries($pdo, $deliverer_id);
$todayEarnings = getTodayEarnings($pdo, $deliverer_id);
$stats = getDeliveryStats($pdo, $deliverer_id);
$pendingDeliveries = getPendingDeliveries($pdo, $deliverer_id);
$completedDeliveries = getCompletedDeliveries($pdo, $deliverer_id);


// updates

$stmt = $pdo->prepare("
    SELECT u.*, d.vehicle_type, d.license_plate, d.is_active
    FROM users u
    LEFT JOIN deliverers d ON u.user_id = d.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

/* If deliverer profile does not exist, create it */
if ($profile['vehicle_type'] === null) {

    $pdo->prepare("
        INSERT INTO deliverers (user_id, vehicle_type, license_plate, is_active)
        VALUES (?, '', '', 1)
    ")->execute([$_SESSION['user_id']]);

    // reload profile
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleType = $_POST['vehicle_type'];
    $licensePlate = $_POST['license_plate'];
    $phone = $_POST['phone'];
    
    try {
        $pdo->beginTransaction();
        
        // Update user info
        $pdo->prepare("
            UPDATE users 
            SET phone = ?
            WHERE user_id = ?
        ")->execute([$phone, $_SESSION['user_id']]);
        
        // Update deliverer info
        $pdo->prepare("
            UPDATE deliverers 
            SET vehicle_type = ?, license_plate = ?
            WHERE user_id = ?
        ")->execute([$vehicleType, $licensePlate, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        $_SESSION['message'] = "Profile updated successfully";
        $_SESSION['msg_type'] = "success";
        header("Location: deliverer_profile.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Error updating profile: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}



$pageTitle = "My Profile";
$content = '
<style>
    .stat-card {
        transition: transform 0.2s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }
    }
    .status-assigned { background: #ffc107; color: #000; }
    .status-picked_up { background: #17a2b8; color: #fff; }
    .status-in_transit { background: #007bff; color: #fff; }
    .status-delivered { background: #28a745; color: #fff; }
</style>

<div class="container mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck me-2"></i>Deliverer Dashboard</h2>
        
    </div>

    <!-- Todays Performance Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm stat-card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">💰 Todays Performance</h6>
                        <i class="fas fa-chart-line text-success fs-4"></i>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="text-muted">Deliveries</span>
                            <strong class="fs-5">' . $todayDeliveries . '</strong>
                        </div>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: ' . min(100, ($todayDeliveries / 10) * 100) . '%;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Earnings</span>
                            <strong class="fs-5">KSh ' . number_format($todayEarnings) . '</strong>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Rating</span>
                            <strong class="fs-5">4.5 ⭐</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="col-md-8 gap-2">
            <div class="row gap-2 gap-md-0">
                <div class="col-md-4">
                    <div class="card shadow-sm stat-card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Total Delivered</h6>
                            <h3 class="mb-0">' . $stats['total_delivered'] . '</h3>
                            <small>All time deliveries</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm stat-card bg-warning text-dark">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Active Deliveries</h6>
                            <h3 class="mb-0">' . $stats['active_deliveries'] . '</h3>
                            <small>In progress</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm stat-card bg-info text-white">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Pending Assignments</h6>
                            <h3 class="mb-0">' . $stats['pending_assignments'] . '</h3>
                            <small>Awaiting pickup</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Deliveries -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-truck-moving me-2"></i>Active Deliveries</h5>
        </div>
        <div class="card-body">';

if (count($pendingDeliveries) > 0) {
    $content .= '
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Order ID</th>
                            <th>Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($pendingDeliveries as $delivery) {
        $content .= '
                        <tr>
                            <td>#' . $delivery['delivery_id'] . '</td>
                            <td>#' . $delivery['order_id'] . '</td>
                            <td>' . htmlspecialchars(substr($delivery['delivery_address'], 0, 50)) . '...</td>
                            <td>
                                <span class="status-badge status-' . $delivery['status'] . '">
                                    ' . ucfirst(str_replace('_', ' ', $delivery['status'])) . '
                                </span>
                            </td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>';
} else {
    $content .= '<p class="text-muted text-center mb-0">No active deliveries at the moment.</p>';
}

$content .= '
        </div>
    </div>

    <!-- Completed Deliveries -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Recent Completed Deliveries</h5>
        </div>
        <div class="card-body">';

if (count($completedDeliveries) > 0) {
    $content .= '
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Order ID</th>
                            <th>Completed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($completedDeliveries as $delivery) {
        $content .= '
                        <tr>
                            <td>#' . $delivery['delivery_id'] . '</td>
                            <td>#' . $delivery['order_id'] . '</td>
                            <td>' . date('M d, H:i', strtotime($delivery['updated_at'])) . '</td>
                            <td>
                                <span class="status-badge status-delivered">
                                    Delivered
                                </span>
                            </td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>';
} else {
    $content .= '<p class="text-muted text-center mb-0">No completed deliveries yet.</p>';
}

$content .= '
        </div>
    </div>
</div>


<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Profile</h2>
        <a href="deliverer_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="'.htmlspecialchars($profile['username']).'" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="'.htmlspecialchars($profile['email']).'" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <input type="text" class="form-control" value="'.($profile['is_active'] ? 'Active' : 'Inactive').'" readonly>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Delivery Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="'.htmlspecialchars($profile['phone']).'" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type</label>
                            <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                <option value="Motorcycle" '.($profile['vehicle_type'] === 'Motorcycle' ? 'selected' : '').'>Motorcycle</option>
                                <option value="Bicycle" '.($profile['vehicle_type'] === 'Bicycle' ? 'selected' : '').'>Bicycle</option>
                                <option value="Car" '.($profile['vehicle_type'] === 'Car' ? 'selected' : '').'>Car</option>
                                <option value="Pickup Truck" '.($profile['vehicle_type'] === 'Pickup Truck' ? 'selected' : '').'>Pickup Truck</option>
                                <option value="Van" '.($profile['vehicle_type'] === 'Van' ? 'selected' : '').'>Van</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="license_plate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="license_plate" name="license_plate" 
                                   value="'.htmlspecialchars($profile['license_plate']).'" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>';
include '../includes/main_template.php';
?>