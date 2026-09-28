<?php
// admin/products.php - Manage Inventory & Multi-Image Parts CRUD
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$pdo = getDbConnection();
$dbStatus = getDbStatus();

$categories = fetchCategories();
$brands = fetchBrands();

// Handle Form Submissions (Create, Update, Delete, Delete Photo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------------------------------------------------------------
    // 1. ADD NEW PRODUCT (with Multiple Image Upload)
    // -------------------------------------------------------------
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
        $desc = trim(sanitize($_POST['description'] ?? ''));

        // Handle File Uploads (Multiple Photos)
        $uploadedPhotos = [];
        $uploadDir = __DIR__ . '/../assets/uploads/products/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

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

        // Determine Primary Image (First uploaded photo, or default built-in SVG)
        $primaryImage = !empty($uploadedPhotos) ? $uploadedPhotos[0] : 'engine_1.svg';

        try {
            // Check for duplicate SKU
            $chk = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
            $chk->execute([$sku]);
            if ($chk->fetch()) {
                $sku .= '-' . rand(10, 99);
            }

            // Insert Product into Database
            $stmt = $pdo->prepare("INSERT INTO products 
                (name, sku, category_id, brand_id, model_compatibility, price, stock_quantity, image_url, description, is_featured) 
                VALUES (?, ?, ?, ?, 'General JDM Fitment', ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $sku, $catId, $brandId, $price, $stock, $primaryImage, $desc]);
            $productId = $pdo->lastInsertId();

            // Insert all photos into product_images table
            if (!empty($uploadedPhotos)) {
                $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                foreach ($uploadedPhotos as $idx => $photo) {
                    $isPrim = ($idx === 0) ? 1 : 0;
                    $imgStmt->execute([$productId, $photo, $isPrim]);
                }
            }

            $photoMsg = count($uploadedPhotos) > 0 ? " with " . count($uploadedPhotos) . " photo(s)" : "";
            setFlash('success', "✓ Product \"{$name}\" (ID: {$sku}) added to database successfully{$photoMsg}!");
        } catch (Exception $e) {
            setFlash('error', "Database Error: " . $e->getMessage());
        }

        header('Location: products.php');
        exit;
    }

    // -------------------------------------------------------------
    // 2. UPDATE EXISTING PRODUCT (with Option to Add More Photos)
    // -------------------------------------------------------------
    if ($action === 'update' && $pdo) {
        $id = (int)$_POST['id'];
        $sku = trim(sanitize($_POST['sku'] ?? ''));
        $name = trim(sanitize($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $brandId = (int)($_POST['brand_id'] ?? 1);
        $catId = (int)($_POST['category_id'] ?? 1);
        $stock = (int)($_POST['stock_quantity'] ?? 1);
        $desc = trim(sanitize($_POST['description'] ?? ''));

        // Handle additional file uploads
        $uploadedPhotos = [];
        $uploadDir = __DIR__ . '/../assets/uploads/products/';
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
            $stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, category_id = ?, brand_id = ?, price = ?, stock_quantity = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $sku, $catId, $brandId, $price, $stock, $desc, $id]);

            // Save additional photos if any
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

        header('Location: products.php');
        exit;
    }

    // -------------------------------------------------------------
    // 3. DELETE PRODUCT & ALL ATTACHED IMAGES
    // -------------------------------------------------------------
    if ($action === 'delete' && $pdo) {
        $id = (int)$_POST['id'];
        $product = fetchProductById($id);
        $prodName = $product ? $product['name'] : "#{$id}";

        if (deleteProductById($id)) {
            setFlash('info', "✓ Product \"{$prodName}\" and all associated photos permanently deleted from database.");
        } else {
            setFlash('error', "Failed to delete product #{$id}.");
        }

        header('Location: products.php');
        exit;
    }

    // -------------------------------------------------------------
    // 4. DELETE INDIVIDUAL PHOTO FROM A PRODUCT
    // -------------------------------------------------------------
    if ($action === 'delete_photo' && $pdo) {
        $photoId = (int)$_POST['photo_id'];
        $prodId = (int)$_POST['product_id'];

        try {
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->execute([$photoId]);
            $path = $stmt->fetchColumn();

            if ($path) {
                $fullPath = __DIR__ . '/../assets/uploads/products/' . basename($path);
                if (file_exists($fullPath) && is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$photoId]);
                setFlash('info', "Photo removed successfully.");
            }
        } catch (Exception $e) {
            setFlash('error', "Could not remove photo: " . $e->getMessage());
        }

        header("Location: products.php?edit={$prodId}");
        exit;
    }
}

