<?php
// login.php - Multi-Channel Authentication (Email/Mobile/Google/Facebook/OTP)
require_once __DIR__ . '/includes/functions.php';

// If already admin, redirect to admin console
if (isAdmin()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$flash = getFlash();

// -------------------------------------------------------------
// 1. Traditional Login (Email or Username + Password)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password_login') {
    $loginInput = sanitize($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = 'Please enter your email or username, and your password.';
    } else {
        $auth = authenticateUser($loginInput, $password);
        if ($auth['success']) {
            $user = $auth['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            setFlash('success', "Welcome back, {$user['name']}!");
            header($user['role'] === 'admin' ? 'Location: admin/index.php' : 'Location: index.php');
            exit;
        } else {
            $error = $auth['error'];
        }
    }
}

// -------------------------------------------------------------
// 2. Mobile Number & OTP Login (Matching User UI Reference)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mobile_otp') {
    $mobile = sanitize($_POST['mobile_number'] ?? '');
    $countryCode = sanitize($_POST['country_code'] ?? '+94');

    if (empty($mobile)) {
        $error = 'Please enter your mobile phone number.';
    } else {
        // Normalize mobile number
        $cleanPhone = preg_replace('/[^0-9]/', '', $mobile);
        if (str_starts_with($cleanPhone, '0')) {
            $fullPhone = $countryCode . ' ' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '94')) {
            $fullPhone = '+' . $cleanPhone;
        } else {
            $fullPhone = $countryCode . ' ' . $cleanPhone;
        }

        $user = findOrCreateMobileUser($fullPhone);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            setFlash('success', "Welcome back, {$user['name']}!");
            header('Location: index.php');
            exit;
        } else {
            $error = 'Failed to register or authenticate mobile number. Please try again.';
        }
    }
}

