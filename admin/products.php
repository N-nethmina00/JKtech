<?php
// admin/products.php - Manage Inventory & Multi-Image Parts CRUD
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();
$dbStatus = getDbStatus();

$categories = fetchCategories();
$brands = fetchBrands();

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../assets/uploads/products/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// -------------------------------------------------------------
// Handle Form Submissions (Create, Update, Delete, Photo Actions)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW PRODUCT WITH MULTIPLE PHOTOS
    if ($action === 'create' && $pdo) {
        $sku = trim(sanitize($_POST['sku'] ?? ''));
        if (empty($sku)) {
            $sku = 'JDM-' . strtoupper(substr(uniqid(), -6));
        }

        $name = trim(sanitize($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $brandId = (int)($_POST['brand_id'] ?? 1);
        $catId = (int)($_POST['category_id'] ?? 1);
        $stock = (int)($_POST['stock_quantity'] ?? 1);
        $fitment = trim(sanitize($_POST['model_compatibility'] ?? 'General JDM Fitment'));
        $grade = trim(sanitize($_POST['grade'] ?? 'Grade A'));
        $desc = trim(sanitize($_POST['description'] ?? ''));

        // Handle Uploaded Files
        $uploadedPhotos = [];
        if (isset($_FILES['product_photos']) && is_array($_FILES['product_photos']['name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $fileCount = count($_FILES['product_photos']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                $error = $_FILES['product_photos']['error'][$i];
                if ($error === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['product_photos']['tmp_name'][$i];
                    $origName = $_FILES['product_photos']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowedExts)) {
                        $newFilename = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                        $destPath = $uploadDir . $newFilename;

                        if (move_uploaded_file($tmpName, $destPath)) {
                            $uploadedPhotos[] = $newFilename;
                        }
                    }
                }
            }
        }

        // Optional preset/fallback image if no files uploaded
        if (empty($uploadedPhotos)) {
            $preset = trim(sanitize($_POST['preset_image'] ?? 'engine_1.svg'));
            $uploadedPhotos[] = !empty($preset) ? $preset : 'engine_1.svg';
        }

        $primaryImage = $uploadedPhotos[0];

        try {
            // Check for duplicate SKU
            $chk = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
            $chk->execute([$sku]);
            if ($chk->fetch()) {
                $sku .= '-' . rand(10, 99);
            }

            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products 
                (name, sku, category_id, brand_id, model_compatibility, grade, price, stock_quantity, image_url, description, is_featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $sku, $catId, $brandId, $fitment, $grade, $price, $stock, $primaryImage, $desc]);
            $productId = $pdo->lastInsertId();

            // Insert all photos into product_images table
            $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
            foreach ($uploadedPhotos as $idx => $photo) {
                $isPrim = ($idx === 0) ? 1 : 0;
                $imgStmt->execute([$productId, $photo, $isPrim]);
            }

            $photoCount = count($uploadedPhotos);
            setFlash('success', "✓ Product \"{$name}\" (SKU: {$sku}) added successfully with {$photoCount} photo(s)!");
        } catch (Exception $e) {
            setFlash('error', "Database Error: " . $e->getMessage());
        }

        header('Location: products.php');
        exit;
    }

    // 2. UPDATE PRODUCT DETAILS & ADD MORE PHOTOS
    if ($action === 'update' && $pdo) {
        $id = (int)$_POST['id'];
        $sku = trim(sanitize($_POST['sku'] ?? ''));
        $name = trim(sanitize($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $brandId = (int)($_POST['brand_id'] ?? 1);
        $catId = (int)($_POST['category_id'] ?? 1);
        $stock = (int)($_POST['stock_quantity'] ?? 1);
        $fitment = trim(sanitize($_POST['model_compatibility'] ?? ''));
        $grade = trim(sanitize($_POST['grade'] ?? 'Grade A'));
        $desc = trim(sanitize($_POST['description'] ?? ''));

        // Handle newly uploaded additional photos
        $uploadedPhotos = [];
        if (isset($_FILES['additional_photos']) && is_array($_FILES['additional_photos']['name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $fileCount = count($_FILES['additional_photos']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['additional_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['additional_photos']['tmp_name'][$i];
                    $origName = $_FILES['additional_photos']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowedExts)) {
                        $newFilename = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmpName, $uploadDir . $newFilename)) {
                            $uploadedPhotos[] = $newFilename;
                        }
                    }
                }
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, category_id = ?, brand_id = ?, model_compatibility = ?, grade = ?, price = ?, stock_quantity = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $sku, $catId, $brandId, $fitment, $grade, $price, $stock, $desc, $id]);

            // Save additional photos if uploaded
            if (!empty($uploadedPhotos)) {
                $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 0)");
                foreach ($uploadedPhotos as $photo) {
                    $imgStmt->execute([$id, $photo]);
                }
            }

            setFlash('success', "✓ Product #{$id} ({$name}) updated successfully!");
        } catch (Exception $e) {
            setFlash('error', "Update failed: " . $e->getMessage());
        }

        header("Location: products.php?edit={$id}");
        exit;
    }

    // 3. SET PHOTO AS PRIMARY / COVER
    if ($action === 'set_primary_photo' && $pdo) {
        $photoId = (int)$_POST['photo_id'];
        $prodId = (int)$_POST['product_id'];

        if (setPrimaryProductPhoto($photoId, $prodId)) {
            setFlash('success', "✓ Cover photo updated successfully!");
        } else {
            setFlash('error', "Failed to update cover photo.");
        }

        header("Location: products.php?edit={$prodId}");
        exit;
    }

    // 4. DELETE INDIVIDUAL PHOTO
    if ($action === 'delete_photo' && $pdo) {
        $photoId = (int)$_POST['photo_id'];
        $prodId = (int)$_POST['product_id'];

        if (deleteProductPhoto($photoId, $prodId)) {
            setFlash('info', "Photo removed successfully.");
        } else {
            setFlash('error', "Failed to remove photo.");
        }

        header("Location: products.php?edit={$prodId}");
        exit;
    }

    // 5. DELETE PRODUCT ENTIRELY
    if ($action === 'delete' && $pdo) {
        $id = (int)$_POST['id'];
        $product = fetchProductById($id);
        $prodName = $product ? $product['name'] : "#{$id}";

        if (deleteProductById($id)) {
            setFlash('info', "✓ Product \"{$prodName}\" and all associated photos permanently deleted.");
        } else {
            setFlash('error', "Failed to delete product #{$id}.");
        }

        header('Location: products.php');
        exit;
    }
}

