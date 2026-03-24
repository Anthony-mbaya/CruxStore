<?php
// deliverer_functions.php
function getDelivererIdByUserId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT deliverer_id FROM deliverers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result['deliverer_id'] : null;
}

function getTodayDeliveries($pdo, $deliverer_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM deliveries 
        WHERE deliverer_id = ? 
        AND DATE(updated_at) = CURDATE()
        AND status = 'delivered'
    ");
    $stmt->execute([$deliverer_id]);
    $result = $stmt->fetch();
    return $result['count'];
}

function getTodayEarnings($pdo, $deliverer_id) {
    // Assuming delivery fee is KSh 100 per delivery
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as delivery_count 
        FROM deliveries 
        WHERE deliverer_id = ? 
        AND DATE(updated_at) = CURDATE()
        AND status = 'delivered'
    ");
    $stmt->execute([$deliverer_id]);
    $result = $stmt->fetch();
    $delivery_fee = 100; // KSh per delivery
    return $result['delivery_count'] * $delivery_fee;
}
function getPendingDeliveries($pdo, $deliverer_id) {
    $stmt = $pdo->prepare("
        SELECT d.*, o.total_amount, o.delivery_address 
        FROM deliveries d
        JOIN orders o ON d.order_id = o.order_id
        WHERE d.deliverer_id = ? 
        AND d.status IN ('assigned', 'picked_up', 'in_transit')
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$deliverer_id]);
    return $stmt->fetchAll();
}

function getCompletedDeliveries($pdo, $deliverer_id, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT d.*, o.total_amount, o.delivery_address 
        FROM deliveries d
        JOIN orders o ON d.order_id = o.order_id
        WHERE d.deliverer_id = ? 
        AND d.status = 'delivered'
        ORDER BY d.updated_at DESC
        LIMIT ?
    ");
    $stmt->execute([$deliverer_id, $limit]);
    return $stmt->fetchAll();
}

function getDeliveryStats($pdo, $deliverer_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as total_delivered,
            COUNT(CASE WHEN status IN ('assigned', 'picked_up', 'in_transit') THEN 1 END) as active_deliveries,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_assignments
        FROM deliveries 
        WHERE deliverer_id = ?
    ");
    $stmt->execute([$deliverer_id]);
    return $stmt->fetch();
}
?>