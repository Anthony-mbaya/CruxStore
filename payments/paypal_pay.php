<?php
require_once '../includes/db.php';
require_once '../config/paypal_config.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

// Setup PayPal client
$environment = new SandboxEnvironment($paypal_config['client_id'], $paypal_config['secret']);
$client = new PayPalHttpClient($environment);

$order_id = $_POST['order_id'];

// Fetch order details
$stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) die("Invalid order");

// Create PayPal order
$request = new OrdersCreateRequest();
$request->prefer('return=representation');
$request->body = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "reference_id" => "order_$order_id",
        "amount" => [
            "value" => $order['total_amount'],
            "currency_code" => "USD"
        ]
    ]],
    "application_context" => [
        "cancel_url" => "https://yourdomain.com/checkout.php",
        "return_url" => "https://yourdomain.com/payments/paypal_callback.php?order_id=$order_id"
    ]
];

try {
    $response = $client->execute($request);
    $approvalUrl = null;

    foreach ($response->result->links as $link) {
        if ($link->rel == 'approve') {
            $approvalUrl = $link->href;
            break;
        }
    }

    if ($approvalUrl) {
        // Log payment attempt
        $stmt = $pdo->prepare("INSERT INTO payments
            (order_id, payment_method, amount, transaction_id, status)
            VALUES (?, 'paypal', ?, ?, 'pending')");
        $stmt->execute([$order_id, $order['total_amount'], $response->result->id]);

        header("Location: " . $approvalUrl);
    } else {
        throw new Exception("No approval URL found");
    }
} catch (Exception $e) {
    $_SESSION['message'] = "PayPal error: " . $e->getMessage();
    header("Location: ../checkout.php");
}