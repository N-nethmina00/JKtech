<?php
// admin/inventory.php - Warehouse Stock Monitor & Quick Level Adjuster
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();

// Handle Quick Stock Adjust (+1 / -1 / set)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $productId = (int)$_POST['product_id'];
    $delta = (int)$_POST['delta'];

    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity + ?) WHERE id = ?");
        $stmt->execute([$delta, $productId]);
        setFlash('success', 'Stock level updated.');
    } else {
        setFlash('success', 'Stock level updated (Demo mode).');
    }
    header('Location: inventory.php');
    exit;
}

$products = fetchAllProducts();
$totalUnits = 0;
$totalValue = 0;
$lowStockCount = 0;

foreach ($products as $p) {
    $totalUnits += $p['stock_quantity'];
    $totalValue += ($p['stock_quantity'] * $p['price']);
    if ($p['stock_quantity'] <= 2) $lowStockCount++;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Management | JK Tech Motors Admin</title>
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
    .stock-counter-btn {
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      color: #fff;
      width: 28px;
      height: 28px;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-weight: 700;
      transition: background 0.2s;
    }
    .stock-counter-btn:hover {
      background: var(--accent-cyan);
      color: #000;
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
        <li><a href="orders.php">📦 Orders &amp; Dispatch</a></li>
        <li><a href="inventory.php" class="active">⚙️ Inventory &amp; Stock</a></li>
        <li><a href="customers.php">👥 Garages &amp; Clients</a></li>
      </ul>

      <div style="padding: 16px 8px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px;">
        <a href="../index.php" class="btn btn-outline btn-sm" style="width: 100%;">🌐 View Main Website</a>
        <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; color:#ef4444;">Sign Out</a>
      </div>
    </aside>

    <main class="admin-main">
      <div class="flex justify-between items-center" style="margin-bottom: 28px;">
        <div>
          <h1 style="font-size: 26px; font-weight: 900; color: #fff;">WAREHOUSE INVENTORY &amp; STOCK CONTROL</h1>
          <p style="font-size: 13px; color: var(--text-muted);">Monitor units on floor at Colombo Central Depot &amp; Yokohama inbound shipments</p>
        </div>
        <a href="products.php" class="btn btn-primary btn-sm">+ Add New Part</a>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Inventory KPI Summary -->
      <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px;">
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Units in Stock</span>
          <div style="font-size: 28px; font-weight: 900; color: var(--accent-cyan); margin-top: 6px;"><?= number_format($totalUnits) ?> Units</div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px;">
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Wholesale Valuation</span>
          <div style="font-size: 28px; font-weight: 900; color: var(--accent-green); margin-top: 6px;"><?= formatCurrency($totalValue) ?></div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px;">
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Reorder Threshold Alerts</span>
          <div style="font-size: 28px; font-weight: 900; color: var(--primary); margin-top: 6px;"><?= $lowStockCount ?> Critical</div>
        </div>
      </div>

      <!-- Stock Table with Quick Increment/Decrement -->
      <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
          <thead>
            <tr style="background: #0d131f; border-bottom: 1px solid var(--border-color); color: var(--text-dim); text-transform: uppercase; font-size: 11px;">
              <th style="padding: 14px 18px;">SKU</th>
              <th style="padding: 14px 18px;">Component Description</th>
              <th style="padding: 14px 18px;">Unit Price</th>
              <th style="padding: 14px 18px;">Current Stock</th>
              <th style="padding: 14px 18px;">Status</th>
              <th style="padding: 14px 18px;">Quick Stock Adjust</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr style="border-bottom: 1px solid var(--border-color); vertical-align: middle;">
                <td style="padding: 12px 18px;"><code><?= htmlspecialchars($p['sku']) ?></code></td>
                <td style="padding: 12px 18px;">
                  <strong style="color: #fff; display: block;"><?= htmlspecialchars($p['name']) ?></strong>
                  <span style="font-size: 11px; color: var(--text-dim);"><?= htmlspecialchars($p['model_compatibility']) ?></span>
                </td>
                <td style="padding: 12px 18px; color: #fff; font-weight: 600;">
                  <?= formatCurrency($p['price']) ?>
                </td>
                <td style="padding: 12px 18px;">
                  <strong style="font-size: 16px; color: <?= $p['stock_quantity'] <= 2 ? '#f87171' : '#34d399' ?>;">
                    <?= $p['stock_quantity'] ?>
                  </strong>
                </td>
                <td style="padding: 12px 18px;">
                  <?php if ($p['stock_quantity'] <= 2): ?>
                    <span class="badge badge-orange">Reorder Low</span>
                  <?php else: ?>
                    <span class="badge badge-stock">Optimal</span>
                  <?php endif; ?>
                </td>
                <td style="padding: 12px 18px;">
                  <div class="flex items-center gap-2">
                    <form method="POST" action="inventory.php" style="display:inline;">
                      <input type="hidden" name="action" value="adjust_stock">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="delta" value="-1">
                      <button type="submit" class="stock-counter-btn" title="Decrease stock">-</button>
                    </form>

                    <form method="POST" action="inventory.php" style="display:inline;">
                      <input type="hidden" name="action" value="adjust_stock">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="delta" value="1">
                      <button type="submit" class="stock-counter-btn" title="Add 1 imported unit">+</button>
                    </form>

                    <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px; margin-left: 8px;">Edit</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

</body>
</html>
