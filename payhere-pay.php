<?php
// payhere-pay.php - Secure Payment Authorization Gateway
require_once __DIR__ . '/includes/functions.php';
$payhereConfig = require __DIR__ . '/config/payhere.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    setFlash('error', 'Invalid order reference for payment.');
    header('Location: index.php');
    exit;
}

$pdo = getDbConnection();
$order = null;
$orderItems = [];

if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$orderId]);
        $orderItems = $itemStmt->fetchAll();
    }
}

// Fallback demo order if DB offline
if (!$order) {
    $order = [
        'id' => $orderId,
        'customer_name' => 'Valued Customer',
        'customer_email' => 'customer@gmail.com',
        'customer_phone' => '+94 77 837 6481',
        'delivery_address' => '128 Kandy Road',
        'city' => 'Colombo',
        'total_amount' => 187500.00,
        'status' => 'Pending'
    ];
}

// If already paid
if ($order['status'] === 'Processing' || $order['status'] === 'Delivered' || $order['status'] === 'Shipped') {
    header("Location: payment-success.php?order_id={$orderId}");
    exit;
}

// Customer Name formatting
$nameParts = explode(' ', trim($order['customer_name']), 2);
$firstName = $nameParts[0] ?? 'Valued';
$lastName  = $nameParts[1] ?? 'Customer';

// Order summary item text
$itemNames = [];
foreach ($orderItems as $it) {
    $itemNames[] = $it['product_name'] . " (x" . $it['quantity'] . ")";
}
$itemsSummary = !empty($itemNames) ? implode(', ', $itemNames) : "JKtech JDM Parts Order #{$orderId}";
if (strlen($itemsSummary) > 250) {
    $itemsSummary = substr($itemsSummary, 0, 247) . '...';
}

$amount = number_format((float)$order['total_amount'], 2, '.', '');
$currency = $payhereConfig['currency'];
$merchantId = $payhereConfig['merchant_id'];
$merchantSecret = $payhereConfig['merchant_secret'];