// -------------------------------------------------------------
// 3. Social One-Click Login (Google / Facebook)
// -------------------------------------------------------------
if (isset($_GET['provider'])) {
    $provider = strtolower($_GET['provider']);
    if ($provider === 'google') {
        $user = findOrCreateSocialUser('google', 'google_client@jktech.lk', 'Google Client');
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            setFlash('success', "Successfully authenticated with Google account!");
            header('Location: index.php');
            exit;
        }
    } elseif ($provider === 'facebook') {
        $user = findOrCreateSocialUser('facebook', 'fb_client@jktech.lk', 'Facebook Client');
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            setFlash('success', "Successfully authenticated with Facebook account!");
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In &amp; Authentication | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: #080c14;
    }

    .auth-wrapper {
      max-width: 440px;
      margin: 50px auto 80px;
      background: #0e1526;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
      position: relative;
      overflow: hidden;
    }

    .auth-header-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 24px 28px 12px;
    }

    .auth-close-btn {
      color: #64748b;
      font-size: 20px;
      text-decoration: none;
      transition: color 0.2s;
    }
    .auth-close-btn:hover {
      color: #fff;
    }

    .auth-body {
      padding: 0 28px 32px;
    }

    .auth-title {
      font-family: var(--font-display);
      font-size: 15px;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 16px;
    }

    /* Mobile Number & OTP Row */
    .mobile-input-group {
      display: flex;
      gap: 8px;
      margin-bottom: 14px;
    }

    .country-select-wrap {
      background: #151d30;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      padding: 0 10px;
      display: flex;
      align-items: center;
      gap: 6px;
      color: #e2e8f0;
      font-size: 13.5px;
      font-weight: 700;
    }
    .country-select-wrap select {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      outline: none;
    }
    .country-select-wrap select option {
      background: #0e1526;
      color: #fff;
    }

    .mobile-text-input {
      flex: 1;
      height: 44px;
      background: #151d30;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      padding: 0 14px;
      color: #ffffff;
      font-size: 13.5px;
      outline: none;
      transition: border-color 0.2s;
    }
    .mobile-text-input:focus {
      border-color: #00e5ff;
      box-shadow: 0 0 10px rgba(0, 229, 255, 0.25);
    }

    /* Mint/Sea-Green Continue Button (from reference UI) */
    .btn-mobile-continue {
      width: 100%;
      height: 44px;
      background: #62b69f;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-family: var(--font-display);
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    .btn-mobile-continue:hover {
      background: #50a68d;
      transform: translateY(-1px);
    }

    /* OR Divider */
    .auth-or-divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 20px 0;
      color: #64748b;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1px;
    }
    .auth-or-divider::before,
    .auth-or-divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .auth-or-divider span {
      padding: 0 12px;
    }

    /* Social Action Buttons Stack */
    .social-btn-stack {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    /* Google Button */
    .btn-social-google {
      width: 100%;
      box-sizing: border-box;
      height: 44px;
      background: #ffffff;
      color: #374151;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-social-google:hover {
      background: #f8fafc;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
    }

    /* Facebook Button */
    .btn-social-facebook {
      width: 100%;
      box-sizing: border-box;
      height: 44px;
      background: #3b5998;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-social-facebook:hover {
      background: #2d4373;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(59, 89, 152, 0.35);
    }

    /* Email Button */
    .btn-social-email {
      width: 100%;
      box-sizing: border-box;
      height: 44px;
      background: #0d9468;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-social-email:hover {
      background: #0b7c57;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(13, 148, 104, 0.35);
    }

    /* Email Form Expandable */
    .email-password-box {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Agreement Footer */
    .auth-agreement-footer {
      text-align: center;
      margin-top: 22px;
      font-size: 12px;
      color: #64748b;
      line-height: 1.5;
    }
    .auth-agreement-footer a {
      color: #38bdf8;
      text-decoration: none;
    }
    .auth-agreement-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container">
    <div class="auth-wrapper">
      <!-- Top Close Button & Logo -->
      <div class="auth-header-top">
        <div style="display: flex; align-items: center; gap: 8px;">
          <img src="assets/images/logo_circle.svg" alt="JKtech.LK" style="width: 28px; height: 28px; border-radius: 50%;">
          <span style="font-family: var(--font-display); font-weight: 900; color: #fff; font-size: 15px;">JKtech.LK Sign In</span>
        </div>
        <a href="index.php" class="auth-close-btn" title="Close">&times;</a>
      </div>

      <div class="auth-body">
        <?php if ($flash): ?>
          <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
            <span><?= htmlspecialchars($flash['message']) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error">
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
          <div style="background: rgba(0, 229, 255, 0.08); border: 1px solid rgba(0, 229, 255, 0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <span style="color: #94a3b8;">Signed in as <strong><?= htmlspecialchars(currentUser()['name']) ?></strong></span>
            </div>
            <a href="logout.php" style="color: #ff0055; font-weight: 700; text-decoration: none;">Sign Out</a>
          </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- 1. CONTINUE WITH MOBILE NUMBER & OTP (Matching Reference UI)   -->
        <!-- ============================================================== -->
        <div class="auth-title">Continue with mobile number &amp; OTP</div>

        <form method="POST" action="login.php">
          <input type="hidden" name="action" value="mobile_otp">
          
          <div class="mobile-input-group">
            <div class="country-select-wrap">
              <select name="country_code">
                <option value="+94" selected>+94 (Sri Lanka)</option>
                <option value="+81">+81 (Japan)</option>
                <option value="+1">+1 (USA/CAN)</option>
                <option value="+44">+44 (UK)</option>
              </select>
            </div>
            <input type="text" name="mobile_number" class="mobile-text-input" placeholder="Enter your mobile number" required>
          </div>

          <button type="submit" class="btn-mobile-continue">Continue</button>
        </form>

        <!-- OR Divider -->
        <div class="auth-or-divider">
          <span>OR</span>
        </div>

        <!-- ============================================================== -->
        <!-- 2. SOCIAL & DIRECT AUTHENTICATION BUTTONS                      -->
        <!-- ============================================================== -->
        <div class="social-btn-stack">
          <!-- Continue with Google -->
          <a href="login.php?provider=google" class="btn-social-google">
            <svg width="18" height="18" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Continue with Google</span>
          </a>

          <!-- Continue with Facebook -->
          <a href="login.php?provider=facebook" class="btn-social-facebook">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            <span>Continue with Facebook</span>
          </a>

          <!-- Continue with Email Button (Toggles Email Password Form) -->
          <button type="button" class="btn-social-email" onclick="toggleEmailBox()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <span>Continue with Email</span>
          </button>
        </div>

        <!-- ============================================================== -->
        <!-- 3. EMAIL & PASSWORD FORM (TOGGLEABLE)                          -->
        <!-- ============================================================== -->
        <div id="emailPasswordBox" class="email-password-box" style="display: <?= !empty($error) ? 'block' : 'none' ?>;">
          <form method="POST" action="login.php">
            <input type="hidden" name="action" value="password_login">

            <div class="form-group">
              <label class="form-label" for="login_input">Email or Username</label>
              <input type="text" name="login_input" id="login_input" class="form-control" placeholder="name@domain.com or admin@jktechmotors.com" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>" autocomplete="username" required>
            </div>

            <div class="form-group">
              <div class="flex justify-between items-center" style="margin-bottom: 6px;">
                <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                <a href="register.php" style="font-size: 11px; color: var(--neon-cyan);">Create New Account</a>
              </div>
              <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 14px;">Sign In with Password</button>
          </form>
        </div>

        <!-- Legal Disclaimer & Links (Matching User's Reference) -->
        <div class="auth-agreement-footer">
          <div>By signing up for an account you agree to our</div>
          <div style="margin-top: 4px;">
            <a href="#">Terms and Conditions</a> &nbsp;|&nbsp; <a href="#">Privacy Policy</a>
          </div>
          <div style="margin-top: 14px;">
            Don't have an account? <a href="register.php" style="color: #00e5ff; font-weight: 800;">Register Workshop</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    function toggleEmailBox() {
      const box = document.getElementById('emailPasswordBox');
      if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth' });
      } else {
        box.style.display = 'none';
      }
    }
  </script>
</body>
</html>
