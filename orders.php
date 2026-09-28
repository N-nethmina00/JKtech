<?php
// orders.php - Customer Order History & Tracking
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$user = currentUser();
$pdo = getDbConnection();

$orders = [];
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? OR customer_email = ? ORDER BY id DESC");
    $stmt->execute([$user['id'], $user['email']]);
    $orders = $stmt->fetchAll();

    // Fetch items for each order
    foreach ($orders as &$ord) {
        $itemStmt = $pdo->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemStmt->execute([$ord['id']]);
        $ord['items'] = $itemStmt->fetchAll();
    }
} else {
    // Fallback demo order
    $orders = [
        [
            'id' => 1024,
            'created_at' => '2026-09-08 14:32:00',
            'delivery_address' => '128 Kandy Road, Kiribathgoda',
            'city' => 'Kelaniya',
            'payment_method' => 'cod',
            'total_amount' => 187500.00,
            'status' => 'Delivered',
            'items' => [
                [
                    'product_name' => 'Toyota 1NZ-FE VVT-i Engine Assembly',
                    'price' => 185000.00,
                    'quantity' => 1,
                    'subtotal' => 185000.00,
                    'image_url' => 'engine_1.svg'
                ]
            ]
        ]
    ];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders | JK Tech Motors</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .order-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      margin-bottom: 24px;
      overflow: hidden;
    }
    .order-card-header {
      background: #0d131f;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-color);
    }
    .order-card-body {
      padding: 24px;
    }
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: var(--radius-sm);
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-Delivered { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
    .status-Processing { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.4); }
    .status-Pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
    .status-Shipped { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }
    .status-Cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div style="background: #0d131f; border-bottom: 1px solid var(--border-color); padding: 32px 0;">
    <div class="container">
      <h1 style="font-size: 26px; font-weight: 900; color: #fff;">MY ORDERS &amp; DISPATCH STATUS</h1>
      <p style="color: var(--text-muted); font-size: 13px;">Track your imported components from Yokohama container arrival to garage delivery.</p>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="container" style="margin-top: 20px;">
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <div class="container" style="padding: 40px 24px 80px;">
    <?php if (empty($orders)): ?>
      <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 60px 20px; text-align: center;">
        <h3 style="font-size: 18px; color: #fff; margin-bottom: 8px;">No past orders found</h3>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">You have not placed any orders yet.</p>
        <a href="products.php" class="btn btn-primary btn-sm">Explore Parts Directory</a>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $ord): ?>
        <div class="order-card">
          <div class="order-card-header">
            <div>
              <strong style="color: #fff; font-size: 15px;">Order #<?= $ord['id'] ?></strong>
              <span style="font-size: 12px; color: var(--text-muted); margin-left: 12px;">Placed on <?= date('M d, Y', strtotime($ord['created_at'])) ?></span>
            </div>
            <div>
              <span class="status-badge status-<?= $ord['status'] ?>"><?= $ord['status'] ?></span>
            </div>
          </div>

          <div class="order-card-body">
            <div style="margin-bottom: 20px;">
              <?php if (!empty($ord['items'])): ?>
                <?php foreach ($ord['items'] as $item): ?>
                  <div class="flex items-center justify-between" style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                    <div class="flex items-center gap-4">
                      <div style="width: 50px; height: 40px; background: #020617; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                        <img src="<?= htmlspecialchars(getProductImageUrl($item['image_url'] ?? 'engine_1.svg')) ?>" style="width:100%; height:100%; object-fit:cover;">
                      </div>
                      <div>
                        <strong style="color: #fff; font-size: 14px; display: block;"><?= htmlspecialchars($item['product_name']) ?></strong>
                        <span style="font-size: 12px; color: var(--text-muted);">Qty: <?= $item['quantity'] ?> × <?= formatCurrency($item['price']) ?></span>
                      </div>
                    </div>
                    <span style="font-weight: 700; color: #fff;"><?= formatCurrency($item['subtotal']) ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <div class="flex justify-between items-center" style="font-size: 13px; color: var(--text-muted);">
              <div>
                <strong>Destination:</strong> <?= htmlspecialchars($ord['delivery_address']) ?>, <?= htmlspecialchars($ord['city']) ?>
                <span style="margin-left: 14px;"><strong>Payment Mode:</strong> <?= strtoupper($ord['payment_method']) ?></span>
              </div>
              <div>
                <strong style="font-size: 16px; color: #fff;">Total: <?= formatCurrency($ord['total_amount']) ?></strong>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
