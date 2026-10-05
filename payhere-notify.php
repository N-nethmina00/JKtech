<?php
// payhere-notify.php - Server-to-Server IPN Notification Listener for PayHere Gateway
require_once __DIR__ . '/includes/functions.php';
$payhereConfig = require __DIR__ . '/config/payhere.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$merchant_id      = $_POST['merchant_id'] ?? '';
$order_id         = $_POST['order_id'] ?? '';
$payhere_amount   = $_POST['payhere_amount'] ?? '';
$payhere_currency = $_POST['payhere_currency'] ?? '';
$status_code      = $_POST['status_code'] ?? '';
$md5sig           = $_POST['md5sig'] ?? '';
$payment_id       = $_POST['payment_id'] ?? '';
$method           = $_POST['method'] ?? 'CARD';
$status_message   = $_POST['status_message'] ?? '';

// Verify security checksum
$merchant_secret = $payhereConfig['merchant_secret'];
$local_md5sig = verifyPayHereSignature($merchant_id, $order_id, $payhere_amount, $payhere_currency, $status_code, $merchant_secret);

if (($local_md5sig === $md5sig) && ($status_code == 2)) {
    // Payment SUCCESS
    $pdo = getDbConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([(int)$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            $note = ($order['notes'] ? $order['notes'] . "\n" : "") . "[PayHere IPN Verified: Paid via {$method}. TxID: {$payment_id}]";
            $up = $pdo->prepare("UPDATE orders SET status = 'Processing', notes = ? WHERE id = ?");
            $up->execute([$note, (int)$order_id]);
        }
    }

    http_response_code(200);
    echo "OK";
    exit;
} elseif ($status_code == 0) {
    // Payment Pending
    http_response_code(200);
    echo "PENDING";
    exit;
} else {
    // Failed or invalid signature
    http_response_code(400);
    echo "FAILED_OR_INVALID_SIGNATURE";
    exit;
}
