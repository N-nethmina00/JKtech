<?php
// admin/customers.php - Customer & Garage Directory with Live Database Sync
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();
$dbStatus = getDbStatus();

// Handle Form Actions (Add user, Delete user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Admin Add New Account
    if ($action === 'create_customer') {
        $cName = sanitize($_POST['name'] ?? '');
        $cEmail = sanitize($_POST['email'] ?? '');
        $cPhone = sanitize($_POST['phone'] ?? '');
        $cCity = sanitize($_POST['city'] ?? 'Colombo');
        $cPass = $_POST['password'] ?? '123456';
        $cRole = sanitize($_POST['role'] ?? 'customer');

        if (empty($cName) || empty($cEmail)) {
            setFlash('error', 'Name and Email are required.');
        } else {
            $reg = registerUser($cName, $cEmail, $cPass, $cPhone, $cCity, $cRole);
            if ($reg['success']) {
                setFlash('success', "✓ Account for \"{$cName}\" ({$cEmail}) created and saved to database successfully!");
            } else {
                setFlash('error', $reg['error']);
            }
        }
        header('Location: customers.php');
        exit;
    }

    // Admin Delete Account
    if ($action === 'delete_customer' && $pdo) {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId === 1 || $delId === (int)($_SESSION['user_id'] ?? 0)) {
            setFlash('error', 'Cannot delete the active administrator account.');
        } else {
            try {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delId]);
                setFlash('success', '✓ User account deleted from database successfully.');
            } catch (Exception $e) {
                setFlash('error', 'Error deleting account: ' . $e->getMessage());
            }
        }
        header('Location: customers.php');
        exit;
    }
}

