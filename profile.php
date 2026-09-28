<?php
// profile.php - Customer / Garage Profile
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$user = currentUser();
$pdo = getDbConnection();

$userData = $user;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();
    if ($dbUser) $userData = $dbUser;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');

    if ($pdo) {
        $up = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ? WHERE id = ?");
        $up->execute([$name, $phone, $address, $city, $user['id']]);
        $_SESSION['user_name'] = $name;
        setFlash('success', 'Profile updated successfully.');
        header('Location: profile.php');
        exit;
    } else {
        $_SESSION['user_name'] = $name;
        setFlash('success', 'Profile updated (Demo mode).');
        header('Location: profile.php');
        exit;
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Profile | JK Tech Motors</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .profile-card {
      max-width: 650px;
      margin: 40px auto 80px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 36px;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container">
    <div class="profile-card">
      <div class="flex justify-between items-center" style="margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
        <div>
          <h1 style="font-size: 22px; font-weight: 800; color: #fff;">MY ACCOUNT</h1>
          <p style="font-size: 13px; color: var(--text-muted);">Manage your workshop address and dispatch info</p>
        </div>
        <a href="orders.php" class="btn btn-outline btn-sm">View My Orders &rarr;</a>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="profile.php">
        <div class="form-group">
          <label class="form-label">Full Name / Garage</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Email Address (Read Only)</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly style="opacity: 0.6;">
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Default Delivery / Workshop Address</label>
          <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($userData['address'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($userData['city'] ?? 'Colombo') ?>">
        </div>

        <div class="flex justify-between items-center" style="margin-top: 24px;">
          <button type="submit" class="btn btn-primary">Save Profile Changes</button>
          <a href="logout.php" class="btn btn-outline btn-sm" style="color: #ef4444;">Sign Out</a>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