// Generate PayHere MD5 security hash
$hash = generatePayHereHash($merchantId, $orderId, $amount, $currency, $merchantSecret);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Card Payment | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Official PayHere Payment SDK -->
  <script type="text/javascript" src="<?= htmlspecialchars($payhereConfig['js_sdk_url']) ?>"></script>
  <style>
    body {
      background: #080c14;
      color: #e2e8f0;
      min-height: 100vh;
    }

    .checkout-flow-wrap {
      max-width: 960px;
      margin: 36px auto 90px;
      padding: 0 16px;
    }

    /* Checkout Stepper */
    .flow-stepper {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 16px;
      margin-bottom: 28px;
    }

    .step-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #64748b;
      font-weight: 700;
    }

    .step-item.active {
      color: #00e5ff;
    }

    .step-circle {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: #131b2e;
      border: 1px solid #334155;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-family: var(--font-mono);
    }

    .step-item.active .step-circle {
      background: rgba(0, 229, 255, 0.15);
      border-color: #00e5ff;
      color: #00e5ff;
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.4);
    }

    .step-line {
      width: 32px;
      height: 2px;
      background: #1e293b;
    }

    /* Main Terminal Card */
    .terminal-card {
      background: #0d1424;
      border: 1px solid rgba(0, 229, 255, 0.22);
      border-radius: 18px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 35px rgba(0, 229, 255, 0.04);
      overflow: hidden;
    }

    .terminal-header {
      background: linear-gradient(135deg, #101a30 0%, #090e1a 100%);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding: 22px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .terminal-branding {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .terminal-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #34d399;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.5px;
    }

    .terminal-body {
      padding: 34px 32px;
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
      gap: 36px;
    }

    @media (max-width: 768px) {
      .terminal-body {
        grid-template-columns: 1fr;
        padding: 22px 18px;
      }
      .terminal-header {
        padding: 18px 20px;
      }
    }

    /* Left Card: Virtual Card Display & Authorization */
    .section-headline {
      font-family: var(--font-display);
      font-size: 14.5px;
      font-weight: 800;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Virtual Metallic Card Visual */
    .virtual-card-visual {
      background: linear-gradient(135deg, #18253f 0%, #0c1527 100%);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 14px;
      padding: 22px 24px;
      position: relative;
      overflow: hidden;
      margin-bottom: 24px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .virtual-card-visual::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(0, 229, 255, 0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .card-top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 22px;
    }

    .chip-graphic {
      width: 36px;
      height: 26px;
      background: linear-gradient(135deg, #d4af37 0%, #aa8012 100%);
      border-radius: 5px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.4);
    }

    .card-num-preview {
      font-family: var(--font-mono);
      font-size: 18px;
      letter-spacing: 3px;
      color: #f1f5f9;
      margin-bottom: 20px;
    }

    .card-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }

    .card-holder-label {
      font-size: 9.5px;
      text-transform: uppercase;
      color: #94a3b8;
      letter-spacing: 0.8px;
      margin-bottom: 3px;
    }

    .card-holder-name {
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
    }

    /* Pay Button */
    .btn-pay-now {
      width: 100%;
      height: 54px;
      background: linear-gradient(135deg, #00e5ff 0%, #0077ff 100%);
      color: #040914;
      font-family: var(--font-display);
      font-size: 16px;
      font-weight: 900;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      box-shadow: 0 10px 28px rgba(0, 229, 255, 0.35);
      transition: all 0.25s ease;
      letter-spacing: 0.5px;
    }

    .btn-pay-now:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 35px rgba(0, 229, 255, 0.55);
      color: #000;
    }

    /* Trust & Security Badges */
    .trust-badges-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 22px;
      padding-top: 18px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .trust-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 11.5px;
      color: #94a3b8;
    }

    .trust-item svg {
      color: #00e5ff;
      flex-shrink: 0;
    }

    /* Right Card: Order Summary */
    .summary-box {
      background: #111a2e;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      padding: 22px;
    }

    .order-item-line {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      font-size: 13px;
    }

    .order-item-line:last-child {
      border-bottom: none;
    }

    .amount-highlight {
      background: rgba(0, 229, 255, 0.08);
      border: 1px solid rgba(0, 229, 255, 0.25);
      border-radius: 10px;
      padding: 16px 18px;
      margin: 18px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .amount-val {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 900;
      color: #00e5ff;
    }

    .customer-info-block {
      background: rgba(255, 255, 255, 0.03);
      border-radius: 8px;
      padding: 12px 14px;
      margin-top: 16px;
      font-size: 12.5px;
      color: #94a3b8;
      line-height: 1.6;
    }

    /* Developer Sandbox Accordion (Clean, Collapsed by Default) */
    .dev-drawer {
      margin-top: 24px;
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 8px;
      background: rgba(0, 0, 0, 0.2);
    }

    .dev-drawer summary {
      padding: 10px 14px;
      font-size: 11px;
      color: #64748b;
      cursor: pointer;
      user-select: none;
      font-weight: 600;
    }

    .dev-drawer summary:hover {
      color: #94a3b8;
    }

    .dev-drawer-content {
      padding: 10px 14px 14px;
      font-size: 11.5px;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.04);
      line-height: 1.7;
    }

    .dev-drawer-content code {
      color: #00e5ff;
      font-family: var(--font-mono);
      background: rgba(0, 229, 255, 0.1);
      padding: 2px 6px;
      border-radius: 4px;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="checkout-flow-wrap">

    <!-- Stepper Navigation -->
    <div class="flow-stepper">
      <div class="step-item">
        <span class="step-circle">✓</span>
        <span>Cart</span>
      </div>
      <div class="step-line"></div>
      <div class="step-item">
        <span class="step-circle">✓</span>
        <span>Shipping</span>
      </div>
      <div class="step-line"></div>
      <div class="step-item active">
        <span class="step-circle">3</span>
        <span>Payment Clearance</span>
      </div>
      <div class="step-line"></div>
      <div class="step-item">
        <span class="step-circle">4</span>
        <span>Confirmed</span>
      </div>
    </div>

    <!-- Main Payment Terminal -->
    <div class="terminal-card">
      <div class="terminal-header">
        <div class="terminal-branding">
          <img src="assets/images/logo_circle.svg" alt="JKtech Logo" style="width: 32px; height: 32px;">
          <div>
            <div style="font-family: var(--font-display); font-size: 15px; font-weight: 900; color: #fff; letter-spacing: 0.5px;">
              JKtech.LK
            </div>
            <div style="font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.8px;">
              SECURE CARD PAYMENT GATEWAY
            </div>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
          <div class="terminal-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span>256-Bit SSL Encrypted</span>
          </div>
          <div style="font-size: 12.5px; color: #94a3b8; font-family: var(--font-mono);">
            Ref: <strong style="color: #fff;">#<?= sprintf('%05d', $orderId) ?></strong>
          </div>
        </div>
      </div>

      <div class="terminal-body">
        <!-- Left Column: Card Authorization -->
        <div>
          <div class="section-headline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Payment Authorization</span>
          </div>

          <!-- Virtual Metallic Card Representation -->
          <div class="virtual-card-visual">
            <div class="card-top-row">
              <div class="chip-graphic"></div>
              <div style="display: flex; gap: 8px; align-items: center;">
                <span style="background: rgba(255, 255, 255, 0.15); border-radius: 4px; padding: 2px 7px; font-size: 9.5px; font-weight: 800; color: #fff; letter-spacing: 0.5px;">VISA</span>
                <span style="background: rgba(255, 255, 255, 0.15); border-radius: 4px; padding: 2px 7px; font-size: 9.5px; font-weight: 800; color: #fff; letter-spacing: 0.5px;">MASTERCARD</span>
                <span style="background: rgba(255, 255, 255, 0.15); border-radius: 4px; padding: 2px 7px; font-size: 9.5px; font-weight: 800; color: #38bdf8; letter-spacing: 0.5px;">AMEX</span>
              </div>
            </div>

            <div class="card-num-preview">
              •••• &nbsp; •••• &nbsp; •••• &nbsp; <?= sprintf('%04d', $orderId) ?>
            </div>

            <div class="card-meta-row">
              <div>
                <div class="card-holder-label">Order Account Holder</div>
                <div class="card-holder-name"><?= htmlspecialchars($order['customer_name']) ?></div>
              </div>
              <div style="text-align: right;">
                <div class="card-holder-label">Amount Payable</div>
                <div style="font-family: var(--font-display); font-size: 15px; font-weight: 800; color: #00e5ff;">
                  <?= formatCurrency($order['total_amount']) ?>
                </div>
              </div>
            </div>
          </div>

          <p style="color: #94a3b8; font-size: 13.5px; line-height: 1.6; margin-bottom: 22px;">
            Click below to open the secure payment verification dialog. You can authenticate your payment using any valid Visa, Mastercard, or American Express card.
          </p>

          <!-- Primary Trigger Button -->
          <button type="button" class="btn-pay-now" id="payButton" onclick="triggerPaymentModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span>Complete Secure Card Payment</span>
          </button>

          <!-- Standard Fallback Form (Hidden / Optional) -->
          <form method="POST" action="<?= htmlspecialchars($payhereConfig['checkout_url']) ?>" id="securePayFallbackForm" style="display: none;">
            <input type="hidden" name="merchant_id" value="<?= htmlspecialchars($merchantId) ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($payhereConfig['return_url'] . '?order_id=' . $orderId) ?>">
            <input type="hidden" name="cancel_url" value="<?= htmlspecialchars($payhereConfig['cancel_url'] . '&order_id=' . $orderId) ?>">
            <input type="hidden" name="notify_url" value="<?= htmlspecialchars($payhereConfig['notify_url']) ?>">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId) ?>">
            <input type="hidden" name="items" value="<?= htmlspecialchars($itemsSummary) ?>">
            <input type="hidden" name="currency" value="<?= htmlspecialchars($currency) ?>">
            <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">
            <input type="hidden" name="first_name" value="<?= htmlspecialchars($firstName) ?>">
            <input type="hidden" name="last_name" value="<?= htmlspecialchars($lastName) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($order['customer_email']) ?>">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($order['customer_phone']) ?>">
            <input type="hidden" name="address" value="<?= htmlspecialchars($order['delivery_address']) ?>">
            <input type="hidden" name="city" value="<?= htmlspecialchars($order['city']) ?>">
            <input type="hidden" name="country" value="Sri Lanka">
            <input type="hidden" name="hash" value="<?= htmlspecialchars($hash) ?>">
          </form>

          <div style="text-align: center; margin-top: 14px;">
            <button type="button" onclick="document.getElementById('securePayFallbackForm').submit()" style="background: none; border: none; color: #64748b; font-size: 12px; cursor: pointer; text-decoration: underline;">
              Having trouble with the popup? Use standard hosted checkout &rarr;
            </button>
          </div>

          <!-- Trust Badges -->
          <div class="trust-badges-grid">
            <div class="trust-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Bank-Grade 3D Secure OTP</span>
            </div>
            <div class="trust-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>PCI-DSS Level 1 Compliant</span>
            </div>
            <div class="trust-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Instant WhatsApp Confirmation</span>
            </div>
            <div class="trust-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Zero Surcharge on Local Cards</span>
            </div>
          </div>

          <!-- Discrete Testing Reference (Collapsed by Default) -->
          <details class="dev-drawer">
            <summary>Developer Testing Reference (Click to expand)</summary>
            <div class="dev-drawer-content">
              <div>• Visa Card: <code>4916 2175 0161 1292</code></div>
              <div>• Mastercard: <code>5307 7321 2553 1191</code></div>
              <div>• Expiry: <code>12/28</code> (or any future date) &nbsp;|&nbsp; CVV: <code>123</code></div>
            </div>
          </details>
        </div>

        <!-- Right Column: Order Summary -->
        <div>
          <div class="section-headline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>Order Summary</span>
          </div>

          <div class="summary-box">
            <?php if (!empty($orderItems)): ?>
              <div style="margin-bottom: 14px;">
                <div style="font-size: 11px; text-transform: uppercase; color: var(--text-dim); letter-spacing: 0.8px; margin-bottom: 8px;">
                  Purchased Items (<?= count($orderItems) ?>)
                </div>
                <?php foreach ($orderItems as $item): ?>
                  <div class="order-item-line">
                    <div>
                      <strong style="color: #fff; display: block; font-family: var(--font-display);"><?= htmlspecialchars($item['product_name']) ?></strong>
                      <span style="font-size: 11.5px; color: var(--text-dim); font-family: var(--font-mono);">Qty: <?= $item['quantity'] ?></span>
                    </div>
                    <span style="color: #f1f5f9; font-weight: 700; font-family: var(--font-mono);">
                      <?= formatCurrency($item['subtotal'] ?? ($item['price'] * $item['quantity'])) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="amount-highlight">
              <div>
                <div style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.5px;">Total Payable</div>
                <div style="font-size: 11.5px; color: var(--neon-emerald); font-weight: 700;">Freight: Free Islandwide</div>
              </div>
              <div class="amount-val"><?= formatCurrency($order['total_amount']) ?></div>
            </div>

            <!-- Customer & Delivery Destination -->
            <div class="customer-info-block">
              <div style="font-weight: 700; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <?= htmlspecialchars($order['customer_name']) ?>
              </div>
              <div>📞 <?= htmlspecialchars($order['customer_phone']) ?></div>
              <div>📍 <?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['city']) ?></div>
            </div>

            <div style="margin-top: 18px; text-align: center;">
              <a href="checkout.php" style="color: #64748b; font-size: 12.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                &larr; Return to Checkout
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // PayHere Payment Object configuration
    var payment = {
      "sandbox": true,
      "merchant_id": "<?= htmlspecialchars($merchantId) ?>",
      "return_url": "<?= htmlspecialchars($payhereConfig['return_url'] . '?order_id=' . $orderId) ?>",
      "cancel_url": "<?= htmlspecialchars($payhereConfig['cancel_url'] . '&order_id=' . $orderId) ?>",
      "notify_url": "<?= htmlspecialchars($payhereConfig['notify_url']) ?>",
      "order_id": "<?= htmlspecialchars($orderId) ?>",
      "items": "<?= htmlspecialchars(addslashes($itemsSummary)) ?>",
      "amount": "<?= htmlspecialchars($amount) ?>",
      "currency": "<?= htmlspecialchars($currency) ?>",
      "hash": "<?= htmlspecialchars($hash) ?>",
      "first_name": "<?= htmlspecialchars(addslashes($firstName)) ?>",
      "last_name": "<?= htmlspecialchars(addslashes($lastName)) ?>",
      "email": "<?= htmlspecialchars(addslashes($order['customer_email'])) ?>",
      "phone": "<?= htmlspecialchars(addslashes($order['customer_phone'])) ?>",
      "address": "<?= htmlspecialchars(addslashes($order['delivery_address'])) ?>",
      "city": "<?= htmlspecialchars(addslashes($order['city'])) ?>",
      "country": "Sri Lanka"
    };

    // PayHere Event Callbacks
    payhere.onCompleted = function onCompleted(orderId) {
      console.log("Payment completed for order " + orderId);
      window.location.href = "payment-success.php?order_id=" + orderId + "&status=completed";
    };

    payhere.onDismissed = function onDismissed() {
      console.log("Payment window dismissed by user.");
    };

    payhere.onError = function onError(error) {
      console.error("Payment Gateway Notice:", error);
      alert("Payment Gateway Notice: " + error);
    };

    // Trigger Popup
    function triggerPaymentModal() {
      try {
        if (typeof payhere !== 'undefined') {
          payhere.startPayment(payment);
        } else {
          document.getElementById('securePayFallbackForm').submit();
        }
      } catch (err) {
        document.getElementById('securePayFallbackForm').submit();
      }
    }
  </script>
</body>
</html>
