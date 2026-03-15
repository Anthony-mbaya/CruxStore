<?php
require_once '../config/mpesa_config.php';

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

    print_r($response);
}

getMpesaToken();
?>