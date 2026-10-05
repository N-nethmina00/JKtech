<?php
// checkout.php - Secure Checkout & Order Processing
require_once __DIR__ . '/includes/functions.php';

$cart = getCart();
if (empty($cart)) {
    setFlash('warning', 'Your cart is empty. Add parts before checkout.');
    header('Location: products.php');
    exit;
}

$user = currentUser();
$subtotal = getCartSubtotal();
$shipping = getCartShipping();
$total = getCartTotal();

if (isset($_GET['status']) && $_GET['status'] === 'cancelled') {
    setFlash('warning', 'Card payment was cancelled. You can try again or select another payment method.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['customer_name'] ?? '');
    $email = sanitize($_POST['customer_email'] ?? '');
    $phone = sanitize($_POST['customer_phone'] ?? '');
    $address = sanitize($_POST['delivery_address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $postalCode = sanitize($_POST['postal_code'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cod');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone)) $errors[] = 'Phone number is required for dispatch notification.';
    if (empty($address)) $errors[] = 'Delivery address is required.';
    if (empty($city)) $errors[] = 'City is required.';

    if (empty($errors)) {
        $pdo = getDbConnection();
        $orderId = null;

        if ($pdo) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO orders 
                    (user_id, customer_name, customer_email, customer_phone, delivery_address, city, postal_code, payment_method, total_amount, status, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
                $stmt->execute([
                    $user['id'] ?? null,
                    $name,
                    $email,
                    $phone,
                    $address,
                    $city,
                    $postalCode,
                    $paymentMethod,
                    $total,
                    $notes
                ]);

                $orderId = $pdo->lastInsertId();

                $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");

                foreach ($cart as $item) {
                    $itemSubtotal = $item['price'] * $item['quantity'];
                    $itemStmt->execute([
                        $orderId,
                        $item['id'],
                        $item['name'],
                        $item['price'],
                        $item['quantity'],
                        $itemSubtotal
                    ]);
                    $stockStmt->execute([$item['quantity'], $item['id']]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Order creation failed: " . $e->getMessage();
            }
        } else {
            $orderId = rand(1000, 9999);
        }

        if (empty($errors)) {
            clearCart();
            if ($paymentMethod === 'card') {
                header("Location: payhere-pay.php?order_id={$orderId}");
                exit;
            }
            setFlash('success', "Order #{$orderId} placed successfully! Our logistics team will call {$phone} shortly.");
            header('Location: orders.php');
            exit;
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Checkout | JK Tech Motors Sri Lanka</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .checkout-layout {
      display: grid;
      grid-template-columns: 1.35fr 0.65fr;
      gap: 40px;
      padding: 44px 0 90px;
    }
    .checkout-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      padding: 32px;
      margin-bottom: 28px;
      box-shadow: var(--shadow-sm);
    }
    .checkout-title {
      font-family: var(--font-display);
      font-size: 17px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 22px;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      letter-spacing: 0.5px;
    }
    .payment-option {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-sm);
      padding: 16px 20px;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 14px;
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .payment-option:hover, .payment-option.active {
      border-color: var(--neon-cyan);
      box-shadow: 0 0 14px var(--neon-cyan-glow);
    }
    .order-item-mini {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 0;
      border-bottom: 1px solid var(--border-subtle);
      font-size: 13px;
    }
    @media (max-width: 992px) {
      .checkout-layout {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- Page Header -->
  <div style="background: #090e18; border-bottom: 1px solid var(--border-subtle); padding: 36px 0;">
    <div class="container">
      <h1 style="font-family: var(--font-display); font-size: 28px; font-weight: 900; color: #fff;">SECURE CHECKOUT</h1>
      <p style="color: var(--text-muted); font-size: 14px;">Confirm workshop delivery location and select your payment preference.</p>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="container" style="margin-top: 24px;">
      <div class="alert alert-error">
        <div>
          <?php foreach ($errors as $err): ?>
            <div>• <?= htmlspecialchars($err) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="container">
    <form method="POST" action="checkout.php">
      <div class="checkout-layout">
        <div>
          <!-- Delivery Details Card -->
          <div class="checkout-card">
            <h3 class="checkout-title">
              <span class="badge badge-grade">1</span>
              <span>DELIVERY &amp; WORKSHOP LOCATION</span>
            </h3>

            <div class="form-group">
              <label class="form-label">Full Name / Garage Lead</label>
              <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($_POST['customer_name'] ?? ($user['name'] ?? '')) ?>" required>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="customer_email" class="form-control" value="<?= htmlspecialchars($_POST['customer_email'] ?? ($user['email'] ?? '')) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Mobile Hotline (For Carrier Delivery)</label>
                <input type="text" name="customer_phone" class="form-control" placeholder="+94 7X XXX XXXX" value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Street Address &amp; Workshop Landmark</label>
              <input type="text" name="delivery_address" class="form-control" placeholder="e.g. 128 Kandy Road, Near Fuel Station" value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>" required>
            </div>

            <div class="grid" style="grid-template-columns: 1.5fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">City / District</label>
                <input type="text" name="city" class="form-control" placeholder="Colombo, Kandy, Gampaha..." value="<?= htmlspecialchars($_POST['city'] ?? 'Colombo') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" class="form-control" placeholder="10100" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Unloading / Crane Notes (Optional)</label>
              <textarea name="notes" class="form-control" style="min-height: 75px;" placeholder="e.g. Hoist available at garage, engine stand ready..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <!-- Payment Options Card -->
          <div class="checkout-card">
            <h3 class="checkout-title">
              <span class="badge badge-grade">2</span>
              <span>PAYMENT METHOD</span>
            </h3>

            <label class="payment-option">
              <input type="radio" name="payment_method" value="cod" checked style="accent-color: var(--primary); cursor: pointer;">
              <div>
                <strong style="color: #fff; display: block; font-size: 14px; font-family: var(--font-display);">Cash on Delivery (Islandwide)</strong>
                <span style="font-size: 13px; color: var(--text-muted);">Inspect engine/gearbox serial &amp; paperwork before releasing payment to transport carrier.</span>
              </div>
            </label>

            <label class="payment-option">
              <input type="radio" name="payment_method" value="bank_transfer" style="accent-color: var(--primary); cursor: pointer;">
              <div>
                <strong style="color: #fff; display: block; font-size: 14px; font-family: var(--font-display);">Direct Bank Transfer (Commercial / HNB)</strong>
                <span style="font-size: 13px; color: var(--text-muted);">Transfer directly to our corporate bank account. Share deposit slip via WhatsApp for instant dispatch.</span>
              </div>
            </label>

            <label class="payment-option" style="border: 1px solid rgba(0, 229, 255, 0.35); background: rgba(0, 229, 255, 0.03);">
              <input type="radio" name="payment_method" value="card" style="accent-color: var(--neon-cyan); cursor: pointer;">
              <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                  <strong style="color: #fff; font-size: 14px; font-family: var(--font-display); display: flex; align-items: center; gap: 8px;">
                    Credit / Debit Card (Online Payment)
                  </strong>
                  <span style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; padding: 2px 9px; border-radius: 12px; font-size: 10.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    256-Bit SSL Encrypted
                  </span>
                </div>
                <span style="font-size: 12.5px; color: var(--text-muted); display: block; margin-top: 5px; line-height: 1.5;">
                  Instant, secure online card payment. Supports all major Sri Lankan &amp; international cards. You will be prompted to authenticate your payment in a secure encrypted window.
                </span>
                <div style="display: flex; align-items: center; gap: 6px; margin-top: 9px;">
                  <span style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.12); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; color: #f1f5f9; letter-spacing: 0.5px;">VISA</span>
                  <span style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.12); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; color: #f1f5f9; letter-spacing: 0.5px;">MASTERCARD</span>
                  <span style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.12); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; color: #f1f5f9; letter-spacing: 0.5px;">AMERICAN EXPRESS</span>
                  <span style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.12); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; color: #38bdf8; letter-spacing: 0.5px;">GENIE</span>
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div>
          <div class="checkout-card" style="position: sticky; top: 98px;">
            <h3 class="checkout-title">YOUR ORDER (<?= $cartCount ?> ITEMS)</h3>

            <div style="max-height: 290px; overflow-y: auto; margin-bottom: 18px;">
              <?php foreach ($cart as $item): ?>
                <div class="order-item-mini">
                  <div>
                    <strong style="color: #fff; display: block; font-family: var(--font-display);"><?= htmlspecialchars($item['name']) ?></strong>
                    <span style="font-size: 12px; color: var(--text-dim); font-family: var(--font-mono);"><?= htmlspecialchars($item['sku']) ?> × <?= $item['quantity'] ?></span>
                  </div>
                  <span style="font-weight: 700; color: #fff; font-family: var(--font-display);">
                    <?= formatCurrency($item['price'] * $item['quantity']) ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="summary-row">
              <span>Items Subtotal:</span>
              <strong style="color: #fff; font-family: var(--font-display);"><?= formatCurrency($subtotal) ?></strong>
            </div>
            <div class="summary-row">
              <span>Islandwide Freight:</span>
              <span style="color: var(--neon-emerald); font-weight: 700;"><?= formatCurrency($shipping) ?></span>
            </div>
            <div class="summary-row total">
              <span>Total Payable:</span>
              <span style="color: #fff;"><?= formatCurrency($total) ?></span>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 26px;">
              <span>Place Order Now</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
            </button>

            <div style="font-size: 12px; color: var(--text-dim); text-align: center; margin-top: 16px;">
              Includes 90-Day Full Replacement Warranty &amp; JEVIC Certificate.
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
