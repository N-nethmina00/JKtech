<?php
// login.php - Professional Email & Password Authentication + Account Registration Portal
require_once __DIR__ . '/includes/functions.php';

// If already admin, redirect to admin console
if (isAdmin()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$errors = [];
$activeTab = (isset($_GET['mode']) && $_GET['mode'] === 'register') ? 'register' : 'login';
$prefillEmail = isset($_GET['email']) ? sanitize($_GET['email']) : '';

// -------------------------------------------------------------
// 1. Handle Sign In (Email + Password)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password_login') {
    $activeTab = 'login';
    $loginInput = sanitize($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    $prefillEmail = $loginInput;

    if (empty($loginInput) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $auth = authenticateUser($loginInput, $password);
        if ($auth['success']) {
            $user = $auth['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar_url'] ?? '';

            if (function_exists('sendLoginNotificationEmail') && !empty($user['email'])) {
                $emailRes = sendLoginNotificationEmail($user['email'], $user['name'], 'Email & Password');
                if ($emailRes) {
                    $_SESSION['login_email_alert'] = $emailRes;
                }
            }

            setFlash('success', "Welcome back, {$user['name']}!");
            header($user['role'] === 'admin' ? 'Location: admin/index.php' : 'Location: index.php');
            exit;
        } else {
            $error = $auth['error'];
        }
    }
}

// -------------------------------------------------------------
// 1.5 Handle Google One-Click / Social Sign In
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'google_login') {
    $activeTab = 'login';
    $gEmail = sanitize($_POST['google_email'] ?? '');
    $gName = sanitize($_POST['google_name'] ?? '');
    $gAvatar = trim($_POST['google_avatar'] ?? '');

    if (empty($gEmail) || !filter_var($gEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid Google account email address.';
    } else {
        $user = findOrCreateSocialUser('google', $gEmail, $gName, $gAvatar);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar_url'] ?? '';

            if (function_exists('sendLoginNotificationEmail') && !empty($user['email'])) {
                $emailRes = sendLoginNotificationEmail($user['email'], $user['name'], 'Google One-Click');
                if ($emailRes) {
                    $_SESSION['login_email_alert'] = $emailRes;
                }
            }

            setFlash('success', "Signed in successfully with Google as {$user['name']}!");
            header($user['role'] === 'admin' ? 'Location: admin/index.php' : 'Location: index.php');
            exit;
        } else {
            $error = 'Failed to authenticate with Google. Please try again.';
        }
    }
}