// Fetch single product for edit modal if requested
$editProduct = null;
$editPhotos = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editProduct = fetchProductById($editId);
    if ($editProduct && $pdo) {
        try {
            $pStmt = $pdo->prepare("SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY id ASC");
            $pStmt->execute([$editId]);
            $editPhotos = $pStmt->fetchAll();
        } catch (Exception $e) {}
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
      background: rgba(0,0,0,0.8);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.active {
      display: flex;
    }
    .modal-box {
      background: #0d1424;
      border: 1px solid rgba(6, 182, 212, 0.3);
      border-radius: var(--radius-lg);
      max-width: 680px;
      width: 100%;
      max-height: 92vh;
      overflow-y: auto;
      padding: 30px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 30px rgba(6, 182, 212, 0.15);
    }

    /* Multi-File Upload Styling */
    .dropzone-box {
      border: 2px dashed rgba(6, 182, 212, 0.35);
      background: rgba(6, 182, 212, 0.04);
      border-radius: var(--radius-md);
      padding: 26px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s ease;
      position: relative;
    }
    .dropzone-box:hover, .dropzone-box.dragover {
      border-color: var(--neon-cyan);
      background: rgba(6, 182, 212, 0.09);
    }
    .dropzone-box input[type="file"] {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
      gap: 12px;
      margin-top: 16px;
    }
    .preview-thumb {
      position: relative;
      height: 75px;
      border-radius: var(--radius-sm);
      overflow: hidden;
      border: 2px solid rgba(255,255,255,0.1);
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
      background: rgba(0,0,0,0.8);
      font-size: 8px;
      font-weight: 700;
      color: var(--neon-cyan);
      padding: 1px 4px;
      border-radius: 2px;
    }
    .preview-thumb.is-main {
      border-color: var(--primary);
    }
    .preview-thumb.is-main .thumb-tag {
      background: var(--primary);
      color: #fff;
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
      font-size: 8px;
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
            Easily add, delete, upload multiple photos, and manage prices in the live database
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
            <span>+ Add New Product</span>
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
              <th style="padding: 14px 18px;">Product Name</th>
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
                  No products found in the database. Click "+ Add New Product" above to add your first part with photos!
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
                        <span class="badge-photo-count">📷 <?= $photoCount ?></span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- SKU / Product ID -->
                  <td style="padding: 12px 18px;">
                    <span class="sku-badge"><?= htmlspecialchars($p['sku']) ?></span>
                  </td>

                  <!-- Product Title -->
                  <td style="padding: 12px 18px;">
                    <strong style="color: #fff; display: block; font-size: 14px;"><?= htmlspecialchars($p['name']) ?></strong>
                    <?php if (!empty($p['description'])): ?>
                      <span style="font-size: 11px; color: var(--text-muted); display: block; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($p['description']) ?>
                      </span>
                    <?php endif; ?>
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
                      <a href="../product-details.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline btn-sm" title="View in store" style="font-size: 11px; padding: 5px 9px;">
                        👁️
                      </a>

                      <!-- Edit Button -->
                      <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 5px 10px;">
                        ✏️ Edit
                      </a>

                      <!-- Delete Button with Immediate Confirmation -->
                      <form method="POST" action="products.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\' and its photos from the database?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="font-size: 11px; padding: 5px 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                          🗑️ Delete
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
  <!-- MODAL 1: ADD NEW PRODUCT (SIMPLIFIED & MULTI-PHOTO UPLOAD)   -->
  <!-- ------------------------------------------------------------- -->
  <div id="addProductModal" class="modal-backdrop">
    <div class="modal-box">
      <div class="flex justify-between items-center" style="margin-bottom: 22px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
        <div>
          <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0;">ADD NEW PRODUCT / SPARE PART</h3>
          <p style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">Enter essential details and upload multiple photos directly from your device</p>
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
            <input type="text" id="addSkuInput" name="sku" class="form-control" placeholder="e.g. PRD-101 or ENG-1NZ" required>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Toyota 1NZ-FE VVT-i Engine Assembly" required>
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
            <label class="form-label">Category Group</label>
            <select name="category_id" class="form-control">
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Row 4: Multiple Image Upload -->
        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label">Product Photos (Upload multiple images from your PC)</label>
          <div class="dropzone-box" id="dropzoneBox">
            <input type="file" name="product_photos[]" id="productPhotosInput" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml">
            <div style="font-size: 28px; margin-bottom: 6px;">📷</div>
            <strong style="color: #fff; display: block; font-size: 13px;">Click or Drag &amp; Drop Photos Here</strong>
            <span style="font-size: 12px; color: var(--text-muted);">You can select multiple photos at once (JPG, PNG, WEBP)</span>
          </div>
          <!-- Live Preview Grid -->
          <div id="imagePreviewGrid" class="preview-grid"></div>
        </div>

        <!-- Row 5: Description -->
        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label">Description / Condition Notes</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Enter details like vehicle fitment, tested compression, included accessories, or warranty terms..."></textarea>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-between items-center" style="border-top: 1px solid var(--border-color); padding-top: 16px;">
          <button type="button" onclick="closeAddModal()" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary btn-lg">
            <span>✓ Save Product to Database</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ------------------------------------------------------------- -->
  <!-- MODAL 2: EDIT PRODUCT & MANAGE ITS PHOTOS                     -->
  <!-- ------------------------------------------------------------- -->
  <?php if ($editProduct): ?>
    <div id="editProductModal" class="modal-backdrop active">
      <div class="modal-box">
        <div class="flex justify-between items-center" style="margin-bottom: 22px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
          <div>
            <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0;">EDIT PRODUCT: <?= htmlspecialchars($editProduct['name']) ?></h3>
            <span style="font-size: 12px; color: var(--neon-cyan);">SKU: <?= htmlspecialchars($editProduct['sku']) ?></span>
          </div>
          <a href="products.php" class="btn btn-outline btn-sm" style="padding: 4px 10px;">✕</a>
        </div>

        <form method="POST" action="products.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">

          <div class="grid" style="grid-template-columns: 0.8fr 1.2fr; gap: 14px; margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Product ID / SKU *</label>
              <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($editProduct['sku']) ?>" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Product Name *</label>
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

          <!-- Existing Photos List -->
          <?php if (!empty($editPhotos)): ?>
            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label">Current Gallery Photos</label>
              <div class="preview-grid" style="margin-top: 6px;">
                <?php foreach ($editPhotos as $pIndex => $ep): ?>
                  <div class="preview-thumb <?= $pIndex === 0 ? 'is-main' : '' ?>" style="display: flex; flex-direction: column;">
                    <img src="../<?= htmlspecialchars(getProductImageUrl($ep['image_path'])) ?>">
                    <span class="thumb-tag"><?= $pIndex === 0 ? 'Cover' : 'Photo #' . ($pIndex + 1) ?></span>
                    <?php if (count($editPhotos) > 1): ?>
                      <form method="POST" action="products.php" onsubmit="return confirm('Remove this photo?');" style="position: absolute; top: 2px; right: 2px; z-index: 10;">
                        <input type="hidden" name="action" value="delete_photo">
                        <input type="hidden" name="photo_id" value="<?= $ep['id'] ?>">
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                        <button type="submit" style="background: rgba(239, 68, 68, 0.9); border: none; color: #fff; width: 18px; height: 18px; border-radius: 50%; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">✕</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Upload Additional Photos -->
          <div class="form-group" style="margin-bottom: 14px;">
            <label class="form-label">Add More Photos (Optional)</label>
            <input type="file" name="additional_photos[]" class="form-control" multiple accept="image/*">
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
          </div>

          <div class="flex justify-between items-center" style="border-top: 1px solid var(--border-color); padding-top: 16px;">
            <a href="products.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
              <span>✓ Update Product Changes</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

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

    // Live Multi-Image Selection Preview Handler
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
