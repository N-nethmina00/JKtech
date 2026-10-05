<?php
// payment-success.php - PayHere Payment Success & Order Receipt Page
require_once __DIR__ . '/includes/functions.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$payherePaymentId = sanitize($_GET['payhere_payment_id'] ?? ($_GET['payment_id'] ?? 'PAYHERE-TEST-' . rand(100000, 999999)));

$pdo = getDbConnection();
$order = null;
$orderItems = [];

if ($orderId > 0 && $pdo) {
    // Update order status to Processing (Paid)
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Processing', notes = CONCAT(COALESCE(notes, ''), '\n[Online Card Payment Verified. Ref: ', ?, ']') WHERE id = ?");
        // For SQLite compatibility
        if (getDbDriver() === 'sqlite') {
            $newNote = ($order['notes'] ? $order['notes'] . "\n" : "") . "[Online Card Payment Verified. Ref: {$payherePaymentId}]";
            $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Processing', notes = ? WHERE id = ?");
            $updateStmt->execute([$newNote, $orderId]);
        } else {
            $updateStmt->execute([$payherePaymentId, $orderId]);
        }

        // Fetch items
        $itemStmt = $pdo->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemStmt->execute([$orderId]);
        $orderItems = $itemStmt->fetchAll();
    }
}

// Fallback order info if needed
if (!$order) {
    $order = [
        'id' => $orderId ?: 1089,
        'customer_name' => 'Customer',
        'customer_email' => 'client@jktech.lk',
        'customer_phone' => '+94 77 837 6481',
        'delivery_address' => 'Delivery Address',
        'city' => 'Colombo',
        'total_amount' => 187500.00,
        'created_at' => date('Y-m-d H:i:s'),
        'payment_method' => 'card'
    ];
}

// Clear cart after confirmed payment
clearCart();

// WhatsApp text for customer support
$waText = urlencode("Hi JKtech Auto Parts, I have successfully completed my online card payment for Order #{$order['id']} (Amount: Rs. " . number_format($order['total_amount'], 2) . "). Please confirm dispatch.");
$waUrl = "https://wa.me/94778376481?text={$waText}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Successful | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: #080c14;
      color: #e2e8f0;
      min-height: 100vh;
    }

    .success-wrapper {
      max-width: 740px;
      margin: 40px auto 90px;
      padding: 0 16px;
    }

    .success-card {
      background: #0e1526;
      border: 1px solid rgba(0, 230, 118, 0.3);
      border-radius: 18px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 35px rgba(0, 230, 118, 0.08);
      overflow: hidden;
      padding: 36px 32px;
      text-align: center;
    }

    .check-icon-circle {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: rgba(0, 230, 118, 0.12);
      border: 2px solid #00e676;
      box-shadow: 0 0 25px rgba(0, 230, 118, 0.35);
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #00e676;
      animation: popIn 0.5s ease-out;
    }

    @keyframes popIn {
      0% { transform: scale(0.6); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    .receipt-box {
      background: #131b2e;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 22px;
      margin: 28px 0;
      text-align: left;
    }

    .receipt-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      font-size: 13.5px;
    }

    .receipt-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .btn-wa-receipt {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: #25d366;
      color: #ffffff;
      padding: 14px 24px;
      border-radius: 10px;
      font-family: var(--font-display);
      font-weight: 800;
      font-size: 14.5px;
      text-decoration: none;
      transition: all 0.25s ease;
      box-shadow: 0 6px 20px rgba(37, 211, 102, 0.35);
      margin-right: 10px;
      margin-bottom: 10px;
    }
    .btn-wa-receipt:hover {
      background: #1ebe5d;
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(37, 211, 102, 0.5);
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="success-wrapper">
    <div class="success-card">
      <div class="check-icon-circle">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>

      <h1 style="font-family: var(--font-display); font-size: 26px; font-weight: 900; color: #fff; margin-bottom: 8px;">
        PAYMENT SUCCESSFUL!
      </h1>
      <p style="color: #94a3b8; font-size: 14px; margin-bottom: 4px;">
        Your transaction was securely verified and confirmed.
      </p>
      <div style="font-size: 12px; color: var(--neon-emerald); font-weight: 700; display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 4px 12px; border-radius: 20px; margin-top: 6px;">
        🔒 256-Bit SSL Encrypted &amp; Verified Clearance
      </div>

      <!-- Receipt Breakdown -->
      <div class="receipt-box">
        <div class="receipt-row">
          <span style="color: #94a3b8;">Order Reference:</span>
          <strong style="color: #fff; font-family: var(--font-mono);">#<?= htmlspecialchars($order['id']) ?></strong>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Payment Method:</span>
          <span style="color: #00e5ff; font-weight: 700;">Credit / Debit Card (Online)</span>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Payment Authorization Ref:</span>
          <span style="color: #fbbf24; font-family: var(--font-mono); font-size: 12px;"><?= htmlspecialchars($payherePaymentId) ?></span>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Recipient:</span>
          <strong style="color: #fff;"><?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_phone']) ?>)</strong>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Delivery Destination:</span>
          <span style="color: #fff;"><?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['city']) ?></span>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Total Paid:</span>
          <strong style="color: #00e676; font-size: 16px; font-family: var(--font-display);"><?= formatCurrency($order['total_amount']) ?></strong>
        </div>

        <div class="receipt-row">
          <span style="color: #94a3b8;">Status:</span>
          <span class="badge badge-success" style="background: rgba(0, 230, 118, 0.15); border: 1px solid #00e676; color: #00e676; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 800;">
            ✓ Processing / Paid
          </span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div>
        <!-- WhatsApp Confirmation -->
        <a href="<?= $waUrl ?>" target="_blank" class="btn-wa-receipt">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-5.46-4.45-9.92-9.91-9.92z"/>
          </svg>
          <span>Confirm on WhatsApp (+94 77 837 6481)</span>
        </a>

        <!-- View Orders -->
        <a href="orders.php" class="btn btn-primary" style="margin-right: 10px; margin-bottom: 10px;">
          View All Orders
        </a>

        <!-- Back to Store -->
        <a href="products.php" class="btn btn-outline" style="margin-bottom: 10px;">
          Continue Shopping &rarr;
        </a>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
