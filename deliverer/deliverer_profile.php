<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');
/*
// Get deliverer profile
$stmt = $pdo->prepare("
    SELECT u.*, d.vehicle_type, d.license_plate, d.is_active
    FROM users u
    JOIN deliverers d ON u.user_id = d.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

if (!$profile) {
    $_SESSION['message'] = "Profile not found";
    $_SESSION['msg_type'] = "danger";
    header("Location: deliverer_dashboard.php");
    exit();
}
*/
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
</div>';

include '../includes/main_template.php';
?>