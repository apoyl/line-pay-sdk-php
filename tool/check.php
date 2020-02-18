<?php

require __DIR__ . '/_config.php';

// Get saved config
$config = $_SESSION['config'];
// Check session
if (!$config) {
    die("<script>alert('Session expired, please resend.');history.back();</script>");
}
// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $config['channelId'],
    'channelSecret' => $config['channelSecret'],
    'isSandbox' => ($config['isSandbox']) ? true : false, 
]);

// Successful page URL
$successUrl = './index.php?route=order';
// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];
// Get the order from session
$order = $_SESSION['linePayOrder'];

// Check Payment Status API
$response = $linePay->check($transactionId);

// Log
saveLog('Check Payment Status API', $response);

/**
 * Completed Transaction (Request Details API to complete)
 */
if ($response['returnCode'] == "0123") {

    // Use Order Check API to confirm the transaction
    $resDetails = $linePay->details([
        'transactionId' => $transactionId,
    ]);

    // Log
    saveLog('Payment Details API', $resDetails);

    if ($resDetails->isSuccessful()) {
        
        // Save the order info to session for confirm
        $_SESSION['linePayOrder'] = [
            'transactionId' => $transactionId,
            'params' => $resDetails["info"][0],
            'isSandbox' => $config['isSandbox'], 
        ];

        // PayInfo sum up
        $amount = 0;
        foreach ($resDetails["info"][0]['payInfo'] as $key => $payInfo) {
            if ($payInfo['method'] != 'DISCOUNT') {
                $amount += $payInfo['amount'];
            }
        }
        $_SESSION['linePayOrder']['params']['amount'] = $amount;
        $_SESSION['linePayOrder']['info'] = $resDetails["info"][0];

        // Code for saving the successful order into your application database...
        $_SESSION['linePayOrder']['isSuccessful'] = true;
    }
}

die("<script>alert('Result:\\nCode: {$response['returnCode']}\\nMessage: {$response['returnMessage']}');history.back();</script>");