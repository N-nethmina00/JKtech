<?php
// admin/orders.php - Order Fulfillment & Status Management
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();
$dbStatus = getDbStatus();

// Handle Status Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitize($_POST['status'] ?? 'Pending');

    if ($pdo) {
        $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $up->execute([$newStatus, $orderId]);
        setFlash('success', "Order #{$orderId} status updated to {$newStatus}.");
    } else {
        setFlash('success', "Order #{$orderId} status updated (Demo mode).");
    }
    header('Location: orders.php');
    exit;
}

// Handle Delete Order POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_order') {
    $orderId = (int)$_POST['order_id'];
    if ($pdo) {
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        setFlash('info', "Order #{$orderId} deleted from database.");
    } else {
        setFlash('info', "Order #{$orderId} deleted (Demo mode).");
    }
    header('Location: orders.php');
    exit;
}

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$orders = [];

if ($pdo) {
    $sql = "SELECT * FROM orders";
    $params = [];
    if (!empty($statusFilter)) {
        $sql .= " WHERE status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$ord) {
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$ord['id']]);
        $ord['items'] = $itemStmt->fetchAll();
    }
} else {
    // Fallback demo orders
    $orders = [
        [
            'id' => 1047,
            'customer_name' => 'Sampath Auto',
            'customer_email' => 'sampath@auto.lk',
            'customer_phone' => '+94 77 111 2233',
            'delivery_address' => '45 William Gopallawa Mawatha',
            'city' => 'Kandy',
            'payment_method' => 'cod',
            'total_amount' => 187500.00,
            'status' => 'Delivered',
            'created_at' => '2026-09-10 10:14:00',
            'items' => [['product_name' => 'Toyota 1NZ-FE VVT-i Engine Assembly', 'price' => 185000, 'quantity' => 1, 'subtotal' => 185000]]
        ],
        [
            'id' => 1046,
            'customer_name' => 'Niroshan Ranasinghe',
            'customer_email' => 'niro@gmail.com',
            'customer_phone' => '+94 71 222 3344',
            'delivery_address' => '12 Havelock Road',
            'city' => 'Colombo 05',
            'payment_method' => 'bank_transfer',
            'total_amount' => 482500.00,
            'status' => 'Processing',
            'created_at' => '2026-09-09 16:20:00',
            'items' => [['product_name' => 'Honda K20A Type-R Red Top Engine', 'price' => 480000, 'quantity' => 1, 'subtotal' => 480000]]
        ],
        [
            'id' => 1045,
            'customer_name' => 'Dammika Motors',
            'customer_email' => 'dammika@gmail.com',
            'customer_phone' => '+94 76 333 4455',
            'delivery_address' => '88 Miriswatta Junction',
            'city' => 'Gampaha',
            'payment_method' => 'cod',
            'total_amount' => 97500.00,
            'status' => 'Pending',
            'created_at' => '2026-09-09 11:05:00',
            'items' => [['product_name' => 'Nissan HR15DE Auto Transmission', 'price' => 95000, 'quantity' => 1, 'subtotal' => 95000]]
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
  <title>Orders &amp; Dispatch | JK Tech Motors Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .admin-container {
      display: grid;
      grid-template-columns: 240px 1fr;
      min-height: 100vh;
    }
    .admin-sidebar {
      background: #090d16;
      border-right: 1px solid var(--border-color);
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
    }
    .admin-menu {
      list-style: none;
      margin-top: 28px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex: 1;
    }
    .admin-menu a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
    }
    .admin-menu a:hover, .admin-menu a.active {
      background: var(--bg-card);
      color: #fff;
      border-left: 3px solid var(--accent-cyan);
    }
    .admin-main {
      padding: 32px 40px;
      background: var(--bg-main);
      overflow-y: auto;
    }
    .order-row-box {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .order-row-top {
      background: #0d131f;
      padding: 14px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-color);
    }
    .order-row-content {
      padding: 20px;
    }
    .status-select {
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      color: #fff;
      padding: 4px 8px;
      border-radius: var(--radius-sm);
      font-size: 12px;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <div class="admin-container">
    <aside class="admin-sidebar">
      <div style="padding: 10px 6px 14px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 14px;">
        <a href="index.php" class="brand-badge" style="display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 10px;" title="JKtech.LK Admin Dashboard">
          <img src="../assets/images/logo_circle.svg" alt="JKtech.LK" class="brand-emblem-circle" style="width: 38px; height: 38px;">
          <span class="brand-text" style="font-size: 20px;">JKtech<span class="brand-domain">.LK</span></span>
        </a>
        <div style="font-size: 10.5px; color: var(--neon-cyan, #00e5ff); font-weight: 800; letter-spacing: 1.2px; font-family:var(--font-display); padding-left: 2px;">MANAGEMENT CONSOLE</div>
      </div>

      <ul class="admin-menu">
        <li><a href="index.php">📊 Dashboard</a></li>
        <li><a href="products.php">🔧 Products &amp; Spares</a></li>
        <li><a href="orders.php" class="active">📦 Orders &amp; Dispatch</a></li>
        <li><a href="inventory.php">⚙️ Inventory &amp; Stock</a></li>
        <li><a href="customers.php">👥 Garages &amp; Clients</a></li>
      </ul>

      <div style="padding: 16px 8px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px;">
        <a href="../index.php" class="btn btn-outline btn-sm" style="width: 100%;">🌐 View Main Website</a>
        <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; color:#ef4444;">Sign Out</a>
      </div>
    </aside>

    <main class="admin-main">
      <div class="flex justify-between items-center" style="margin-bottom: 28px; flex-wrap: wrap; gap: 14px;">
        <div>
          <h1 style="font-size: 26px; font-weight: 900; color: #fff;">GARAGE ORDERS &amp; FULFILLMENT</h1>
          <p style="font-size: 13px; color: var(--text-muted);">Process, track status, and manage imported engines &amp; spares orders</p>
        </div>
        <div class="flex items-center gap-3">
          <div style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-family:var(--font-mono); font-weight:700; color:var(--neon-cyan); background:rgba(6,182,212,0.1); border:1px solid rgba(6,182,212,0.3); padding:6px 12px; border-radius:var(--radius-pill);">
            <span><?= htmlspecialchars($dbStatus['badge']) ?></span>
          </div>

          <select id="adminOrderStatusFilter" class="form-control" style="width: 180px;">
            <option value="">All Order Statuses</option>
            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Processing" <?= $statusFilter === 'Processing' ? 'selected' : '' ?>>Processing</option>
            <option value="Shipped" <?= $statusFilter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <?php if (empty($orders)): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 48px; text-align: center;">
          <h3 style="font-size: 18px; color: #fff; margin-bottom: 6px;">No orders found</h3>
          <p style="color: var(--text-muted); font-size: 13px;">There are no active garage orders matching this filter.</p>
        </div>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <div class="order-row-box">
            <div class="order-row-top">
              <div class="flex items-center gap-4">
                <strong style="font-size: 16px; color: #fff;">Order #<?= $o['id'] ?></strong>
                <span style="font-size: 12px; color: var(--text-muted);"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></span>
                <span class="badge badge-grade"><?= strtoupper($o['payment_method']) ?></span>
              </div>
              
              <div class="flex items-center gap-2">
                <!-- Quick Status Update Form -->
                <form method="POST" action="orders.php" class="flex items-center gap-2" style="margin: 0;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="status" class="status-select">
                    <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Processing" <?= $o['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Shipped" <?= $o['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $o['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                  <button type="submit" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px;">Save</button>
                </form>

                <!-- Delete Order Form -->
                <form method="POST" action="orders.php" onsubmit="return confirm('Permanently delete Order #<?= $o['id'] ?> from database?');" style="margin: 0;">
                  <input type="hidden" name="action" value="delete_order">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); padding: 3px 8px; font-size: 11px;" title="Delete Order">🗑️ Delete</button>
                </form>
              </div>
            </div>

            <div class="order-row-content">
              <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; font-size: 13px;">
                <div>
                  <span style="color: var(--text-muted); display: block;">Recipient &amp; Delivery Destination:</span>
                  <strong style="color: #fff; font-size: 14px;"><?= htmlspecialchars($o['customer_name']) ?></strong> (<?= htmlspecialchars($o['customer_phone']) ?>)<br>
                  <span style="color: var(--text-muted);"><?= htmlspecialchars($o['delivery_address']) ?>, <?= htmlspecialchars($o['city']) ?></span>
                </div>
                <div style="text-align: right;">
                  <span style="color: var(--text-muted); display: block;">Total Order Value:</span>
                  <strong style="color: var(--primary); font-size: 20px;"><?= formatCurrency($o['total_amount']) ?></strong>
                </div>
              </div>

              <?php if (!empty($o['items'])): ?>
                <div style="background: var(--bg-input); border-radius: var(--radius-sm); padding: 12px 16px;">
                  <span style="font-size: 11px; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Items Ordered:</span>
                  <?php foreach ($o['items'] as $it): ?>
                    <div class="flex justify-between items-center" style="font-size: 13px; padding-top: 6px;">
                      <span style="color: #fff;">• <?= htmlspecialchars($it['product_name']) ?> (×<?= $it['quantity'] ?>)</span>
                      <strong style="color: var(--accent-cyan);"><?= formatCurrency($it['subtotal']) ?></strong>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>
