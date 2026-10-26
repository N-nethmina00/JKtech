<?php
// includes/navbar.php - JKtech.LK Precision Header with Logged-in User Profile Icon & Username Pill
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if a login email notification was just sent in this session
$loginEmailAlert = null;
if (!empty($_SESSION['login_email_alert'])) {
    $loginEmailAlert = $_SESSION['login_email_alert'];
    unset($_SESSION['login_email_alert']);
}
?>
<style>
/* Scoped Header Profile & Dropdown Rules */
.nav-circle-actions {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
}
.nav-user-menu-wrap {
  position: relative !important;
  display: inline-flex !important;
  align-items: center !important;
}
.nav-user-pill {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  background: #101726 !important;
  border: 1px solid rgba(0, 229, 255, 0.4) !important;
  border-radius: 999px !important;
  padding: 4px 14px 4px 5px !important;
  cursor: pointer !important;
  color: #ffffff !important;
  text-decoration: none !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4) !important;
  user-select: none !important;
  transition: all 0.25s ease !important;
}
.nav-user-pill:hover {
  border-color: #00e5ff !important;
  background: #152238 !important;
  box-shadow: 0 0 18px rgba(0, 229, 255, 0.35) !important;
}
.nav-user-avatar-box {
  position: relative !important;
  width: 34px !important;
  height: 34px !important;
  flex-shrink: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}
.nav-user-avatar-img {
  width: 34px !important;
  height: 34px !important;
  max-width: 34px !important;
  max-height: 34px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 1.5px solid #00e5ff !important;
  display: block !important;
}
.nav-user-status-dot {
  position: absolute !important;
  bottom: -1px !important;
  right: -1px !important;
  width: 9px !important;
  height: 9px !important;
  border-radius: 50% !important;
  background: #10b981 !important;
  border: 2px solid #101726 !important;
}
.nav-user-info-text {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  line-height: 1.15 !important;
  text-align: left !important;
}
.nav-user-name {
  font-family: inherit !important;
  font-size: 13.5px !important;
  font-weight: 800 !important;
  color: #ffffff !important;
  max-width: 140px !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}
