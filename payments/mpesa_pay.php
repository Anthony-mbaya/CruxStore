<?php
require_once '../includes/db.php';
require_once '../config/mpesa_config.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}
$mpesa_config = require '../config/mpesa_config.php';
function getMpesaToken() {
    $mpesa_config = require '../config/mpesa_config.php';

    $credentials = base64_encode($mpesa_config['consumer_key'] . ':' . $mpesa_config['consumer_secret']);

    $ch = curl_init('https://' . ($mpesa_config['env'] === 'sandbox' ? 'sandbox' : 'api') . '.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $credentials
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['access_token'])) {
        return $response['access_token'];
    } else {
        die('Failed to generate M-Pesa access token');
    }
}  
$order_id = $_GET['order_id'];
$phone    = $_GET['phone']; // Format: 2547XXXXXXXX

// Fetch order total
$stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) die("Invalid order");

// Initiate M-Pesa STK Push
$timestamp = date('YmdHis');
$password  = base64_encode($mpesa_config['shortcode'] . $mpesa_config['passkey'] . $timestamp);

$payload = [
    'BusinessShortCode' => $mpesa_config['shortcode'],
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => (int) round($order['total_amount']),
    'PartyA'            => $phone,
    'PartyB'            => $mpesa_config['shortcode'],
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $mpesa_config['callback_url'],
    'AccountReference'  => "Order-$order_id",
    'TransactionDesc'   => "Payment for Order #$order_id"
];

$ch = curl_init('https://' . ($mpesa_config['env'] === 'sandbox' ? 'sandbox' : 'api') . '.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER  => [
        'Authorization: Bearer ' . getMpesaToken(),
        'Content-Type: application/json'
    ],
    CURLOPT_POST        => true,
    CURLOPT_POSTFIELDS  => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true
]);


$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {
    // Log payment attempt
    $stmt = $pdo->prepare("INSERT INTO payments
        (order_id, payment_method, amount, transaction_id, status)
        VALUES (?, 'mpesa', ?, ?, 'pending')");
    $stmt->execute([$order_id, $order['total_amount'], $response['CheckoutRequestID']]);
    
    //$_SESSION['message'] = "M-Pesa payment initiated! Complete the prompt on your phone.";
    header("Location: ../client/order_confirmation.php?id=$order_id");
} else {
    //$_SESSION['message'] = "Payment failed, you either cancelled the transaction or system errors";
    //$_SESSION['message'] = "Payment failed: " . ($response['errorMessage'] ?? 'Unknown error');
    header("Location: ../client/checkout.php");
}