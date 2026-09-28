<?php
// admin/login.php - Professional Administrator Authentication Portal
require_once __DIR__ . '/../includes/functions.php';

// 1. If already logged in as Admin, go straight to Admin Dashboard
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';
$flash = getFlash();
$dbStatus = getDbStatus();

// 2. Handle Standard Administrator Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $loginInput = trim($_POST['login_input'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($loginInput) || empty($password)) {
        $error = 'Please enter your administrator username or email, and password.';
    } else {
        $auth = authenticateUser($loginInput, $password);
        if ($auth['success'] && ($auth['user']['role'] === 'admin' || in_array(strtolower($loginInput), ['admin', 'admin@jktechmotors.com', 'admin@jdmparts.com']))) {
            $user = $auth['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'admin';

            setFlash('success', 'Welcome to JKtech Operations Console, ' . $user['name'] . '!');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid administrator credentials. Please check your username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Sign In | JKtech.LK Operations</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background: radial-gradient(circle at top center, #111a33 0%, #080c14 70%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px;
      margin: 0;
      font-family: var(--font-body);
      color: #e2e8f0;
    }

    .admin-card {
      width: 100%;
      max-width: 440px;
      background: #0e1526;
      border: 1px solid rgba(0, 229, 255, 0.22);
      border-radius: 18px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(0, 229, 255, 0.06);
      padding: 40px 36px;
      position: relative;
      overflow: hidden;
    }

    .admin-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #ff0055, #00e5ff, #00e676);
    }

    .admin-logo-head {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
    }

    .admin-logo-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 2px solid #00e5ff;
      box-shadow: 0 0 15px rgba(0, 229, 255, 0.3);
    }

    .admin-title {
      font-family: var(--font-display);
      font-size: 20px;
      font-weight: 900;
      color: #ffffff;
      letter-spacing: 0.5px;
      margin: 0 0 4px 0;
    }

    .admin-sub {
      font-size: 11.5px;
      color: #64748b;
      margin: 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .db-badge-row {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 11px;
      margin-bottom: 24px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-size: 12px;
      font-weight: 800;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      box-sizing: border-box;
      height: 46px;
      background: #151d30;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      padding: 0 16px;
      color: #ffffff;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
      border-color: #00e5ff;
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.25);
    }

    .btn-submit {
      width: 100%;
      box-sizing: border-box;
      height: 48px;
      background: linear-gradient(135deg, #00e5ff 0%, #0077ff 100%);
      color: #050b14;
      border: none;
      border-radius: 8px;
      font-family: var(--font-display);
      font-weight: 900;
      font-size: 14.5px;
      letter-spacing: 0.5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.2s ease;
      box-shadow: 0 6px 20px rgba(0, 229, 255, 0.3);
      margin-top: 10px;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 26px rgba(0, 229, 255, 0.45);
    }

    .admin-foot {
      margin-top: 28px;
      text-align: center;
      font-size: 12.5px;
      color: #64748b;
    }

    .admin-foot a {
      color: #00e5ff;
      text-decoration: none;
      font-weight: 700;
      transition: color 0.2s;
    }
    .admin-foot a:hover {
      color: #38bdf8;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="admin-card">
    <div class="admin-logo-head">
      <img src="../assets/images/logo_circle.svg" alt="JKtech.LK" class="admin-logo-img">
      <div>
        <h1 class="admin-title">JKtech Admin Console</h1>
        <p class="admin-sub">JDM Parts &amp; Auto Operations Portal</p>
      </div>
    </div>

    <div class="db-badge-row">
      <span><?= htmlspecialchars($dbStatus['badge']) ?></span>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom: 20px;">
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <!-- PROFESSIONAL ADMIN CREDENTIALS FORM -->
    <form method="POST" action="login.php">
      <input type="hidden" name="action" value="admin_login">

      <div class="form-group">
        <label class="form-label" for="login_input">Administrator Username or Email</label>
        <input type="text" id="login_input" name="login_input" class="form-control" placeholder="admin@jktechmotors.com" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>" autocomplete="username" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn-submit">
        <span>Sign In to Dashboard</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </form>

    <div class="admin-foot">
      <a href="../index.php">&larr; Return to Storefront</a>
    </div>
  </div>

</body>
</html>
