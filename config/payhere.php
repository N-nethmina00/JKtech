<?php
// config/payhere.php - PayHere Payment Gateway Configuration
// Integrated with user's PayHere Sandbox account (Merchant ID: 1238339)

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$baseUrl = $protocol . $host . ($scriptDir === '/' || $scriptDir === '.' ? '' : $scriptDir);

return [
    'sandbox'         => true,
    'merchant_id'     => '1238339', // Your PayHere Sandbox Merchant ID
    'merchant_secret' => 'OTAwOTc2MDEzMTY5ODA2MDUwNzEzMjYyMjUxNjMyMzM3MjM3NjY3', // Full PayHere Merchant Secret from Dashboard
    'currency'        => 'LKR',
    'app_name'        => 'JKtech.LK AutoParts',
    'checkout_url'    => 'https://sandbox.payhere.lk/pay/checkout',
    'js_sdk_url'      => 'https://www.payhere.lk/lib/payhere.js',
    'return_url'      => $baseUrl . '/payment-success.php',
    'cancel_url'      => $baseUrl . '/checkout.php?status=cancelled',
    'notify_url'      => $baseUrl . '/payhere-notify.php',
];

/**
 * Generate PayHere MD5 security hash
 * Formula: strtoupper(md5(merchant_id + order_id + amount + currency + strtoupper(md5(merchant_secret))))
 */
function generatePayHereHash($merchantId, $orderId, $amount, $currency, $merchantSecret) {
    $merchantId = trim((string)$merchantId);
    $orderId = trim((string)$orderId);
    $formattedAmount = number_format((float)$amount, 2, '.', '');
    $currency = trim((string)$currency);
    $hashedSecret = strtoupper(md5(trim((string)$merchantSecret)));
    return strtoupper(md5($merchantId . $orderId . $formattedAmount . $currency . $hashedSecret));
}

/**
 * Verify PayHere IPN notification signature
 * Formula: strtoupper(md5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + strtoupper(md5(merchant_secret))))
 */
function verifyPayHereSignature($merchantId, $orderId, $payhereAmount, $payhereCurrency, $statusCode, $merchantSecret) {
    $hashedSecret = strtoupper(md5($merchantSecret));
    return strtoupper(md5($merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . $hashedSecret));
}
