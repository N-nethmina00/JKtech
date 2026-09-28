<?php
// admin/index.php - Super Modern Operations Dashboard
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();
$dbStatus = getDbStatus();

$totalRevenue = 0;
$totalOrders = 0;
$lowStockCount = 0;
$totalCustomers = 0;
$recentOrders = [];
$lowStockItems = [];

if ($pdo) {
    $revStmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'");
    $totalRevenue = (float)$revStmt->fetchColumn();

    $ordStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$ordStmt->fetchColumn();

    $lowStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 2");
    $lowStockCount = (int)$lowStmt->fetchColumn();

    $custStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $totalCustomers = (int)$custStmt->fetchColumn();

    $recentStmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
    $recentOrders = $recentStmt->fetchAll();

    $lowListStmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE stock_quantity <= 2 LIMIT 5");
    $lowStockItems = $lowListStmt->fetchAll();
} else {
    $totalRevenue = 4250000.00;
    $totalOrders = 1047;
    $lowStockCount = 23;
    $totalCustomers = 450;
    $recentOrders = [
        ['id' => 1047, 'customer_name' => 'Sampath Auto', 'customer_phone' => '+94 77 111 2233', 'total_amount' => 185000, 'status' => 'Delivered', 'created_at' => '2026-09-10 10:14:00'],
        ['id' => 1046, 'customer_name' => 'Niroshan Ranasinghe', 'customer_phone' => '+94 71 222 3344', 'total_amount' => 480000, 'status' => 'Processing', 'created_at' => '2026-09-09 16:20:00'],
        ['id' => 1045, 'customer_name' => 'Dammika Motors', 'customer_phone' => '+94 76 333 4455', 'total_amount' => 95000, 'status' => 'Pending', 'created_at' => '2026-09-09 11:05:00'],
        ['id' => 1044, 'customer_name' => 'Express Garage Negombo', 'customer_phone' => '+94 77 444 5566', 'total_amount' => 240000, 'status' => 'Shipped', 'created_at' => '2026-09-08 09:45:00']
    ];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | JK Tech Motors Operations</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .admin-container {
      display: grid;
      grid-template-columns: 250px 1fr;
      min-height: 100vh;
    }
    .admin-sidebar {
      background: #060911;
      border-right: 1px solid var(--border-subtle);
      padding: 26px 18px;
      display: flex;
      flex-direction: column;
    }
    .admin-menu {
      list-style: none;
      margin-top: 32px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex: 1;
    }
    .admin-menu a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 16px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 700;
      color: var(--text-muted);
      font-family: var(--font-display);
      transition: all 0.25s ease;
    }
    .admin-menu a:hover, .admin-menu a.active {
      background: var(--bg-card);
      color: #fff;
      border-left: 3px solid var(--neon-cyan);
      box-shadow: 0 4px 14px rgba(0,0,0,0.3);
    }
    .admin-main {
      padding: 36px 44px;
      background: var(--bg-main);
      overflow-y: auto;
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 22px;
      margin-bottom: 38px;
    }
    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      padding: 24px;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease;
    }
    .kpi-card:hover {
      transform: translateY(-3px);
    }
    .kpi-card h4 {
      font-family: var(--font-display);
      font-size: 12px;
      font-weight: 800;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 10px;
    }
    .kpi-card .val {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 900;
      color: #fff;
    }
    .kpi-card.cyan .val { color: var(--neon-cyan); }
    .kpi-card.orange .val { color: var(--primary); }
    .kpi-card.green .val { color: var(--neon-emerald); }
    .kpi-card.amber .val { color: var(--neon-amber); }

    .data-table-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 32px;
      box-shadow: var(--shadow-sm);
    }
    .data-table-header {
      background: #090e18;
      padding: 18px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-subtle);
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 13px;
    }
    .data-table th {
      padding: 14px 20px;
      color: var(--text-dim);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border-bottom: 1px solid var(--border-subtle);
      font-family: var(--font-display);
      font-weight: 800;
    }
    .data-table td {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-subtle);
      vertical-align: middle;
    }
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: var(--radius-xs);
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      font-family: var(--font-display);
      letter-spacing: 0.5px;
    }
    .status-Delivered { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
    .status-Processing { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.35); }
    .status-Pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
    .status-Shipped { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.35); }
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
        <div style="font-size: 10.5px; color: var(--neon-cyan); font-weight: 800; letter-spacing: 1.2px; font-family:var(--font-display); padding-left: 2px;">MANAGEMENT CONSOLE</div>
      </div>

      <ul class="admin-menu">
        <li><a href="index.php" class="active">📊 Dashboard</a></li>
        <li><a href="products.php">🔧 Products &amp; Spares</a></li>
        <li><a href="orders.php">📦 Orders &amp; Dispatch</a></li>
        <li><a href="inventory.php">⚙️ Inventory &amp; Stock</a></li>
        <li><a href="customers.php">👥 Garages &amp; Clients</a></li>
      </ul>

      <div style="padding: 18px 8px; border-top: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 10px;">
        <a href="../index.php" class="btn btn-outline btn-sm" style="width: 100%;">🌐 View Main Store</a>
        <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; color:#ef4444;">Sign Out</a>
      </div>
    </aside>

    <main class="admin-main">
      <div class="flex justify-between items-center" style="margin-bottom: 32px;">
        <div>
          <h1 style="font-family: var(--font-display); font-size: 28px; font-weight: 900; color: #fff;">OPERATIONS DASHBOARD</h1>
          <p style="font-size: 14px; color: var(--text-muted);">Real-time telemetry for Japanese arrivals, stock levels &amp; garage fulfillments</p>
        </div>
        <div class="flex items-center gap-3">
          <div style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-family:var(--font-mono); font-weight:700; color:var(--neon-cyan); background:rgba(6,182,212,0.1); border:1px solid rgba(6,182,212,0.3); padding:6px 12px; border-radius:var(--radius-pill);">
            <span><?= htmlspecialchars($dbStatus['badge']) ?></span>
          </div>
          <span class="badge-pill-live">
            <span class="pulse-dot"></span>
            <span>Yokohama Telemetry Live</span>
          </span>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- KPI Cards Row -->
      <div class="kpi-grid">
        <div class="kpi-card green">
          <h4>Total Revenue</h4>
          <div class="val"><?= formatCurrency($totalRevenue) ?></div>
        </div>
        <div class="kpi-card cyan">
          <h4>Total Orders</h4>
          <div class="val"><?= number_format($totalOrders) ?></div>
        </div>
        <div class="kpi-card orange">
          <h4>Critical Low Stock</h4>
          <div class="val"><?= $lowStockCount ?> Parts</div>
        </div>
        <div class="kpi-card amber">
          <h4>Registered Garages</h4>
          <div class="val"><?= number_format($totalCustomers) ?></div>
        </div>
      </div>

      <!-- Recent Orders Table -->
      <div class="data-table-card">
        <div class="data-table-header">
          <h3 style="font-family: var(--font-display); font-size: 16px; font-weight: 800; color: #fff;">RECENT GARAGE ORDERS</h3>
          <a href="orders.php" class="btn btn-outline btn-sm">View All Orders &rarr;</a>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer / Garage</th>
              <th>Phone</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Order Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ro): ?>
              <tr>
                <td><strong style="font-family: var(--font-mono);">#<?= $ro['id'] ?></strong></td>
                <td style="color: #fff; font-weight: 700;"><?= htmlspecialchars($ro['customer_name']) ?></td>
                <td style="font-family: var(--font-mono);"><?= htmlspecialchars($ro['customer_phone']) ?></td>
                <td style="color: #fff; font-weight: 800; font-family: var(--font-display);"><?= formatCurrency($ro['total_amount']) ?></td>
                <td><span class="status-badge status-<?= $ro['status'] ?>"><?= $ro['status'] ?></span></td>
                <td><?= date('M d, H:i', strtotime($ro['created_at'])) ?></td>
                <td>
                  <a href="orders.php?highlight=<?= $ro['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 10px;">Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Quick Inventory Alert Table -->
      <?php if (!empty($lowStockItems)): ?>
        <div class="data-table-card">
          <div class="data-table-header">
            <h3 style="font-family: var(--font-display); font-size: 16px; font-weight: 800; color: #f87171;">⚠️ URGENT YARD RE-ORDER ALERTS</h3>
            <a href="inventory.php" class="btn btn-outline btn-sm">Stock Manager &rarr;</a>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>SKU</th>
                <th>Part Description</th>
                <th>Category</th>
                <th>Current Units</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lowStockItems as $item): ?>
                <tr>
                  <td><code style="color:var(--neon-cyan); font-family:var(--font-mono);"><?= htmlspecialchars($item['sku']) ?></code></td>
                  <td style="color: #fff; font-weight: 700;"><?= htmlspecialchars($item['name']) ?></td>
                  <td><?= htmlspecialchars($item['category_name'] ?? 'Component') ?></td>
                  <td>
                    <span style="color: #f87171; font-weight: 800;"><?= $item['stock_quantity'] ?> unit(s) remaining</span>
                  </td>
                  <td>
                    <a href="products.php?edit=<?= $item['id'] ?>" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 4px 10px;">Restock</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>
