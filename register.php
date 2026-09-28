<?php
// register.php - Garage & Customer Registration
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $city = sanitize($_POST['city'] ?? 'Colombo');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = 'Full Name or Workshop Name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $res = registerUser($name, $email, $password, $phone, $city, 'customer');
        if ($res['success']) {
            $user = $res['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            setFlash('success', "✓ Account created & saved to database successfully! Welcome {$name}.");
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = $res['error'];
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
  <title>Create Account | JK Tech Motors</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .auth-container {
      max-width: 500px;
      margin: 60px auto 90px;
      background: rgba(20, 29, 48, 0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-lg);
      padding: 38px;
      box-shadow: var(--shadow-md), 0 0 30px rgba(0, 0, 0, 0.5);
      position: relative;
    }
    .auth-container::before {
      content: "";
      position: absolute;
      top: -1px; left: 30px; right: 30px; height: 2px;
      background: linear-gradient(90deg, var(--neon-cyan), var(--primary));
      box-shadow: 0 0 14px var(--neon-cyan);
    }
    .auth-header {
      text-align: center;
      margin-bottom: 26px;
    }
    .auth-header h1 {
      font-family: var(--font-display);
      font-size: 26px;
      font-weight: 900;
      color: #fff;
      margin-bottom: 6px;
      letter-spacing: 0.5px;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container">
    <div class="auth-container">
      <div class="auth-header">
        <h1>CREATE ACCOUNT</h1>
        <p style="font-size: 13px; color: var(--text-muted);">Register your garage or personal workshop profile</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <div>
            <?php foreach ($errors as $err): ?>
              <div>• <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php">
        <div class="form-group">
          <label class="form-label">Full Name / Garage Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Kasun Auto Engineering" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="garage@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number</label>
            <input type="text" name="phone" class="form-control" placeholder="+94 7X XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">City / Town</label>
          <input type="text" name="city" class="form-control" placeholder="e.g. Colombo, Kandy, Galle" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>

        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 14px;">Create Account</button>
      </form>

      <div style="text-align: center; margin-top: 26px; font-size: 13px; color: var(--text-muted);">
        Already registered? <a href="login.php" style="color: var(--neon-cyan); font-weight: 700;">Sign In</a>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
