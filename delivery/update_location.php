<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $deliveryId = $data['delivery_id'];
    $lat = $data['latitude'];
    $lng = $data['longitude'];

    // Check if logged-in user is the assigned deliverer
    $stmt = $pdo->prepare("
        UPDATE deliveries d
        JOIN deliverers dl ON d.deliverer_id = dl.deliverer_id
        SET d.current_latitude = ?, d.current_longitude = ?, d.updated_at = NOW()
        WHERE d.delivery_id = ? AND dl.user_id = ?
    ");
    $stmt->execute([$lat, $lng, $deliveryId, $_SESSION['user']['user_id']]);

    echo json_encode(['status' => 'ok']);
    exit;
}
http_response_code(405);
