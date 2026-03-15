<?php
require_once '../includes/db.php';
require_once '../config/paypal_config.php';

use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

$order_id = $_GET['order_id'];
$token = $_GET['token'];

// Capture payment
$environment = new SandboxEnvironment($paypal_config['client_id'], $paypal_config['secret']);
$client = new PayPalHttpClient($environment);

$request = new OrdersCaptureRequest($token);
$response = $client->execute($request);

if ($response->result->status == 'COMPLETED') {
    // Update payment status
    $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE transaction_id = ?");
    $stmt->execute([$response->result->id]);

    // Update order status
    $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ?")
       ->execute([$order_id]);

    header("Location: ../order_confirmation.php?id=$order_id");
} else {
    $_SESSION['message'] = "Payment failed. Please try again.";
    header("Location: ../checkout.php");
}