// Fetch all registered users from active database
$customers = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS total_orders 
                             FROM users u 
                             ORDER BY u.id DESC");
        $customers = $stmt->fetchAll();
    } catch (Exception $e) {
        try {
            $stmt = $pdo->query("SELECT *, 0 AS total_orders FROM users ORDER BY id DESC");
            $customers = $stmt->fetchAll();
        } catch (Exception $e2) {
            $customers = [];
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
  <title>Garages &amp; Clients Directory | JK Tech Motors Admin</title>
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
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .admin-menu a:hover, .admin-menu a.active {
      background: var(--bg-card);
      color: #fff;
      border-left: 3px solid #00e5ff;
    }
    .admin-main {
      padding: 32px 40px;
      background: var(--bg-main);
      overflow-y: auto;
    }
    .add-customer-box {
      background: #0c1220;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 22px;
      margin-bottom: 28px;
    }
    .form-grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 16px;
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
        <li><a href="inventory.php">⚙️ Inventory &amp; Stock</a></li>
        <li><a href="customers.php" class="active">👥 Garages &amp; Clients</a></li>
      </ul>

      <div style="padding: 16px 8px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px;">
        <a href="../index.php" class="btn btn-outline btn-sm" style="width: 100%;">🌐 View Main Website</a>
        <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; color:#ef4444;">Sign Out</a>
      </div>
    </aside>

    <main class="admin-main">
      <div class="flex justify-between items-center" style="margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
          <h1 style="font-size: 26px; font-weight: 900; color: #fff; margin:0 0 4px 0;">GARAGES &amp; CLIENT DIRECTORY</h1>
          <p style="font-size: 13px; color: var(--text-muted); margin:0;">Real-time database sync of all registered workshops, contact phone numbers &amp; buyers</p>
        </div>
        
        <div class="flex items-center gap-3">
          <span class="badge badge-grade" style="font-size:12px;"><?= count($customers) ?> Accounts in Database</span>
          <div style="font-size: 11px; font-family:var(--font-mono); font-weight:700; color:#00e5ff; background:rgba(0,229,255,0.08); border:1px solid rgba(0,229,255,0.3); padding:6px 12px; border-radius:var(--radius-pill);">
            <?= htmlspecialchars($dbStatus['badge']) ?>
          </div>
          <button type="button" onclick="toggleAddCustomer()" class="btn btn-primary btn-sm">+ Add Account</button>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Add New Customer Collapsible Form -->
      <div id="addCustomerBox" class="add-customer-box" style="display: none;">
        <h3 style="font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 14px;">Directly Add Customer / Workshop to Database</h3>
        <form method="POST" action="customers.php">
          <input type="hidden" name="action" value="create_customer">

          <div class="form-grid-3">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Full Name / Garage Name *</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Bandara Motors" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Email Address *</label>
              <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Mobile Phone Number</label>
              <input type="text" name="phone" class="form-control" placeholder="+94 77 123 4567">
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group" style="margin:0;">
              <label class="form-label">City / Region</label>
              <input type="text" name="city" class="form-control" placeholder="Colombo / Kandy" value="Colombo">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Temporary Password</label>
              <input type="password" name="password" class="form-control" value="123456" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Role</label>
              <select name="role" class="form-control">
                <option value="customer" selected>Customer / Garage Client</option>
                <option value="admin">Administrator</option>
              </select>
            </div>
          </div>

          <div style="margin-top: 16px; display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary btn-sm">Save to Database</button>
            <button type="button" onclick="toggleAddCustomer()" class="btn btn-secondary btn-sm">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Live Users Table -->
      <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
          <thead>
            <tr style="background: #0d131f; border-bottom: 1px solid var(--border-color); color: var(--text-dim); text-transform: uppercase; font-size: 11px;">
              <th style="padding: 14px 18px;">ID</th>
              <th style="padding: 14px 18px;">Customer / Garage Name</th>
              <th style="padding: 14px 18px;">Email Address</th>
              <th style="padding: 14px 18px;">Phone</th>
              <th style="padding: 14px 18px;">City / Region</th>
              <th style="padding: 14px 18px;">Account Role</th>
              <th style="padding: 14px 18px;">Orders Placed</th>
              <th style="padding: 14px 18px;">Registered Date</th>
              <th style="padding: 14px 18px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customers)): ?>
              <tr>
                <td colspan="9" style="padding: 30px; text-align:center; color: var(--text-muted);">
                  No registered accounts in database yet. New signups from <code>register.php</code> will appear here automatically!
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($customers as $c): ?>
                <tr style="border-bottom: 1px solid var(--border-color); vertical-align: middle;">
                  <td style="padding: 14px 18px;">#<?= $c['id'] ?></td>
                  <td style="padding: 14px 18px;">
                    <strong style="color: #fff; display: block;"><?= htmlspecialchars($c['name']) ?></strong>
                    <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($c['address'] ?? '') ?></span>
                  </td>
                  <td style="padding: 14px 18px; color: #38bdf8;"><?= htmlspecialchars($c['email']) ?></td>
                  <td style="padding: 14px 18px; color: #fff; font-family: var(--font-mono);"><?= htmlspecialchars($c['phone'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 18px;"><?= htmlspecialchars($c['city'] ?? 'Colombo') ?></td>
                  <td style="padding: 14px 18px;">
                    <?php if ($c['role'] === 'admin'): ?>
                      <span class="badge badge-orange">Administrator</span>
                    <?php else: ?>
                      <span class="badge badge-stock">Garage / Client</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 14px 18px;">
                    <strong style="color: #00e5ff;"><?= (int)($c['total_orders'] ?? 0) ?> orders</strong>
                  </td>
                  <td style="padding: 14px 18px; color: var(--text-dim);">
                    <?= !empty($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : 'Recent' ?>
                  </td>
                  <td style="padding: 14px 18px; text-align: right;">
                    <?php if ($c['id'] != 1 && $c['email'] !== 'admin@jktechmotors.com'): ?>
                      <form method="POST" action="customers.php" onsubmit="return confirm('Are you sure you want to remove account for <?= htmlspecialchars($c['name']) ?>?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444; padding: 4px 10px; font-size: 11px;">Delete</button>
                      </form>
                    <?php else: ?>
                      <span style="font-size: 11px; color:#64748b;">Primary Admin</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    function toggleAddCustomer() {
      const box = document.getElementById('addCustomerBox');
      box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
      if (box.style.display === 'block') {
        box.scrollIntoView({ behavior: 'smooth' });
      }
    }
  </script>
</body>
</html>