// -------------------------------------------------------------
// 2. Handle Account Registration (Create Account with Email)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_account') {
    $activeTab = 'register';
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $city = sanitize($_POST['city'] ?? 'Colombo');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = 'Please enter your full name.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($phone)) {
        $errors[] = 'Please enter your contact phone number.';
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match. Please re-check.';
    }

    if (empty($errors)) {
        $res = registerUser($name, $email, $password, $phone, $city, 'customer');
        if ($res['success']) {
            setFlash('success', "Account created successfully! Please sign in with your email and password.");
            header('Location: login.php?email=' . urlencode($email));
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
  <title>Sign In or Create Account | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .auth-page-section {
      padding: 48px 16px 80px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 60vh;
    }

    .auth-card {
      width: 100%;
      max-width: 480px;
      margin: 0 auto;
      background: #0c1322;
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 18px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.65);
      position: relative;
      overflow: hidden;
    }

    /* Top Accent Line */
    .auth-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #00e5ff 0%, #ff4d2d 100%);
    }

    .auth-card-header {
      padding: 32px 32px 20px;
      text-align: center;
    }

    .auth-brand-emblem {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      margin: 0 auto 14px;
      box-shadow: 0 0 20px rgba(0, 229, 255, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .auth-heading {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 900;
      color: #ffffff;
      margin: 0 0 6px;
      letter-spacing: -0.3px;
    }

    .auth-subheading {
      font-size: 13.5px;
      color: #94a3b8;
      margin: 0;
      line-height: 1.5;
    }

    /* Mode Switcher Tabs (Sign In / Create Account) */
    .auth-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #070b14;
      padding: 5px;
      border-radius: 12px;
      margin: 0 32px 24px;
      border: 1px solid rgba(255, 255, 255, 0.07);
    }

    .auth-tab-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-family: var(--font-display);
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.4px;
      padding: 10px 12px;
      border-radius: 9px;
      cursor: pointer;
      transition: all 0.25s ease;
    }

    .auth-tab-btn:hover {
      color: #ffffff;
    }

    .auth-tab-btn.active {
      background: #152036;
      color: #00e5ff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(0, 229, 255, 0.25);
    }

    .auth-card-body {
      padding: 0 32px 34px;
    }

    /* Form Fields with Left Icon */
    .auth-field {
      margin-bottom: 18px;
    }

    .auth-label {
      display: block;
      font-size: 12.5px;
      font-weight: 700;
      color: #cbd5e1;
      margin-bottom: 7px;
      letter-spacing: 0.2px;
    }

    .input-icon-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-left-icon {
      position: absolute;
      left: 14px;
      color: #64748b;
      pointer-events: none;
      display: flex;
      align-items: center;
      transition: color 0.2s ease;
    }

    .auth-input {
      width: 100%;
      height: 46px;
      background: #080d18;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0 42px 0 42px;
      color: #ffffff;
      font-size: 14px;
      font-family: var(--font-body);
      transition: all 0.2s ease;
      outline: none;
    }

    .auth-input:focus {
      border-color: #00e5ff;
      background: #0a101f;
      box-shadow: 0 0 0 3px rgba(0, 229, 255, 0.12);
    }

    .input-icon-wrap:focus-within .input-left-icon {
      color: #00e5ff;
    }

    .pwd-toggle-btn {
      position: absolute;
      right: 12px;
      background: transparent;
      border: none;
      color: #64748b;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s ease;
    }

    .pwd-toggle-btn:hover {
      color: #cbd5e1;
    }

    .auth-row-2col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .auth-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: -4px 0 20px;
      font-size: 12.5px;
    }

    .remember-label {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      color: #94a3b8;
      cursor: pointer;
      user-select: none;
    }

    .remember-label input {
      accent-color: #00e5ff;
      width: 15px;
      height: 15px;
      cursor: pointer;
    }

    /* Primary Submit Button */
    .btn-auth-submit {
      width: 100%;
      height: 48px;
      background: linear-gradient(90deg, #ff4d2d 0%, #f97316 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-family: var(--font-display);
      font-size: 14px;
      font-weight: 800;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(255, 77, 45, 0.35);
      transition: all 0.25s ease;
    }

    .btn-auth-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 26px rgba(255, 77, 45, 0.55);
    }

    .auth-switch-footer {
      text-align: center;
      margin-top: 22px;
      padding-top: 18px;
      border-top: 1px solid rgba(255, 255, 255, 0.07);
      font-size: 13px;
      color: #94a3b8;
    }

    .auth-switch-link {
      color: #00e5ff;
      font-weight: 800;
      cursor: pointer;
      background: none;
      border: none;
      font-size: 13px;
      font-family: inherit;
      padding: 0;
      margin-left: 4px;
      text-decoration: none;
    }

    .auth-switch-link:hover {
      text-decoration: underline;
    }

    /* Active Session Banner */
    .active-session-box {
      background: rgba(16, 185, 129, 0.08);
      border: 1px solid rgba(16, 185, 129, 0.3);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      font-size: 13px;
    }

    @media (max-width: 540px) {
      .auth-page-section {
        padding: 24px 12px 50px;
      }
      .auth-card-header {
        padding: 24px 20px 16px;
      }
      .auth-tabs {
        margin: 0 20px 20px;
      }
      .auth-card-body {
        padding: 0 20px 26px;
      }
      .auth-row-2col {
        grid-template-columns: 1fr;
        gap: 0;
      }
      .auth-heading {
        font-size: 21px;
      }
    }
    /* Google Sign-In Button */
    .btn-google-login {
      width: 100%;
      height: 46px;
      background: #ffffff;
      color: #1f2937;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      font-family: var(--font-body);
      font-size: 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    .btn-google-login:hover {
      background: #f8fafc;
      box-shadow: 0 4px 14px rgba(255, 255, 255, 0.25);
      transform: translateY(-1px);
    }

    .auth-divider-line {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 18px 0;
      color: #64748b;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .auth-divider-line::before,
    .auth-divider-line::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .auth-divider-line span {
      padding: 0 12px;
    }

    /* Google Account Chooser Modal */
    .google-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(4, 7, 14, 0.85);
      backdrop-filter: blur(6px);
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .google-modal.active {
      display: flex;
    }
    .google-dialog {
      width: 100%;
      max-width: 440px;
      background: #0c1322;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85);
    }
    .google-dialog-header {
      padding: 24px 24px 16px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      position: relative;
    }
    .google-dialog-body {
      padding: 20px 24px 24px;
    }
    .google-account-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: #080d1a;
      cursor: pointer;
      margin-bottom: 10px;
      transition: all 0.2s ease;
      text-align: left;
      width: 100%;
      color: #fff;
    }
    .google-account-item:hover {
      background: #141f36;
      border-color: #00e5ff;
    }
    .google-acc-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #2563eb;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 15px;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container auth-page-section">
    <div class="auth-card">
      <!-- Top Brand Header -->
      <div class="auth-card-header">
        <img src="assets/images/logo_circle.svg" alt="JKtech.LK" class="auth-brand-emblem">
        <h1 class="auth-heading" id="authMainHeading">
          <?= $activeTab === 'register' ? 'Create Your Account' : 'Sign In to Your Account' ?>
        </h1>
        <p class="auth-subheading" id="authSubHeading">
          <?= $activeTab === 'register'
              ? 'Register with your email to track orders and speed up checkout'
              : 'Enter your registered email and password to continue' ?>
        </p>
      </div>

      <!-- Mode Switcher Tabs -->
      <div class="auth-tabs">
        <button type="button" id="tabBtnLogin" class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchAuthTab('login')">
          Sign In
        </button>
        <button type="button" id="tabBtnRegister" class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchAuthTab('register')">
          Create Account
        </button>
      </div>

      <div class="auth-card-body">
        <!-- Flash Notification -->
        <?php if ($flash): ?>
          <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 18px;">
            <span><?= htmlspecialchars($flash['message']) ?></span>
          </div>
        <?php endif; ?>

        <!-- Login Error -->
        <?php if ($error): ?>
          <div class="alert alert-error" style="margin-bottom: 18px;">
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Registration Errors -->
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error" style="margin-bottom: 18px;">
            <div>
              <?php foreach ($errors as $err): ?>
                <div>&bull; <?= htmlspecialchars($err) ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- If Already Logged In -->
        <?php if (isLoggedIn()): ?>
          <div class="active-session-box">
            <div>
              <div style="color: #e2e8f0; font-weight: 700;"><?= htmlspecialchars(currentUser()['name']) ?></div>
              <div style="color: #94a3b8; font-size: 11.5px;"><?= htmlspecialchars(currentUser()['email']) ?></div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
              <a href="profile.php" style="color: #00e5ff; font-weight: 700; font-size: 12.5px;">My Account</a>
              <span style="color: #475569;">|</span>
              <a href="logout.php" style="color: #ef4444; font-weight: 700; font-size: 12.5px;">Sign Out</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- PANEL 1: SIGN IN WITH EMAIL & PASSWORD                         -->
        <!-- ============================================================== -->
        <div id="panelLogin" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
          <!-- One-Click Google Sign-In Option -->
          <div style="margin-bottom: 20px;">
            <button type="button" class="btn-google-login" onclick="openGoogleAccountChooser()">
              <svg width="20" height="20" viewBox="0 0 48 48" style="flex-shrink:0;">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
              </svg>
              <span>Continue with Google</span>
            </button>
            <div class="auth-divider-line">
              <span>or sign in with email</span>
            </div>
          </div>

          <form method="POST" action="login.php">
            <input type="hidden" name="action" value="password_login">

            <div class="auth-field">
              <label class="auth-label" for="login_input">Email Address</label>
              <div class="input-icon-wrap">
                <span class="input-left-icon">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </span>
                <input
                  type="text"
                  name="login_input"
                  id="login_input"
                  class="auth-input"
                  placeholder="name@example.com"
                  value="<?= htmlspecialchars($prefillEmail) ?>"
                  autocomplete="username"
                  required
                >
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="login_password">Password</label>
              <div class="input-icon-wrap">
                <span class="input-left-icon">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                </span>
                <input
                  type="password"
                  name="password"
                  id="login_password"
                  class="auth-input"
                  placeholder="Enter your password"
                  autocomplete="current-password"
                  required
                >
                <button type="button" class="pwd-toggle-btn" onclick="togglePassword('login_password', this)" title="Show / Hide Password">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>

            <div class="auth-meta-row">
              <label class="remember-label">
                <input type="checkbox" name="remember" checked>
                <span>Keep me signed in</span>
              </label>
              <button type="button" class="auth-switch-link" onclick="switchAuthTab('register')">Create new account?</button>
            </div>

            <button type="submit" class="btn-auth-submit">
              <span>Sign In</span>
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>
          </form>

          <div class="auth-switch-footer">
            Don't have an account yet?
            <button type="button" class="auth-switch-link" onclick="switchAuthTab('register')">Register with Email</button>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- PANEL 2: CREATE NEW ACCOUNT (REGISTER WITH EMAIL)              -->
        <!-- ============================================================== -->
        <div id="panelRegister" style="display: <?= $activeTab === 'register' ? 'block' : 'none' ?>;">
          <form method="POST" action="login.php">
            <input type="hidden" name="action" value="register_account">

            <div class="auth-field">
              <label class="auth-label" for="reg_name">Full Name</label>
              <div class="input-icon-wrap">
                <span class="input-left-icon">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </span>
                <input
                  type="text"
                  name="name"
                  id="reg_name"
                  class="auth-input"
                  placeholder="e.g. Kasun Perera"
                  value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                  required
                >
              </div>
            </div>

            <div class="auth-field">
              <label class="auth-label" for="reg_email">Email Address</label>
              <div class="input-icon-wrap">
                <span class="input-left-icon">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </span>
                <input
                  type="email"
                  name="email"
                  id="reg_email"
                  class="auth-input"
                  placeholder="name@example.com"
                  value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                  autocomplete="email"
                  required
                >
              </div>
            </div>

            <div class="auth-row-2col">
              <div class="auth-field">
                <label class="auth-label" for="reg_phone">Mobile Number</label>
                <div class="input-icon-wrap">
                  <span class="input-left-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                  </span>
                  <input
                    type="text"
                    name="phone"
                    id="reg_phone"
                    class="auth-input"
                    style="padding-right: 14px;"
                    placeholder="077 123 4567"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                    required
                  >
                </div>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="reg_city">City / Town</label>
                <div class="input-icon-wrap">
                  <span class="input-left-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                  </span>
                  <input
                    type="text"
                    name="city"
                    id="reg_city"
                    class="auth-input"
                    style="padding-right: 14px;"
                    placeholder="e.g. Colombo"
                    value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
                  >
                </div>
              </div>
            </div>

            <div class="auth-row-2col">
              <div class="auth-field">
                <label class="auth-label" for="reg_password">Password</label>
                <div class="input-icon-wrap">
                  <span class="input-left-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                  </span>
                  <input
                    type="password"
                    name="password"
                    id="reg_password"
                    class="auth-input"
                    placeholder="Min. 6 chars"
                    autocomplete="new-password"
                    required
                  >
                  <button type="button" class="pwd-toggle-btn" onclick="togglePassword('reg_password', this)" title="Show / Hide Password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>
              </div>

              <div class="auth-field">
                <label class="auth-label" for="reg_confirm">Confirm Password</label>
                <div class="input-icon-wrap">
                  <span class="input-left-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                  </span>
                  <input
                    type="password"
                    name="confirm_password"
                    id="reg_confirm"
                    class="auth-input"
                    placeholder="Repeat password"
                    autocomplete="new-password"
                    required
                  >
                  <button type="button" class="pwd-toggle-btn" onclick="togglePassword('reg_confirm', this)" title="Show / Hide Password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" class="btn-auth-submit" style="margin-top: 6px;">
              <span>Create Account</span>
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
            </button>
          </form>

          <div class="auth-switch-footer">
            Already have an account?
            <button type="button" class="auth-switch-link" onclick="switchAuthTab('login')">Sign In Here</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Hidden Form for Google Login Dispatch -->
  <form id="googleLoginForm" method="POST" action="login.php" style="display:none;">
    <input type="hidden" name="action" value="google_login">
    <input type="hidden" name="google_email" id="gFormEmail" value="">
    <input type="hidden" name="google_name" id="gFormName" value="">
    <input type="hidden" name="google_avatar" id="gFormAvatar" value="">
  </form>

  <!-- Google Account Chooser Modal -->
  <div class="google-modal" id="googleModal" onclick="if(event.target===this) closeGoogleChooser()">
    <div class="google-dialog">
      <div class="google-dialog-header">
        <svg width="28" height="28" viewBox="0 0 48 48" style="margin: 0 auto 10px; display: block;">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        <h3 style="font-family: var(--font-display); font-size: 17px; font-weight: 800; color: #fff; margin: 0 0 4px;">Choose a Google Account</h3>
        <p style="font-size: 12.5px; color: #94a3b8; margin: 0;">to continue to JKtech.LK</p>
      </div>
      <div class="google-dialog-body">
        <!-- Preset Demo Accounts -->
        <div class="google-account-item" onclick="submitGoogleLogin('kasun.perera@gmail.com', 'Kasun Perera', '')">
          <div class="google-acc-avatar" style="background:#ea4335;">K</div>
          <div style="overflow:hidden; flex:1;">
            <div style="font-weight:700; font-size:13.5px; color:#fff;">Kasun Perera</div>
            <div style="font-size:12px; color:#94a3b8;">kasun.perera@gmail.com</div>
          </div>
        </div>

        <div class="google-account-item" onclick="submitGoogleLogin('jktech.customer@gmail.com', 'Auto Garage LK', '')">
          <div class="google-acc-avatar" style="background:#10b981;">A</div>
          <div style="overflow:hidden; flex:1;">
            <div style="font-weight:700; font-size:13.5px; color:#fff;">Auto Garage LK</div>
            <div style="font-size:12px; color:#94a3b8;">jktech.customer@gmail.com</div>
          </div>
        </div>

        <!-- Custom Real Email Box -->
        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08);">
          <label style="display:block; font-size:12px; font-weight:700; color:#cbd5e1; margin-bottom:6px;">Or use your real Google / Gmail address:</label>
          <div style="display:flex; gap:8px;">
            <input type="email" id="customGoogleEmail" class="auth-input" placeholder="youremail@gmail.com" style="height:42px; font-size:13px; padding: 0 12px;">
            <button type="button" class="btn btn-primary btn-sm" onclick="submitCustomGoogle()" style="flex-shrink:0; height:42px; padding: 0 16px;">Sign In</button>
          </div>
        </div>

        <button type="button" onclick="closeGoogleChooser()" style="width:100%; margin-top:16px; background:transparent; border:none; color:#64748b; font-size:13px; cursor:pointer;">Cancel</button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    function openGoogleAccountChooser() {
      const modal = document.getElementById('googleModal');
      if (modal) modal.classList.add('active');
    }

    function closeGoogleChooser() {
      const modal = document.getElementById('googleModal');
      if (modal) modal.classList.remove('active');
    }

    function submitGoogleLogin(email, name, avatar) {
      document.getElementById('gFormEmail').value = email;
      document.getElementById('gFormName').value = name;
      document.getElementById('gFormAvatar').value = avatar;
      document.getElementById('googleLoginForm').submit();
    }

    function submitCustomGoogle() {
      const input = document.getElementById('customGoogleEmail');
      const email = (input ? input.value : '').trim();
      if (!email || !email.includes('@')) {
        alert('Please enter a valid Gmail address.');
        return;
      }
      submitGoogleLogin(email, '', '');
    }
    function switchAuthTab(mode) {
      const loginPanel = document.getElementById('panelLogin');
      const regPanel = document.getElementById('panelRegister');
      const tabLogin = document.getElementById('tabBtnLogin');
      const tabReg = document.getElementById('tabBtnRegister');
      const mainHeading = document.getElementById('authMainHeading');
      const subHeading = document.getElementById('authSubHeading');

      if (mode === 'register') {
        loginPanel.style.display = 'none';
        regPanel.style.display = 'block';
        tabLogin.classList.remove('active');
        tabReg.classList.add('active');
        mainHeading.textContent = 'Create Your Account';
        subHeading.textContent = 'Register with your email to track orders and speed up checkout';
      } else {
        regPanel.style.display = 'none';
        loginPanel.style.display = 'block';
        tabReg.classList.remove('active');
        tabLogin.classList.add('active');
        mainHeading.textContent = 'Sign In to Your Account';
        subHeading.textContent = 'Enter your registered email and password to continue';
      }
    }

    function togglePassword(inputId, btn) {
      const input = document.getElementById(inputId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.style.color = '#00e5ff';
      } else {
        input.type = 'password';
        btn.style.color = '#64748b';
      }
    }
  </script>
</body>
</html>
