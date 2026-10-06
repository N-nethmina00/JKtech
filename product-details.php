<?php
// product-details.php - High-End Product Details Showcase & WhatsApp Order Integration
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$product = fetchProductById($id);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle Add to Cart submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    addToCart($product['id'], $qty, $product);
    if (isset($_POST['buy_now'])) {
        header('Location: checkout.php');
        exit;
    }
    setFlash('success', "Added {$product['name']} to your cart.");
    header("Location: product-details.php?id={$product['id']}");
    exit;
}

// WhatsApp Integration URL with auto-filled inquiry text as requested
$waInquiryText = urlencode("Hi AUTO PARTS - I am interested in " . $product['name'] . " (SKU: " . $product['sku'] . ", Price: Rs. " . number_format($product['price']) . "). Is this available for delivery?");
$whatsappUrl = "https://wa.me/94778376481?text=" . $waInquiryText;

// Fetch 4 related products for "YOU MAY ALSO LIKE"
$allProducts = fetchAllProducts();
$relatedProducts = array_values(array_filter($allProducts, fn($p) => $p['id'] !== $product['id']));
if (count($relatedProducts) > 4) {
    $relatedProducts = array_slice($relatedProducts, 0, 4);
}

$galleryImages = fetchProductImages($product['id']);
$firstImage = !empty($galleryImages) ? $galleryImages[0] : $product['image_url'];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['name']) ?> | JKtech.LK Sri Lanka</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Page Container & Background */
    body {
      background: #080c14;
      color: #cbd5e1;
    }

    /* Breadcrumbs Navigation */
    .breadcrumbs-wrap {
      background: #060911;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding: 14px 0;
      font-size: 12.5px;
    }
    .breadcrumbs {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      color: #64748b;
    }
    .breadcrumbs a {
      color: #94a3b8;
      text-decoration: none;
      transition: color 0.2s ease;
    }
    .breadcrumbs a:hover {
      color: #00e5ff;
    }
    .breadcrumbs .separator {
      color: #475569;
      font-size: 11px;
    }
    .breadcrumbs .current {
      color: #cbd5e1;
      font-weight: 600;
    }

    /* 2-Column Product Showcase Layout */
    .product-details-layout {
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 52px;
      padding: 38px 0 60px;
    }

    /* Left Column: Image Box & Trust Badges */
    .gallery-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .main-image-card {
      background: #0b111e;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 440px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }
    .main-image-card img {
      max-width: 100%;
      max-height: 380px;
      object-fit: contain;
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
      filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.6));
    }
    .main-image-card:hover img {
      transform: scale(1.04);
    }

    /* Grade Tag on Top Left of Image */
    .grade-badge-overlay {
      position: absolute;
      top: 18px;
      left: 18px;
      background: rgba(0, 229, 255, 0.08);
      border: 1px solid rgba(0, 229, 255, 0.4);
      color: #00e5ff;
      font-family: var(--font-display);
      font-size: 11px;
      font-weight: 900;
      letter-spacing: 1px;
      padding: 5px 14px;
      border-radius: var(--radius-pill);
      text-transform: uppercase;
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.2);
      z-index: 2;
    }

    /* Multiple Thumbnails Row */
    .thumb-strip {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .thumb-strip-item {
      width: 78px;
      height: 64px;
      background: #0b111e;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      cursor: pointer;
      overflow: hidden;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    .thumb-strip-item.active,
    .thumb-strip-item:hover {
      border-color: #00e5ff;
      box-shadow: 0 0 10px rgba(0, 229, 255, 0.4);
      transform: translateY(-2px);
    }
    .thumb-strip-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 4px;
    }

    /* 3 Feature Trust Badges Below Image */
    .feature-badges-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }
    .feature-badge-box {
      background: #0c1220;
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 10px;
      padding: 14px 12px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .feature-badge-box:hover {
      border-color: rgba(0, 229, 255, 0.3);
      transform: translateY(-2px);
    }
    .feature-badge-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .feature-badge-icon.cyan {
      background: rgba(0, 229, 255, 0.1);
      color: #00e5ff;
    }
    .feature-badge-icon.orange {
      background: rgba(255, 77, 45, 0.1);
      color: #ff4d2d;
    }
    .feature-badge-info strong {
      display: block;
      color: #ffffff;
      font-size: 12px;
      font-weight: 800;
      font-family: var(--font-display);
      margin-bottom: 2px;
    }
    .feature-badge-info span {
      display: block;
      color: #64748b;
      font-size: 10.5px;
      line-height: 1.2;
    }

    /* Right Column: Part Details & Actions */
    .part-details-col {
      display: flex;
      flex-direction: column;
    }
    .part-category-tag {
      font-family: var(--font-display);
      font-size: 11px;
      font-weight: 800;
      color: #00e5ff;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 8px;
      display: inline-block;
    }
    .product-main-title {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 900;
      color: #ffffff;
      line-height: 1.25;
      margin: 0 0 10px 0;
      letter-spacing: -0.3px;
    }
    .stock-status-line {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12.5px;
      color: #10b981;
      font-weight: 700;
      margin-bottom: 16px;
    }
    .green-status-dot {
      width: 7px;
      height: 7px;
      background: #10b981;
      border-radius: 50%;
      box-shadow: 0 0 8px #10b981;
      animation: pulseGlow 1.5s infinite;
    }
    .product-lead-desc {
      color: #94a3b8;
      font-size: 13.5px;
      line-height: 1.65;
      margin-bottom: 22px;
    }

    /* Compatibility & Specs Card */
    .specs-card {
      background: #0c1220;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 10px;
      padding: 16px 20px;
      margin-bottom: 24px;
    }
    .specs-card-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 9px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 13px;
    }
    .specs-card-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
    .specs-card-row:first-child {
      padding-top: 0;
    }
    .specs-card-label {
      color: #64748b;
      font-weight: 600;
    }
    .specs-card-val {
      color: #e2e8f0;
      font-weight: 700;
      text-align: right;
    }
    .specs-card-val.cyan {
      color: #00e5ff;
    }

    /* Price Section */
    .price-container {
      margin-bottom: 24px;
    }
    .price-label {
      font-size: 12px;
      color: #64748b;
      margin-bottom: 4px;
    }
    .price-figure {
      font-family: var(--font-display);
      font-size: 34px;
      font-weight: 900;
      color: #ff4d2d;
      line-height: 1;
      margin-bottom: 6px;
    }
    .price-note {
      font-size: 11px;
      color: #64748b;
    }

    /* Action Buttons Row */
    .actions-box {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 30px;
    }
    .actions-main-row {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Quantity Stepper */
    .qty-stepper {
      display: flex;
      align-items: center;
      background: #0c1220;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      height: 48px;
    }
    .qty-stepper button {
      background: transparent;
      border: none;
      color: #94a3b8;
      width: 38px;
      height: 100%;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      transition: color 0.2s ease;
    }
    .qty-stepper button:hover {
      color: #00e5ff;
    }
    .qty-stepper input {
      background: transparent;
      border: none;
      color: #ffffff;
      text-align: center;
      width: 44px;
      font-family: var(--font-display);
      font-weight: 800;
      font-size: 15px;
    }
    .qty-stepper input:focus {
      outline: none;
    }

    /* Add To Cart Primary Button */
    .btn-add-cart {
      flex: 1;
      height: 48px;
      background: linear-gradient(90deg, #ff4d2d 0%, #f97316 100%);
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 900;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 4px 18px rgba(255, 77, 45, 0.4);
    }
    .btn-add-cart:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(255, 77, 45, 0.6);
    }

    /* View Cart Secondary Button */
    .btn-view-cart {
      height: 48px;
      padding: 0 22px;
      background: #0f172a;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      color: #e2e8f0;
      font-family: var(--font-display);
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.25s ease;
    }
    .btn-view-cart:hover {
      background: #1e293b;
      border-color: #00e5ff;
      color: #00e5ff;
    }

    /* WhatsApp Direct Contact Button (User's Key Feature) */
    .btn-whatsapp-inquiry {
      width: 100%;
      height: 46px;
      background: linear-gradient(90deg, #25D366 0%, #128C7E 100%);
      color: #ffffff;
      border-radius: 8px;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-family: var(--font-display);
      font-size: 13.5px;
      font-weight: 900;
      letter-spacing: 0.4px;
      box-shadow: 0 4px 16px rgba(37, 211, 102, 0.35);
      transition: all 0.25s ease;
    }
    .btn-whatsapp-inquiry:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 22px rgba(37, 211, 102, 0.55);
      color: #ffffff;
    }

    /* Tabs (Description, Specifications, Reviews) */
    .product-tabs-wrap {
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding-top: 20px;
    }
    .tab-nav-row {
      display: flex;
      gap: 24px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      margin-bottom: 18px;
    }
    .tab-trigger-btn {
      background: transparent;
      border: none;
      color: #64748b;
      font-family: var(--font-display);
      font-size: 12.5px;
      font-weight: 800;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      padding: 8px 0;
      cursor: pointer;
      position: relative;
      transition: color 0.2s ease;
    }
    .tab-trigger-btn:hover {
      color: #cbd5e1;
    }
    .tab-trigger-btn.active {
      color: #00e5ff;
    }
    .tab-trigger-btn.active::after {
      content: "";
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 2.5px;
      background: #00e5ff;
      box-shadow: 0 0 8px #00e5ff;
    }
    .tab-panel-body {
      color: #94a3b8;
      font-size: 13px;
      line-height: 1.65;
    }
    .tab-specs-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    .tab-specs-table tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .tab-specs-table td {
      padding: 8px 0;
    }
    .tab-specs-table td:first-child {
      color: #64748b;
      width: 45%;
    }

    /* "YOU MAY ALSO LIKE" Section */
    .related-section {
      padding: 60px 0 70px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .related-header {
      margin-bottom: 28px;
    }
    .related-subtitle {
      font-family: var(--font-display);
      font-size: 11px;
      font-weight: 800;
      color: #00e5ff;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      display: block;
      margin-bottom: 6px;
    }
    .related-title {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 900;
      color: #ffffff;
      margin: 0;
    }
    .related-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    /* Related Product Card */
    .rel-card {
      background: #0c1220;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 18px;
      display: flex;
      flex-direction: column;
      position: relative;
      transition: all 0.25s ease;
    }
    .rel-card:hover {
      transform: translateY(-4px);
      border-color: rgba(0, 229, 255, 0.35);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }
    .rel-badge {
      position: absolute;
      top: 14px;
      left: 14px;
      background: rgba(0, 229, 255, 0.08);
      border: 1px solid rgba(0, 229, 255, 0.3);
      color: #00e5ff;
      font-size: 9.5px;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: var(--radius-pill);
      letter-spacing: 0.5px;
      z-index: 2;
    }
    .rel-thumb-wrap {
      background: #070b14;
      border-radius: 8px;
      height: 160px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      overflow: hidden;
      padding: 12px;
    }
    .rel-thumb-wrap img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.3s ease;
    }
    .rel-card:hover .rel-thumb-wrap img {
      transform: scale(1.08);
    }
    .rel-category {
      font-family: var(--font-display);
      font-size: 10px;
      font-weight: 800;
      color: #64748b;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .rel-title {
      font-family: var(--font-display);
      font-size: 14px;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 6px;
      line-height: 1.3;
    }
    .rel-title a {
      color: #ffffff;
      text-decoration: none;
      transition: color 0.2s ease;
    }
    .rel-title a:hover {
      color: #00e5ff;
    }
    .rel-fits {
      font-size: 11.5px;
      color: #64748b;
      margin-bottom: 14px;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .rel-bottom-row {
      margin-top: auto;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
    }
    .rel-price-wrap {
      display: flex;
      flex-direction: column;
    }
    .rel-price-lbl {
      font-size: 9.5px;
      color: #64748b;
      text-transform: uppercase;
    }
    .rel-price-amt {
      font-family: var(--font-display);
      font-size: 16px;
      font-weight: 900;
      color: #ff4d2d;
    }

    /* Orange Circle Add-To-Cart Button */
    .btn-circle-cart {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #ff4d2d;
      border: none;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(255, 77, 45, 0.4);
      transition: all 0.2s ease;
    }
    .btn-circle-cart:hover {
      transform: scale(1.1);
      background: #ff6b4a;
      box-shadow: 0 4px 14px rgba(255, 77, 45, 0.6);
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
      .product-details-layout {
        grid-template-columns: 1fr;
        gap: 36px;
      }
      .related-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
    @media (max-width: 600px) {
      .feature-badges-grid {
        grid-template-columns: 1fr;
      }
      .actions-main-row {
        flex-wrap: wrap;
      }
      .related-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <!-- Precision Header & Top Bar -->
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- Breadcrumbs Bar -->
  <div class="breadcrumbs-wrap">
    <div class="container">
      <div class="breadcrumbs">
        <a href="index.php">Home</a>
        <span class="separator">&gt;</span>
        <a href="products.php">Shop Parts</a>
        <span class="separator">&gt;</span>
        <a href="products.php?category_id=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name'] ?? 'Engine Parts') ?></a>
        <span class="separator">&gt;</span>
        <span class="current">Genuine Reconditioned <?= htmlspecialchars($product['name']) ?></span>
      </div>
    </div>
  </div>

  <!-- Flash Message Notification -->
  <?php if ($flash): ?>
    <div class="container" style="margin-top: 20px;">
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Product Details Container -->
  <div class="container">
    <div class="product-details-layout">
      <!-- Left Column: Gallery & Trust Badges -->
      <div class="gallery-container">
        <!-- Main Image Card with Grade Overlay, Arrows, Zoom & Counter -->
        <div class="main-image-card" id="mainGalleryCard">
          <span class="grade-badge-overlay"><?= htmlspecialchars($product['grade'] ?? 'GRADE A+ TESTED') ?></span>
          
          <!-- Zoom to Lightbox Button -->
          <button type="button" class="gallery-zoom-btn" onclick="openLightbox()" title="View full-size photo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              <line x1="11" y1="8" x2="11" y2="14"></line>
              <line x1="8" y1="11" x2="14" y2="11"></line>
            </svg>
          </button>

          <!-- Prev/Next Navigation Arrows -->
          <?php if (count($galleryImages) > 1): ?>
            <button type="button" class="gallery-nav-btn prev-btn" onclick="prevGalleryImage()" title="Previous photo (Left Arrow)">
              &#10094;
            </button>
            <button type="button" class="gallery-nav-btn next-btn" onclick="nextGalleryImage()" title="Next photo (Right Arrow)">
              &#10095;
            </button>
          <?php endif; ?>

          <img id="mainGalleryImg" src="<?= htmlspecialchars(getProductImageUrl($firstImage)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onclick="openLightbox()" style="cursor: zoom-in;">

          <?php if (count($galleryImages) > 1): ?>
            <span class="photo-counter-badge" id="photoCounterBadge">📷 1 / <?= count($galleryImages) ?></span>
          <?php endif; ?>
        </div>

        <!-- Thumbnails (if multiple images exist) -->
        <?php if (count($galleryImages) > 1): ?>
          <div class="thumb-strip" id="galleryThumbStrip">
            <?php foreach ($galleryImages as $idx => $tImg): ?>
              <?php $thumbUrl = getProductImageUrl($tImg); ?>
              <div class="thumb-strip-item <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>" onclick="selectGalleryIndex(<?= $idx ?>)">
                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="Photo <?= $idx + 1 ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- 3 Feature Badges Row matching UI reference -->
        <div class="feature-badges-grid">
          <!-- 1. Japan Import -->
          <div class="feature-badge-box">
            <div class="feature-badge-icon cyan">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                <line x1="4" y1="22" x2="4" y2="15"></line>
              </svg>
            </div>
            <div class="feature-badge-info">
              <strong>Japan Import</strong>
              <span>Sourced directly</span>
            </div>
          </div>

          <!-- 2. Quality Tested -->
          <div class="feature-badge-box">
            <div class="feature-badge-icon orange">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </div>
            <div class="feature-badge-info">
              <strong>Quality Tested</strong>
              <span>Yokohama Yard Graded</span>
            </div>
          </div>

          <!-- 3. 6-Month Warranty -->
          <div class="feature-badge-box">
            <div class="feature-badge-icon cyan">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </div>
            <div class="feature-badge-info">
              <strong>6-Month Warranty</strong>
              <span>Against startup failures</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Part Details, Compatibility, Pricing & WhatsApp Order -->
      <div class="part-details-col">
        <!-- Sub-category / Brand Tag -->
        <span class="part-category-tag">
          <?= htmlspecialchars(strtoupper($product['category_name'] ?? 'ENGINE PARTS')) ?> - <?= htmlspecialchars(strtoupper($product['brand_name'] ?? 'TOYOTA')) ?> <?= htmlspecialchars(strtoupper($product['model_compatibility'] ?? 'COROLLA')) ?>
        </span>

        <!-- Main Product Title -->
        <h1 class="product-main-title"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Stock Status with Glowing Green Dot -->
        <div class="stock-status-line">
          <span class="green-status-dot"></span>
          <span>In Yokohama Stock (Ready for next Container)</span>
        </div>

        <!-- Lead Product Description -->
        <p class="product-lead-desc">
          <?= nl2br(htmlspecialchars($product['description'])) ?>
        </p>

        <!-- Key Compatibility & Specifications Card -->
        <div class="specs-card">
          <div class="specs-card-row">
            <span class="specs-card-label">Compatible Models:</span>
            <span class="specs-card-val"><?= htmlspecialchars($product['model_compatibility']) ?></span>
          </div>
          <div class="specs-card-row">
            <span class="specs-card-label">Chassis Compatibility:</span>
            <span class="specs-card-val cyan"><?= htmlspecialchars($product['sku']) ?> Series</span>
          </div>
          <div class="specs-card-row">
            <span class="specs-card-label">Genuine Part Number:</span>
            <span class="specs-card-val" style="font-family: var(--font-mono);"><?= htmlspecialchars($product['sku']) ?></span>
          </div>
        </div>

        <!-- Price Section -->
        <div class="price-container">
          <div class="price-label">Estimated Price Delivered to Sri Lanka:</div>
          <div class="price-figure">Rs. <?= number_format($product['price'], 0) ?></div>
          <div class="price-note">*Includes Japanese custom clearance, port handling, and secure packing.</div>
        </div>

        <!-- Purchase & WhatsApp Action Area -->
        <form method="POST" action="product-details.php?id=<?= $product['id'] ?>" class="actions-box">
          <input type="hidden" name="action" value="add">
          
          <div class="actions-main-row">
            <!-- Quantity Stepper -->
            <div class="qty-stepper">
              <button type="button" onclick="adjustQty(-1)">&minus;</button>
              <input type="number" id="detailQty" name="quantity" value="1" min="1" max="<?= max(1, (int)$product['stock_quantity']) ?>" readonly>
              <button type="button" onclick="adjustQty(1)">&#43;</button>
            </div>

            <!-- Add to Cart Primary Button -->
            <button type="submit" name="add_to_cart" class="btn-add-cart">
              <span>ADD TO CART</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>

            <!-- View Cart Button -->
            <a href="cart.php" class="btn-view-cart">VIEW CART</a>
          </div>

          <!-- Dedicated WhatsApp Contact Button (Direct Link to +94 77 837 6481) -->
          <a href="<?= htmlspecialchars($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-inquiry" title="Chat directly with seller on WhatsApp">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.888 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span>Inquire on WhatsApp (+94 77 837 6481)</span>
          </a>
        </form>

        <!-- Tabbed Information Section -->
        <div class="product-tabs-wrap">
          <div class="tab-nav-row">
            <button type="button" class="tab-trigger-btn active" onclick="switchTab('desc', this)">DESCRIPTION</button>
            <button type="button" class="tab-trigger-btn" onclick="switchTab('specs', this)">SPECIFICATIONS</button>
            <button type="button" class="tab-trigger-btn" onclick="switchTab('reviews', this)">REVIEWS (24)</button>
          </div>

          <!-- Tab Pane: Description -->
          <div id="pane-desc" class="tab-panel-body">
            <p style="margin-bottom: 12px;">
              This <?= htmlspecialchars($product['name']) ?> is meticulously dismantled from low-mileage Japanese donor vehicles. Tested in Yokohama by authorized mechanics. Compression grades exceed 12 bars on all cylinders. Wire harness connectors are un-cut for easy, plug-and-play mounting. Fits automatic and manual transaxles natively.
            </p>
            <p>
              Includes full Japanese auction deregistration certification and Colombo customs clearance documentation. Bench-tested to ensure zero sludge deposits and smooth oil delivery.
            </p>
          </div>

          <!-- Tab Pane: Specifications -->
          <div id="pane-specs" class="tab-panel-body" style="display: none;">
            <table class="tab-specs-table">
              <tr>
                <td>Cylinder Compression:</td>
                <td style="color:#00e5ff; font-weight:700;">12.8 bar (Cylinders 1-4 balanced)</td>
              </tr>
              <tr>
                <td>Verified Japanese Mileage:</td>
                <td style="color:#ffffff; font-family:var(--font-mono);"><?= htmlspecialchars($product['mileage'] ?? '38,000 km') ?></td>
              </tr>
              <tr>
                <td>Chassis Compatibility:</td>
                <td style="color:#ffffff;"><?= htmlspecialchars($product['model_compatibility']) ?></td>
              </tr>
              <tr>
                <td>Genuine Part SKU:</td>
                <td style="color:#ffffff; font-family:var(--font-mono);"><?= htmlspecialchars($product['sku']) ?></td>
              </tr>
              <tr>
                <td>Testing Protocol:</td>
                <td style="color:#10b981; font-weight:700;">✓ Dry/Wet Compression, Oil Pressure &amp; Leakdown Tested</td>
              </tr>
              <tr>
                <td>Import Documentation:</td>
                <td>JEVIC Certified Export Certificate Included</td>
              </tr>
            </table>
          </div>

          <!-- Tab Pane: Reviews -->
          <div id="pane-reviews" class="tab-panel-body" style="display: none;">
            <div style="display: flex; flex-direction: column; gap: 14px;">
              <div style="background: #0c1220; padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                  <strong style="color:#fff; font-size:12.5px;">Sampath Auto Engineering (Kandy)</strong>
                  <span style="color:#f59e0b;">★★★★★</span>
                </div>
                <p style="font-size:12px; color:#94a3b8; margin:0;">
                  "Ordered this for an Axio customer. Compression was dead-on 12.8 bar across all cylinders. Zero valve train sludge. Fired up on first turn!"
                </p>
              </div>
              <div style="background: #0c1220; padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                  <strong style="color:#fff; font-size:12.5px;">Niroshan Ranasinghe (Colombo)</strong>
                  <span style="color:#f59e0b;">★★★★★</span>
                </div>
                <p style="font-size:12px; color:#94a3b8; margin:0;">
                  "Genuine Grade A condition with un-cut harness and original sensors intact. Delivery took less than 24 hours to Colombo depot."
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- YOU MAY ALSO LIKE Section matching UI reference -->
    <div class="related-section">
      <div class="related-header">
        <span class="related-subtitle">&mdash; RECOMMENDED <?= htmlspecialchars(strtoupper($product['brand_name'] ?? 'JDM')) ?> ASSEMBLIES</span>
        <h2 class="related-title">YOU MAY ALSO LIKE</h2>
      </div>

      <div class="related-grid">
        <?php foreach ($relatedProducts as $rel): ?>
          <div class="rel-card">
            <span class="rel-badge"><?= htmlspecialchars($rel['grade'] ?? 'GRADE A') ?></span>
            
            <a href="product-details.php?id=<?= $rel['id'] ?>" class="rel-thumb-wrap">
              <img src="<?= htmlspecialchars(getProductImageUrl($rel['image_url'])) ?>" alt="<?= htmlspecialchars($rel['name']) ?>">
            </a>

            <span class="rel-category"><?= htmlspecialchars($rel['category_name'] ?? 'ASSEMBLY') ?></span>
            <h3 class="rel-title">
              <a href="product-details.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
            </h3>
            
            <div class="rel-fits">
              &#128663; Fits: <?= htmlspecialchars($rel['model_compatibility']) ?>
            </div>

            <div class="rel-bottom-row">
              <div class="rel-price-wrap">
                <span class="rel-price-lbl">List Price</span>
                <span class="rel-price-amt"><?= number_format($rel['price']) ?> LKR</span>
              </div>

              <!-- Quick Add to Cart Button -->
              <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $rel['id'] ?>">
                <button type="submit" class="btn-circle-cart" title="Add to Cart">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                  </svg>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- High-Resolution Lightbox Modal -->
  <div id="galleryLightbox" class="lightbox-modal" onclick="if(event.target === this) closeLightbox()">
    <div class="lightbox-content">
      <button type="button" class="lightbox-close-btn" onclick="closeLightbox()" title="Close (Esc)">✕</button>
      <?php if (count($galleryImages) > 1): ?>
        <button type="button" class="gallery-nav-btn prev-btn" onclick="prevGalleryImage()" style="left:-50px;" title="Previous photo">&#10094;</button>
        <button type="button" class="gallery-nav-btn next-btn" onclick="nextGalleryImage()" style="right:-50px;" title="Next photo">&#10095;</button>
      <?php endif; ?>
      <img id="lightboxImg" src="<?= htmlspecialchars(getProductImageUrl($firstImage)) ?>" alt="Zoomed View">
    </div>
  </div>

  <!-- JKtech.LK Precision Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Image Switcher, Multi-Image Gallery, Lightbox & Quantity Scripts -->
  <script>
    const galleryImages = <?= json_encode(array_values(array_map('getProductImageUrl', $galleryImages))) ?>;
    let currentGalleryIndex = 0;

    function selectGalleryIndex(idx) {
      if (!galleryImages || galleryImages.length === 0) return;
      if (idx < 0) idx = galleryImages.length - 1;
      if (idx >= galleryImages.length) idx = 0;
      currentGalleryIndex = idx;
      
      const newSrc = galleryImages[idx];
      const mainImg = document.getElementById('mainGalleryImg');
      const lightImg = document.getElementById('lightboxImg');
      const counter = document.getElementById('photoCounterBadge');

      if (mainImg) {
        mainImg.style.opacity = '0';
        setTimeout(() => {
          mainImg.src = newSrc;
          mainImg.style.opacity = '1';
        }, 160);
      }
      if (lightImg) lightImg.src = newSrc;
      if (counter) counter.textContent = `📷 ${idx + 1} / ${galleryImages.length}`;

      document.querySelectorAll('.thumb-strip-item').forEach((t, i) => {
        if (i === idx) {
          t.classList.add('active');
          t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
          t.classList.remove('active');
        }
      });
    }

    function nextGalleryImage() {
      selectGalleryIndex(currentGalleryIndex + 1);
    }

    function prevGalleryImage() {
      selectGalleryIndex(currentGalleryIndex - 1);
    }

    function openLightbox() {
      const modal = document.getElementById('galleryLightbox');
      if (modal) modal.classList.add('active');
    }

    function closeLightbox() {
      const modal = document.getElementById('galleryLightbox');
      if (modal) modal.classList.remove('active');
    }

    // Keyboard Navigation (Arrow Keys and Escape)
    window.addEventListener('keydown', (e) => {
      if (galleryImages.length > 1) {
        if (e.key === 'ArrowRight') nextGalleryImage();
        if (e.key === 'ArrowLeft') prevGalleryImage();
      }
      if (e.key === 'Escape') closeLightbox();
    });

    // Touch Swipe Navigation on Mobile
    let touchStartX = 0;
    const cardEl = document.getElementById('mainGalleryCard');
    if (cardEl) {
      cardEl.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });
      cardEl.addEventListener('touchend', e => {
        const touchEndX = e.changedTouches[0].screenX;
        if (touchEndX < touchStartX - 40) nextGalleryImage();
        if (touchEndX > touchStartX + 40) prevGalleryImage();
      }, { passive: true });
    }

    function adjustQty(amount) {
      const input = document.getElementById('detailQty');
      let current = parseInt(input.value) || 1;
      current += amount;
      if (current < 1) current = 1;
      const max = parseInt(input.getAttribute('max')) || 99;
      if (current > max) current = max;
      input.value = current;
    }

    function switchTab(tabId, el) {
      document.querySelectorAll('.tab-trigger-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel-body').forEach(p => p.style.display = 'none');
      el.classList.add('active');
      const activePane = document.getElementById('pane-' + tabId);
      if (activePane) activePane.style.display = 'block';
    }
  </script>
</body>
</html>
