<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

// Get current availability
$stmt = $pdo->prepare("SELECT is_active FROM deliverers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$isActive = $stmt->fetchColumn();

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = !$isActive;
    
    try {
        $pdo->prepare("
            UPDATE deliverers 
            SET is_active = ?, updated_at = NOW()
            WHERE user_id = ?
        ")->execute([$newStatus, $_SESSION['user_id']]);
        
        $_SESSION['message'] = "Your availability has been " . ($newStatus ? "enabled" : "disabled");
        $_SESSION['msg_type'] = "success";
        header("Location: deliverer_availability.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating availability: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

$pageTitle = "Set Availability";

$content = '
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Delivery Availability</h2>
        <a href="deliverer_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <h5 class="card-title mb-4">Current Status</h5>
            
            <div class="mb-4">
                <span class="badge rounded-pill bg-'.($isActive ? 'success' : 'danger').'" style="font-size: 1.2rem;">
                    '.($isActive ? 'Available for Deliveries' : 'Not Available').'
                </span>
            </div>
            
            <form method="POST">
                <button type="submit" class="btn btn-lg '.($isActive ? 'btn-danger' : 'btn-success').'">
                    '.($isActive ? 'Go Offline' : 'Go Online').'
                </button>
            </form>
            
            <div class="mt-4 alert alert-info">
                <strong>Note:</strong> When offline, you won\'t receive new delivery assignments.
            </div>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>