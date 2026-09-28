<?php
// includes/navbar.php - JKtech.LK Precision Header & Top Bar
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
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
          <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
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
      <?php else: ?>
        <a href="admin/login.php" class="top-info-item top-link" style="color: #64748b; font-size: 11.5px; margin-left: 8px;" title="Administrator Console">Admin Login</a>
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
      <li>
        <?php if ($user): ?>
          <a href="<?= isAdmin() ? 'admin/index.php' : 'profile.php' ?>"><?= isAdmin() ? 'ADMIN' : 'ACCOUNT' ?></a>
        <?php else: ?>
          <a href="login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">LOGIN</a>
        <?php endif; ?>
      </li>
    </ul>

    <!-- Right: User Account Profile & Shopping Cart Circle Buttons -->
    <div class="nav-circle-actions">
      <!-- Profile Button -->
      <a href="<?= $user ? (isAdmin() ? 'admin/index.php' : 'profile.php') : 'login.php' ?>" class="nav-circle-btn" title="<?= $user ? htmlspecialchars($user['name']) : 'Sign In / Account' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
      </a>

      <!-- Cart Button with Orange Count Badge -->
      <a href="cart.php" class="nav-circle-btn" title="View Cart">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="nav-cart-badge"><?= $cartCount > 0 ? $cartCount : 3 ?></span>
      </a>
    </div>
  </div>
</header>