// Fetch single product for edit modal if requested
$editProduct = null;
$editPhotos = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editProduct = fetchProductById($editId);
    if ($editProduct) {
        $editPhotos = fetchProductPhotosMeta($editId);
    }
}

$products = fetchAllProducts();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Parts &amp; Multi-Photo Inventory | JK Tech Motors Admin</title>
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
      border-left: 3px solid var(--neon-cyan);
    }
    .admin-main {
      padding: 32px 40px;
      background: var(--bg-main);
      overflow-y: auto;
    }
    .db-status-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: var(--radius-pill);
      font-size: 11px;
      font-weight: 700;
      font-family: var(--font-mono);
      background: rgba(6, 182, 212, 0.1);
      border: 1px solid rgba(6, 182, 212, 0.3);
      color: var(--neon-cyan);
    }
    .db-status-bar.sqlite {
      background: rgba(234, 179, 8, 0.1);
      border-color: rgba(234, 179, 8, 0.3);
      color: #eab308;
    }
    .modal-backdrop {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.85);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
      backdrop-filter: blur(5px);
    }
    .modal-backdrop.active {
      display: flex;
    }
    .modal-box {
      background: #0d1424;
      border: 1px solid rgba(0, 229, 255, 0.3);
      border-radius: var(--radius-lg);
      max-width: 720px;
      width: 100%;
      max-height: 92vh;
      overflow-y: auto;
      padding: 30px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 35px rgba(0, 229, 255, 0.15);
    }

    /* Multi-File Upload Styling */
    .dropzone-box {
      border: 2px dashed rgba(0, 229, 255, 0.35);
      background: rgba(0, 229, 255, 0.04);
      border-radius: var(--radius-md);
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s ease;
      position: relative;
    }
    .dropzone-box:hover, .dropzone-box.dragover {
      border-color: var(--neon-cyan);
      background: rgba(0, 229, 255, 0.09);
    }
    .dropzone-box input[type="file"] {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 12px;
      margin-top: 16px;
    }
    .preview-thumb {
      position: relative;
      height: 85px;
      border-radius: var(--radius-sm);
      overflow: hidden;
      border: 2px solid rgba(255,255,255,0.12);
      background: #040812;
    }
    .preview-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .preview-thumb .thumb-tag {
      position: absolute;
      bottom: 2px;
      left: 2px;
      background: rgba(0,0,0,0.85);
      font-size: 8.5px;
      font-weight: 800;
      color: var(--neon-cyan);
      padding: 2px 5px;
      border-radius: 2px;
    }
    .preview-thumb.is-main {
      border-color: #00e5ff;
    }
    .preview-thumb.is-main .thumb-tag {
      background: #00e5ff;
      color: #000;
    }

    /* Photos Management Grid in Edit Modal */
    .photo-manage-card {
      position: relative;
      background: #111a2e;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .photo-manage-card.is-cover {
      border-color: #00e5ff;
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.25);
    }
    .photo-manage-img {
      height: 90px;
      width: 100%;
      object-fit: cover;
      background: #060a14;
    }
    .photo-manage-bar {
      padding: 6px 8px;
      background: #0a0f1d;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 4px;
    }

    /* Product Table Styling */
    .product-thumb-container {
      position: relative;
      width: 58px;
      height: 46px;
      background: #020617;
      border-radius: var(--radius-sm);
      overflow: hidden;
      border: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .product-thumb-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .badge-photo-count {
      position: absolute;
      bottom: 1px;
      right: 1px;
      background: rgba(0, 0, 0, 0.85);
      color: #00F0FF;
      font-size: 8.5px;
      font-weight: 800;
      padding: 1px 4px;
      border-radius: 2px;
      font-family: var(--font-mono);
    }
    .sku-badge {
      display: inline-block;
      background: rgba(6, 182, 212, 0.12);
      border: 1px solid rgba(6, 182, 212, 0.3);
      padding: 2px 7px;
      border-radius: 3px;
      color: var(--neon-cyan);
      font-size: 11px;
      font-family: var(--font-mono);
      font-weight: 700;
    }
  </style>
</head>
<body>

  <div class="admin-container">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
      <div style="padding: 10px 6px 14px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 14px;">
        <a href="index.php" class="brand-badge" style="display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 10px;" title="JKtech.LK Admin Dashboard">
          <img src="../assets/images/logo_circle.svg" alt="JKtech.LK" class="brand-emblem-circle" style="width: 38px; height: 38px;">
          <span class="brand-text" style="font-size: 20px;">JKtech<span class="brand-domain">.LK</span></span>
        </a>
        <div style="font-size: 10.5px; color: var(--neon-cyan); font-weight: 800; letter-spacing: 1.2px; font-family:var(--font-display); padding-left: 2px;">MANAGEMENT CONSOLE</div>
      </div>

      <ul class="admin-menu">
        <li><a href="index.php">📊 Dashboard</a></li>
        <li><a href="products.php" class="active">🔧 Products &amp; Photos</a></li>
        <li><a href="orders.php">📦 Orders &amp; Dispatch</a></li>
        <li><a href="inventory.php">⚙️ Stock Watch</a></li>
        <li><a href="customers.php">👥 Garages &amp; Clients</a></li>
      </ul>

      <div style="padding: 16px 8px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px;">
        <a href="../index.php" class="btn btn-outline btn-sm" style="width: 100%;">🌐 View Public Website</a>
        <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; color:#ef4444;">Sign Out</a>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
      <!-- Header Row -->
      <div class="flex justify-between items-center" style="margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
          <h1 style="font-size: 26px; font-weight: 900; color: #fff; margin: 0;">PARTS &amp; MULTI-PHOTO INVENTORY</h1>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
            Add parts with multiple angle inspection photos, change cover pictures, and manage live prices
          </p>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Database Live Health Indicator -->
          <div class="db-status-bar <?= $dbStatus['driver'] === 'sqlite' ? 'sqlite' : '' ?>" title="<?= htmlspecialchars($dbStatus['detail']) ?>">
            <span><?= htmlspecialchars($dbStatus['badge']) ?></span>
          </div>

          <!-- Add Product Button (Opens Modal) -->
          <button type="button" class="btn btn-primary" onclick="openAddModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 4px;">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <span>+ Add Part With Multiple Photos</span>
          </button>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Products Directory Table -->
      <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
          <thead>
            <tr style="background: #0c121e; border-bottom: 1px solid var(--border-color); color: var(--text-dim); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
              <th style="padding: 14px 18px;">Photo</th>
              <th style="padding: 14px 18px;">Product ID (SKU)</th>
              <th style="padding: 14px 18px;">Product Name &amp; Fitment</th>
              <th style="padding: 14px 18px;">Brand &amp; Category</th>
              <th style="padding: 14px 18px;">Price (LKR)</th>
              <th style="padding: 14px 18px;">Stock</th>
              <th style="padding: 14px 18px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($products)): ?>
              <tr>
                <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                  No products found in the database. Click "+ Add Part With Multiple Photos" above to add your first part!
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($products as $p): ?>
                <?php 
                  $pPhotos = fetchProductImages($p['id']);
                  $photoCount = count($pPhotos);
                ?>
                <tr style="border-bottom: 1px solid var(--border-color); vertical-align: middle;">
                  <!-- Thumbnail + Photo Count Badge -->
                  <td style="padding: 12px 18px;">
                    <div class="product-thumb-container">
                      <img src="../<?= htmlspecialchars(getProductImageUrl($p['image_url'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                      <?php if ($photoCount > 1): ?>
                        <span class="badge-photo-count" title="<?= $photoCount ?> photos uploaded">📷 <?= $photoCount ?></span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- SKU / Product ID -->
                  <td style="padding: 12px 18px;">
                    <span class="sku-badge"><?= htmlspecialchars($p['sku']) ?></span>
                    <div style="font-size: 10px; color: var(--text-dim); margin-top: 3px;"><?= htmlspecialchars($p['grade'] ?? 'Grade A') ?></div>
                  </td>

                  <!-- Product Title & Fitment -->
                  <td style="padding: 12px 18px;">
                    <strong style="color: #fff; display: block; font-size: 14px;"><?= htmlspecialchars($p['name']) ?></strong>
                    <span style="font-size: 11.5px; color: var(--text-muted); display: block;">
                      🚗 Fits: <?= htmlspecialchars($p['model_compatibility'] ?? 'General JDM') ?>
                    </span>
                  </td>

                  <!-- Brand & Category -->
                  <td style="padding: 12px 18px; color: var(--text-muted);">
                    <strong style="color: #f1f5f9;"><?= htmlspecialchars($p['brand_name'] ?? 'JDM') ?></strong>
                    <div style="font-size: 11px; color: var(--text-dim);"><?= htmlspecialchars($p['category_name'] ?? 'Assemblies') ?></div>
                  </td>

                  <!-- Price -->
                  <td style="padding: 12px 18px; color: #fff; font-weight: 800; font-family: var(--font-display); font-size: 14px;">
                    <?= formatCurrency($p['price']) ?>
                  </td>

                  <!-- Stock Quantity -->
                  <td style="padding: 12px 18px;">
                    <?php if ($p['stock_quantity'] <= 0): ?>
                      <span style="color: #ef4444; font-weight: 800; font-size: 11px; background: rgba(239, 68, 68, 0.1); padding: 3px 8px; border-radius: 3px;">Out of Stock</span>
                    <?php elseif ($p['stock_quantity'] <= 2): ?>
                      <span style="color: #f87171; font-weight: 800; font-size: 11px; background: rgba(248, 113, 113, 0.1); padding: 3px 8px; border-radius: 3px;"><?= $p['stock_quantity'] ?> left (Low)</span>
                    <?php else: ?>
                      <span style="color: #10b981; font-weight: 700; font-size: 11px; background: rgba(16, 185, 129, 0.1); padding: 3px 8px; border-radius: 3px;"><?= $p['stock_quantity'] ?> in stock</span>
                    <?php endif; ?>
                  </td>

                  <!-- Actions (View, Edit, Delete) -->
                  <td style="padding: 12px 18px; text-align: right;">
                    <div class="flex gap-2" style="justify-content: flex-end;">
                      <!-- Public Preview Link -->
                      <a href="../product-details.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline btn-sm" title="View part in public store" style="font-size: 11px; padding: 5px 9px;">
                        👁️ View
                      </a>

                      <!-- Edit Button -->
                      <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 5px 10px;">
                        ✏️ Edit &amp; Photos
                      </a>

                      <!-- Delete Button with Immediate Confirmation -->
                      <form method="POST" action="products.php" style="display:inline;" onsubmit="return confirm('Permanently delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\' and ALL attached photos?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="font-size: 11px; padding: 5px 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                          🗑️
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- ------------------------------------------------------------- -->
  <!-- MODAL 1: ADD NEW PRODUCT WITH MULTIPLE PHOTO UPLOAD          -->
  <!-- ------------------------------------------------------------- -->
  <div id="addProductModal" class="modal-backdrop">
    <div class="modal-box">
      <div class="flex justify-between items-center" style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
        <div>
          <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0;">ADD NEW PART &amp; MULTIPLE PHOTOS</h3>
          <p style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">Select multiple inspection photos from your computer at once</p>
        </div>
        <button type="button" onclick="closeAddModal()" class="btn btn-outline btn-sm" style="padding: 4px 10px;">✕</button>
      </div>

      <form method="POST" action="products.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <!-- Row 1: Product ID & Product Name -->
        <div class="grid" style="grid-template-columns: 0.8fr 1.2fr; gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin-bottom: 0;">
            <div class="flex justify-between items-center" style="margin-bottom: 6px;">
              <label class="form-label" style="margin: 0;">Product ID / SKU *</label>
              <button type="button" onclick="generateRandomSku()" style="background: none; border: none; color: var(--neon-cyan); font-size: 11px; cursor: pointer; text-decoration: underline;">Auto-Gen</button>
            </div>
            <input type="text" id="addSkuInput" name="sku" class="form-control" placeholder="e.g. ENG-1NZ-01" required>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Part Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Honda K20A Type-R Red Top Engine" required>
          </div>
        </div>

        <!-- Row 2: Price & Stock Quantity -->
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Price in LKR (Rs.) *</label>
            <input type="number" name="price" class="form-control" placeholder="185000" min="0" step="0.01" required>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Stock Quantity *</label>
            <input type="number" name="stock_quantity" class="form-control" value="1" min="1" required>
          </div>
        </div>

        <!-- Row 3: Brand & Category -->
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Brand / Manufacturer *</label>
            <select name="brand_id" class="form-control">
              <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Component Category</label>
            <select name="category_id" class="form-control">
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Row 4: Vehicle Compatibility & Condition Grade -->
        <div class="grid" style="grid-template-columns: 1.2fr 0.8fr; gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Vehicle Fitment / Compatibility</label>
            <input type="text" name="model_compatibility" class="form-control" placeholder="e.g. Corolla NZE141, Allion NZT260, Premio">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Condition Grade</label>
            <select name="grade" class="form-control">
              <option value="Grade A+">Grade A+ (Pristine Tested)</option>
              <option value="Grade A" selected>Grade A (Inspected)</option>
              <option value="Grade B">Grade B (Good Working)</option>
            </select>
          </div>
        </div>

        <!-- Row 5: Multi-Image File Upload -->
        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label">
            Part Photos (Select multiple files: Front, Rear, Tags, Internals)
          </label>
          
          <div class="dropzone-box" id="dropzoneBox">
            <input type="file" name="product_photos[]" id="productPhotosInput" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml">
            <div style="font-size: 32px; margin-bottom: 6px;">📷</div>
            <strong style="color: #fff; display: block; font-size: 14px;">Click to Select Multiple Photos (or Drag &amp; Drop Here)</strong>
            <span style="font-size: 12px; color: var(--text-muted);">Supports JPG, PNG, WEBP, SVG. You can pick multiple files at once!</span>
          </div>

          <!-- Live Preview Grid of Selected Photos -->
          <div id="imagePreviewGrid" class="preview-grid"></div>
        </div>

        <!-- Row 6: Description -->
        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label">Description / Inspection Notes</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Compression tested, cold-start verified, included manifolds, sensors..."></textarea>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-between items-center" style="border-top: 1px solid var(--border-color); padding-top: 16px;">
          <button type="button" onclick="closeAddModal()" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary btn-lg">
            <span>✓ Save Part &amp; Upload Photos</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ------------------------------------------------------------- -->
  <!-- MODAL 2: EDIT PRODUCT & MANAGE ITS GALLERY PHOTOS             -->
  <!-- ------------------------------------------------------------- -->
  <?php if ($editProduct): ?>
    <div id="editProductModal" class="modal-backdrop active">
      <div class="modal-box">
        <div class="flex justify-between items-center" style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
          <div>
            <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0;">EDIT PART &amp; GALLERY PHOTOS</h3>
            <span style="font-size: 12px; color: var(--neon-cyan);">SKU: <?= htmlspecialchars($editProduct['sku']) ?> &bull; #<?= $editProduct['id'] ?></span>
          </div>
          <a href="products.php" class="btn btn-outline btn-sm" style="padding: 4px 10px;">✕</a>
        </div>

        <!-- Section 1: Manage Existing Gallery Photos -->
        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 18px; margin-bottom: 22px;">
          <div class="flex justify-between items-center" style="margin-bottom: 12px;">
            <strong style="color: #fff; font-size: 13.5px;">Attached Photos (<?= count($editPhotos) ?>)</strong>
            <span style="font-size: 11.5px; color: var(--text-muted);">Set cover photo or remove angle shots</span>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px;">
            <?php foreach ($editPhotos as $pIdx => $ep): ?>
              <?php $isCover = !empty($ep['is_primary']); ?>
              <div class="photo-manage-card <?= $isCover ? 'is-cover' : '' ?>">
                <img src="../<?= htmlspecialchars(getProductImageUrl($ep['image_path'])) ?>" class="photo-manage-img" alt="Photo <?= $pIdx + 1 ?>">
                <div class="photo-manage-bar">
                  <?php if ($isCover): ?>
                    <span style="color: #00e5ff; font-size: 10px; font-weight: 800; font-family: var(--font-mono);">⭐ COVER</span>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm" style="font-size: 10px; padding: 2px 6px; background: rgba(0, 229, 255, 0.15); color: #00e5ff; border: 1px solid rgba(0, 229, 255, 0.3);" onclick="setCoverPhoto(<?= $ep['id'] ?>, <?= $editProduct['id'] ?>)">
                      Make Cover
                    </button>
                  <?php endif; ?>

                  <?php if (count($editPhotos) > 1): ?>
                    <button type="button" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; border-radius: 4px; padding: 2px 6px; font-size: 10px; cursor: pointer;" onclick="deleteSinglePhoto(<?= $ep['id'] ?>, <?= $editProduct['id'] ?>)">
                      🗑️
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Section 2: Edit Details & Upload Additional Photos Form -->
        <form method="POST" action="products.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">

          <div class="grid" style="grid-template-columns: 0.8fr 1.2fr; gap: 14px; margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">SKU / ID *</label>
              <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($editProduct['sku']) ?>" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Part Name *</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Price in LKR (Rs.) *</label>
              <input type="number" name="price" class="form-control" value="<?= (float)$editProduct['price'] ?>" step="0.01" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Stock Quantity *</label>
              <input type="number" name="stock_quantity" class="form-control" value="<?= (int)$editProduct['stock_quantity'] ?>" min="0" required>
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Brand</label>
              <select name="brand_id" class="form-control">
                <?php foreach ($brands as $b): ?>
                  <option value="<?= $b['id'] ?>" <?= $editProduct['brand_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-control">
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $editProduct['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="grid" style="grid-template-columns: 1.2fr 0.8fr; gap: 14px; margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Vehicle Fitment</label>
              <input type="text" name="model_compatibility" class="form-control" value="<?= htmlspecialchars($editProduct['model_compatibility'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Grade</label>
              <select name="grade" class="form-control">
                <option value="Grade A+" <?= ($editProduct['grade'] ?? '') === 'Grade A+' ? 'selected' : '' ?>>Grade A+ (Pristine)</option>
                <option value="Grade A" <?= ($editProduct['grade'] ?? '') === 'Grade A' ? 'selected' : '' ?>>Grade A (Inspected)</option>
                <option value="Grade B" <?= ($editProduct['grade'] ?? '') === 'Grade B' ? 'selected' : '' ?>>Grade B (Working)</option>
              </select>
            </div>
          </div>

          <!-- Upload More Photos -->
          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Upload Additional Photos to this Part</label>
            <input type="file" name="additional_photos[]" id="editAddPhotosInput" class="form-control" multiple accept="image/*">
            <div id="editPreviewGrid" class="preview-grid"></div>
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Description / Specs</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
          </div>

          <div class="flex justify-between items-center" style="border-top: 1px solid var(--border-color); padding-top: 16px;">
            <a href="products.php" class="btn btn-secondary">Close</a>
            <button type="submit" class="btn btn-primary">
              <span>✓ Save Product Updates</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Standalone Photo Action Form (Avoids any nested form issues) -->
  <form id="standalonePhotoForm" method="POST" action="products.php" style="display: none;">
    <input type="hidden" name="action" id="standalonePhotoAction" value="">
    <input type="hidden" name="photo_id" id="standalonePhotoId" value="">
    <input type="hidden" name="product_id" id="standaloneProductId" value="">
  </form>

  <script>
    function openAddModal() {
      document.getElementById('addProductModal').classList.add('active');
    }
    function closeAddModal() {
      document.getElementById('addProductModal').classList.remove('active');
    }

    function generateRandomSku() {
      const prefixes = ['ENG', 'TRN', 'SUS', 'BRK', 'PRD', 'JDM'];
      const randPrefix = prefixes[Math.floor(Math.random() * prefixes.length)];
      const randNum = Math.floor(1000 + Math.random() * 9000);
      document.getElementById('addSkuInput').value = `${randPrefix}-${randNum}`;
    }

    // Photo Action Triggers
    function setCoverPhoto(photoId, prodId) {
      document.getElementById('standalonePhotoAction').value = 'set_primary_photo';
      document.getElementById('standalonePhotoId').value = photoId;
      document.getElementById('standaloneProductId').value = prodId;
      document.getElementById('standalonePhotoForm').submit();
    }

    function deleteSinglePhoto(photoId, prodId) {
      if (confirm('Permanently remove this photo?')) {
        document.getElementById('standalonePhotoAction').value = 'delete_photo';
        document.getElementById('standalonePhotoId').value = photoId;
        document.getElementById('standaloneProductId').value = prodId;
        document.getElementById('standalonePhotoForm').submit();
      }
    }

    // Live Multi-Image Selection Preview Handler for Add Modal
    const photosInput = document.getElementById('productPhotosInput');
    const previewGrid = document.getElementById('imagePreviewGrid');

    if (photosInput && previewGrid) {
      photosInput.addEventListener('change', function(e) {
        previewGrid.innerHTML = '';
        const files = Array.from(e.target.files);

        if (files.length === 0) return;

        files.forEach((file, index) => {
          if (!file.type.startsWith('image/')) return;

          const reader = new FileReader();
          reader.onload = function(evt) {
            const thumb = document.createElement('div');
            thumb.className = `preview-thumb ${index === 0 ? 'is-main' : ''}`;
            
            const img = document.createElement('img');
            img.src = evt.target.result;
            thumb.appendChild(img);

            const tag = document.createElement('span');
            tag.className = 'thumb-tag';
            tag.textContent = index === 0 ? 'Cover' : `Photo #${index + 1}`;
            thumb.appendChild(tag);

            previewGrid.appendChild(thumb);
          };
          reader.readAsDataURL(file);
        });
      });
    }

    // Live Preview for Edit Modal Additional Photos
    const editAddPhotosInput = document.getElementById('editAddPhotosInput');
    const editPreviewGrid = document.getElementById('editPreviewGrid');
    if (editAddPhotosInput && editPreviewGrid) {
      editAddPhotosInput.addEventListener('change', function(e) {
        editPreviewGrid.innerHTML = '';
        const files = Array.from(e.target.files);
        files.forEach((file, index) => {
          if (!file.type.startsWith('image/')) return;
          const reader = new FileReader();
          reader.onload = function(evt) {
            const thumb = document.createElement('div');
            thumb.className = 'preview-thumb';
            const img = document.createElement('img');
            img.src = evt.target.result;
            thumb.appendChild(img);
            const tag = document.createElement('span');
            tag.className = 'thumb-tag';
            tag.textContent = `New #${index + 1}`;
            thumb.appendChild(tag);
            editPreviewGrid.appendChild(thumb);
          };
          reader.readAsDataURL(file);
        });
      });
    }

    // Drag and Drop styling
    const dropzone = document.getElementById('dropzoneBox');
    if (dropzone) {
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => { e.preventDefault(); dropzone.classList.add('dragover'); }, false);
      });
      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => { e.preventDefault(); dropzone.classList.remove('dragover'); }, false);
      });
    }

    // Close modal on Escape key
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeAddModal();
        const editModal = document.getElementById('editProductModal');
        if (editModal) editModal.classList.remove('active');
      }
    });
  </script>
</body>
</html>