.nav-user-role-tag {
  font-size: 10px !important;
  font-weight: 700 !important;
  color: #00e5ff !important;
  letter-spacing: 0.3px !important;
  text-transform: uppercase !important;
}
.nav-user-chevron {
  color: #94a3b8 !important;
  margin-left: 2px !important;
  transition: transform 0.25s ease !important;
  flex-shrink: 0 !important;
}
.nav-user-menu-wrap.active .nav-user-chevron {
  transform: rotate(180deg) !important;
}
.nav-user-dropdown {
  position: absolute !important;
  top: calc(100% + 12px) !important;
  right: 0 !important;
  width: 260px !important;
  background: #0c1322 !important;
  border: 1px solid rgba(0, 229, 255, 0.35) !important;
  border-radius: 14px !important;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85) !important;
  z-index: 9999 !important;
  display: none !important;
  overflow: hidden !important;
  backdrop-filter: blur(12px) !important;
}
.nav-user-dropdown.open {
  display: block !important;
}
.nav-dropdown-header {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 16px !important;
  background: #080d18 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.nav-dropdown-avatar {
  width: 42px !important;
  height: 42px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 2px solid #00e5ff !important;
  flex-shrink: 0 !important;
}
.nav-dropdown-name {
  font-size: 13.5px !important;
  font-weight: 800 !important;
  color: #ffffff !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}
.nav-dropdown-email {
  font-size: 11px !important;
  color: #94a3b8 !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  margin-top: 2px !important;
}
.nav-dropdown-links {
  padding: 8px !important;
}
.nav-dropdown-item {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 9px 12px !important;
  border-radius: 8px !important;
  color: #cbd5e1 !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
}
.nav-dropdown-item:hover {
  background: rgba(0, 229, 255, 0.1) !important;
  color: #00e5ff !important;
}
.nav-dropdown-divider {
  height: 1px !important;
  background: rgba(255, 255, 255, 0.08) !important;
  margin: 6px 0 !important;
}
.nav-dropdown-item.logout-item {
  color: #f87171 !important;
}
.nav-dropdown-item.logout-item:hover {
  background: rgba(239, 68, 68, 0.15) !important;
  color: #ef4444 !important;
}
@media (max-width: 640px) {
  .nav-user-info-text {
    display: none !important;
  }
  .nav-user-pill {
    padding: 3px 8px 3px 4px !important;
    gap: 4px !important;
  }
}
</style>
<!-- Top Announcement Bar matching reference UI -->
<div class="top-banner">
  <div class="container top-banner-inner">
    <!-- Left: Direct import & Islandwide delivery badges -->
    <div class="top-banner-left">
      <span class="top-info-item">
        <svg class="top-icon cyan" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
          <line x1="4" y1="22" x2="4" y2="15"></line>
        </svg>
        <span>Direct import from Japan</span>
      </span>
      <span class="top-info-item">
        <svg class="top-icon cyan" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="1" y="3" width="15" height="13"></rect>
          <polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon>
          <circle cx="5.5" cy="18.5" r="2.5"></circle>
          <circle cx="18.5" cy="18.5" r="2.5"></circle>
        </svg>
        <span>Island-wide delivery to Sri Lanka</span>
      </span>
    </div>

    <!-- Right: Hotline and Email Support -->
    <div class="top-banner-right">
      <a href="tel:+94778376481" class="top-info-item top-link">
        <svg class="top-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
        </svg>
        <span>+94 77 837 6481</span>
      </a>
      <a href="mailto:jktech00@gmail.com" class="top-info-item top-link">
        <svg class="top-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
          <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        <span>jktech00@gmail.com</span>
      </a>
      <?php if (isAdmin()): ?>
        <a href="admin/index.php" class="top-admin-badge">★ Admin Console</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Main Sticky Header -->
<header class="site-header">
  <div class="container navbar">
    <!-- Left: Circular Logo Emblem + Brand Name JKtech.LK -->
    <a href="index.php" class="brand-badge" title="JKtech.LK - Home">
      <img src="assets/images/logo_circle.svg" alt="JKtech.LK" class="brand-emblem-circle">
      <span class="brand-text">JKtech<span class="brand-domain">.LK</span></span>
    </a>

    <!-- Center: Primary Navigation Links -->
    <ul class="nav-links">
      <li>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">HOME</a>
      </li>
      <li>
        <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">SHOP PARTS</a>
      </li>
      <li>
        <a href="index.php#about">ABOUT</a>
      </li>
      <li>
        <a href="#footer">CONTACT</a>
      </li>
      <?php if (!$user): ?>
        <li>
          <a href="login.php" class="<?= in_array($currentPage, ['login.php', 'register.php']) ? 'active' : '' ?>">LOGIN</a>
        </li>
      <?php endif; ?>
    </ul>

    <!-- Right: User Account Profile & Shopping Cart Circle Buttons -->
    <div class="nav-circle-actions">
      <?php if ($user): ?>
        <?php $avatarSrc = function_exists('getUserAvatarUrl') ? getUserAvatarUrl($user) : 'assets/images/logo_circle.svg'; ?>
        <!-- Logged-in User Profile Pill with Avatar Icon + Username Display -->
        <div class="nav-user-menu-wrap" id="navUserMenuWrap">
          <div class="nav-user-pill" onclick="toggleNavUserMenu(event)" title="My Account: <?= htmlspecialchars($user['name']) ?>">
            <span class="nav-user-avatar-box">
              <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="nav-user-avatar-img">
              <span class="nav-user-status-dot" title="Active"></span>
            </span>
            <span class="nav-user-info-text">
              <span class="nav-user-name"><?= htmlspecialchars($user['name']) ?></span>
              <span class="nav-user-role-tag"><?= isAdmin() ? 'Administrator' : 'Account' ?></span>
            </span>
            <svg class="nav-user-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>

          <!-- Dropdown Menu on Click -->
          <div class="nav-user-dropdown" id="navUserDropdown">
            <div class="nav-dropdown-header">
              <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="nav-dropdown-avatar">
              <div style="overflow: hidden;">
                <div class="nav-dropdown-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="nav-dropdown-email"><?= htmlspecialchars($user['email']) ?></div>
              </div>
            </div>
            <div class="nav-dropdown-links">
              <a href="profile.php" class="nav-dropdown-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>My Profile &amp; Photo</span>
              </a>
              <a href="orders.php" class="nav-dropdown-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span>My Orders</span>
              </a>
              <?php if (isAdmin()): ?>
                <a href="admin/index.php" class="nav-dropdown-item" style="color: #00e5ff;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                  <span>Admin Console</span>
                </a>
              <?php endif; ?>
              <div class="nav-dropdown-divider"></div>
              <a href="logout.php" class="nav-dropdown-item logout-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                  <polyline points="16 17 21 12 16 7"></polyline>
                  <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Sign Out</span>
              </a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Guest: Anonymous User Circle Button linking to Login -->
        <a href="login.php" class="nav-circle-btn" title="Sign In / Create Account">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </a>
      <?php endif; ?>

      <!-- Cart Button with Orange Count Badge -->
      <a href="cart.php" class="nav-circle-btn" title="View Cart">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="nav-cart-badge cart-badge" id="navCartBadge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
      </a>
    </div>
  </div>
</header>

<?php if ($loginEmailAlert): ?>
  <!-- Floating Login Email Notification Banner + Modal Preview -->
  <div class="email-toast-alert" id="loginEmailToast">
    <div class="email-toast-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00e5ff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
        <polyline points="22,6 12,13 2,6"></polyline>
      </svg>
    </div>
    <div class="email-toast-content">
      <div class="email-toast-title">Login Alert Email Dispatched</div>
      <div class="email-toast-desc">
        Confirmation sent to <strong><?= htmlspecialchars($loginEmailAlert['to']) ?></strong>
      </div>
      <div class="email-toast-actions">
        <button type="button" class="email-toast-view-btn" onclick="openEmailPreviewModal()">View Sent Email</button>
      </div>
    </div>
    <button type="button" class="email-toast-close" onclick="document.getElementById('loginEmailToast').remove()">&times;</button>
  </div>

  <!-- Sent Email Viewer Modal -->
  <div class="email-preview-modal" id="emailPreviewModal" onclick="if(event.target===this) closeEmailPreviewModal()">
    <div class="email-preview-dialog">
      <div class="email-preview-top">
        <div>
          <span style="font-size: 11px; color: #00e5ff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Sent Security Notification</span>
          <div style="font-size: 15px; font-weight: 800; color: #fff; margin-top: 2px;"><?= htmlspecialchars($loginEmailAlert['subject']) ?></div>
        </div>
        <button type="button" class="email-preview-close-btn" onclick="closeEmailPreviewModal()">&times;</button>
      </div>
      <div class="email-preview-meta">
        <div><strong>To:</strong> <?= htmlspecialchars($loginEmailAlert['name']) ?> &lt;<?= htmlspecialchars($loginEmailAlert['to']) ?>&gt;</div>
        <div><strong>Time:</strong> <?= htmlspecialchars($loginEmailAlert['time']) ?> &bull; <strong>Method:</strong> <?= htmlspecialchars($loginEmailAlert['method']) ?></div>
      </div>
      <iframe src="assets/uploads/emails/latest_login_email.html" class="email-preview-iframe" title="Email Preview"></iframe>
    </div>
  </div>
<?php endif; ?>

<script>
  function toggleNavUserMenu(e) {
    if (e) e.stopPropagation();
    const dd = document.getElementById('navUserDropdown');
    const wrap = document.getElementById('navUserMenuWrap');
    if (dd) {
      dd.classList.toggle('open');
      if (wrap) wrap.classList.toggle('active');
    }
  }
  document.addEventListener('click', function(e) {
    const wrap = document.getElementById('navUserMenuWrap');
    const dd = document.getElementById('navUserDropdown');
    if (wrap && dd && !wrap.contains(e.target)) {
      dd.classList.remove('open');
      wrap.classList.remove('active');
    }
  });
  function openEmailPreviewModal() {
    const m = document.getElementById('emailPreviewModal');
    if (m) m.classList.add('active');
  }
  function closeEmailPreviewModal() {
    const m = document.getElementById('emailPreviewModal');
    if (m) m.classList.remove('active');
  }
</script>
