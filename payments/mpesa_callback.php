<?php
require_once '../includes/db.php';

$payload = json_decode(file_get_contents('php://input'), true);

if ($payload['Body']['stkCallback']['ResultCode'] == 0) {
    $checkout_request_id = $payload['Body']['stkCallback']['CheckoutRequestID'];
    $mpesa_receipt_number = $payload['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];

    // Update payment status and set transaction_id to MpesaReceiptNumber
    $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', transaction_id = ? WHERE transaction_id = ?");
    $stmt->execute([$mpesa_receipt_number, $checkout_request_id]);

    // Update order status
    $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id =
        (SELECT order_id FROM payments WHERE transaction_id = ? LIMIT 1)")
       ->execute([$mpesa_receipt_number]);
}
?>