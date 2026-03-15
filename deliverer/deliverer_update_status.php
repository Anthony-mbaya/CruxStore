<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

if (!isset($_GET['id'])) {
    header("Location: deliverer_dashboard.php");
    exit();
}

$deliveryId = $_GET['id'];

// Verify deliverer owns this delivery
$stmt = $pdo->prepare("
    SELECT 1 FROM deliveries d
    JOIN deliverers dl ON d.deliverer_id = dl.deliverer_id
    WHERE d.delivery_id = ? AND dl.user_id = ?
");
$stmt->execute([$deliveryId, $_SESSION['user_id']]);

if (!$stmt->fetchColumn()) {
    $_SESSION['message'] = "Delivery not found or not authorized";
    $_SESSION['msg_type'] = "danger";
    header("Location: deliverer_dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? null;
    
    try {
        $pdo->beginTransaction();
        
        $updateStmt = $pdo->prepare("
            UPDATE deliveries 
            SET status = ?, notes = ?, updated_at = NOW()
            WHERE delivery_id = ?
        ");
        $updateStmt->execute([$newStatus, $notes, $deliveryId]);
        
        // If delivered, set actual delivery time
        if ($newStatus === 'delivered') {
            $pdo->prepare("
                UPDATE deliveries 
                SET actual_delivery_time = NOW() 
                WHERE delivery_id = ?
            ")->execute([$deliveryId]);
        }
        
        $pdo->commit();
        
        $_SESSION['message'] = "Delivery status updated successfully";
        $_SESSION['msg_type'] = "success";
        header("Location: deliverer_delivery_details.php?id=".$deliveryId);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Error updating delivery: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

// Get current status
$statusStmt = $pdo->prepare("SELECT status FROM deliveries WHERE delivery_id = ?");
$statusStmt->execute([$deliveryId]);
$currentStatus = $statusStmt->fetchColumn();

$pageTitle = "Update Delivery Status";

$content = '
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Update Delivery Status</h2>
        <a href="deliverer_delivery_details.php?id='.$deliveryId.'" class="btn btn-outline-secondary">Back to Details</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="status" class="form-label">New Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="assigned" '.($currentStatus === 'assigned' ? 'selected' : '').'>Assigned</option>
                        <option value="picked_up" '.($currentStatus === 'picked_up' ? 'selected' : '').'>Picked Up</option>
                        <option value="in_transit" '.($currentStatus === 'in_transit' ? 'selected' : '').'>In Transit</option>
                        <option value="delivered" '.($currentStatus === 'delivered' ? 'selected' : '').'>Delivered</option>
                        <option value="failed" '.($currentStatus === 'failed' ? 'selected' : '').'>Failed</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <a href="deliverer_delivery_details.php?id='.$deliveryId.'" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>