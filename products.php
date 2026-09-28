<?php
// products.php - Japanese Reconditioned Auto Parts Directory
require_once __DIR__ . '/includes/functions.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$brandId = isset($_GET['brand_id']) && $_GET['brand_id'] !== '' ? (int)$_GET['brand_id'] : null;
$grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 500000;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

$filters = [
    'search' => $search,
    'category_id' => $categoryId,
    'brand_id' => $brandId,
    'grade' => $grade,
    'max_price' => $maxPrice
];

$products = fetchAllProducts($filters);

if ($sort === 'price_asc') {
    usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
} elseif ($sort === 'price_desc') {
    usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
}

$categories = fetchCategories();
$brands = fetchBrands();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Japanese Reconditioned Auto Parts Directory | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .catalog-layout {
      display: grid;
      grid-template-columns: 290px 1fr;
      gap: 36px;
      padding: 40px 0 90px;
    }
    .filter-sidebar {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      padding: 26px;
      height: fit-content;
      position: sticky;
      top: 98px;
      box-shadow: var(--shadow-sm);
    }
    .filter-title {
      font-family: var(--font-display);
      font-size: 16px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 14px;
      letter-spacing: 0.5px;
    }
    .filter-section {
      margin-bottom: 24px;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 20px;
    }
    .filter-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    .filter-section h4 {
      font-family: var(--font-display);
      font-size: 13px;
      font-weight: 700;
      color: var(--text-main);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 14px;
    }
    .filter-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-height: 220px;
      overflow-y: auto;
    }
    .filter-item label {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      color: var(--text-muted);
      cursor: pointer;
      transition: color 0.2s;
    }
    .filter-item label:hover {
      color: #fff;
    }
    .filter-item input[type="radio"] {
      accent-color: var(--neon-cyan);
      cursor: pointer;
    }
    .catalog-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 28px;
      background: var(--bg-surface);
      padding: 16px 24px;
      border: 1px solid var(--border-card);
      border-radius: var(--radius-sm);
    }
    .catalog-count {
      font-size: 14px;
      color: var(--text-muted);
    }
    .catalog-count strong {
      color: #fff;
    }
    .sort-select {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      cursor: pointer;
    }
    @media (max-width: 992px) {
      .catalog-layout {
        grid-template-columns: 1fr;
      }
      .filter-sidebar {
        position: static;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- Banner -->
  <div style="background: radial-gradient(circle at 10% 50%, rgba(6, 182, 212, 0.12) 0%, transparent 60%), #090e1a; border-bottom: 1px solid var(--border-subtle); padding: 42px 0;">
    <div class="container">
      <span class="badge badge-grade" style="margin-bottom: 10px;">JEVIC TESTED INVENTORY</span>
      <h1 style="font-family: var(--font-display); font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 6px; letter-spacing:-0.5px;">
        JAPANESE RECONDITIONED AUTO PARTS DIRECTORY
      </h1>
      <p style="color: var(--text-muted); font-size: 15px;">Browse low-mileage engines, gearboxes, coilovers &amp; chassis components imported directly from Yokohama.</p>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="container" style="margin-top: 24px;">
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <div class="container">
    <div class="catalog-layout">
      <!-- Left Filter Sidebar -->
      <aside class="filter-sidebar">
        <form id="catalogFilterForm" action="products.php" method="GET">
          <div class="filter-title">
            <span>Filter Inventory</span>
            <button type="button" id="resetFiltersBtn" class="btn-outline btn-sm" style="font-size: 11px; padding: 3px 8px;">Reset All</button>
          </div>

          <!-- Keyword Search -->
          <div class="filter-section">
            <h4>Keyword / Chassis</h4>
            <input type="text" name="search" class="form-control" placeholder="e.g. 1NZ, K20A, CT9A..." value="<?= htmlspecialchars($search) ?>">
          </div>

          <!-- Vehicle Brand -->
          <div class="filter-section">
            <h4>Vehicle Manufacturer</h4>
            <ul class="filter-list">
              <li class="filter-item">
                <label>
                  <input type="radio" name="brand_id" value="" <?= empty($brandId) ? 'checked' : '' ?>>
                  <span>All Makes</span>
                </label>
              </li>
              <?php foreach ($brands as $b): ?>
                <li class="filter-item">
                  <label>
                    <input type="radio" name="brand_id" value="<?= $b['id'] ?>" <?= $brandId == $b['id'] ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($b['name']) ?></span>
                  </label>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Category -->
          <div class="filter-section">
            <h4>Component Group</h4>
            <ul class="filter-list">
              <li class="filter-item">
                <label>
                  <input type="radio" name="category_id" value="" <?= empty($categoryId) ? 'checked' : '' ?>>
                  <span>All Categories</span>
                </label>
              </li>
              <?php foreach ($categories as $cat): ?>
                <li class="filter-item">
                  <label>
                    <input type="radio" name="category_id" value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                  </label>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Quality Grade -->
          <div class="filter-section">
            <h4>Condition Grade</h4>
            <ul class="filter-list">
              <li class="filter-item">
                <label>
                  <input type="radio" name="grade" value="" <?= empty($grade) ? 'checked' : '' ?>>
                  <span>All Grades</span>
                </label>
              </li>
              <li class="filter-item">
                <label>
                  <input type="radio" name="grade" value="Grade A+" <?= $grade === 'Grade A+' ? 'checked' : '' ?>>
                  <span>Grade A+ (Pristine Low-KM)</span>
                </label>
              </li>
              <li class="filter-item">
                <label>
                  <input type="radio" name="grade" value="Grade A" <?= $grade === 'Grade A' ? 'checked' : '' ?>>
                  <span>Grade A (Bench Tested)</span>
                </label>
              </li>
            </ul>
          </div>

          <!-- Price Range Slider -->
          <div class="filter-section">
            <h4>Max Price: <span id="priceRangeValue" style="color:var(--neon-cyan); font-weight:800; font-family:var(--font-display);">Rs. <?= number_format($maxPrice) ?></span></h4>
            <input type="range" id="priceRangeSlider" name="max_price" min="20000" max="600000" step="10000" value="<?= $maxPrice ?>" style="width:100%; accent-color:var(--neon-cyan); cursor:pointer;">
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="margin-top: 14px;">Apply Filters</button>
        </form>
      </aside>

      <!-- Main Directory Content -->
      <main>
        <div class="catalog-topbar">
          <div class="catalog-count">
            Found <strong><?= count($products) ?></strong> authenticated parts in stock
          </div>
          <div class="flex items-center gap-3">
            <span style="font-size: 13px; color: var(--text-muted);">Sort By:</span>
            <select id="sortSelect" class="sort-select">
              <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Latest Arrivals</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
          </div>
        </div>

        <?php if (empty($products)): ?>
          <div style="background: var(--bg-card); border: 1px solid var(--border-card); border-radius: var(--radius-md); padding: 60px 20px; text-align: center;">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" style="margin: 0 auto 18px;">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <h3 style="font-family: var(--font-display); font-size: 20px; color: #fff; margin-bottom: 8px;">No matching spare parts found</h3>
            <p style="color: var(--text-muted); font-size: 14px; max-width: 440px; margin: 0 auto 24px;">
              Try resetting your filters or contact our Yokohama yard team to source this specific chassis code.
            </p>
            <a href="products.php" class="btn btn-primary btn-sm">Clear All Filters</a>
          </div>
        <?php else: ?>
          <div class="products-grid">
            <?php foreach ($products as $item): ?>
              <div class="product-card tilt-card hud-bracket">
                <div class="card-thumb">
                  <img src="<?= htmlspecialchars(getProductImageUrl($item['image_url'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                  <span class="card-badge-top badge badge-grade"><?= htmlspecialchars($item['grade'] ?? 'Grade A') ?></span>
                </div>
                <div class="card-body">
                  <span class="card-category"><?= htmlspecialchars($item['brand_name'] ?? 'JDM Genuine') ?> • <?= htmlspecialchars($item['category_name'] ?? 'Assembly') ?></span>
                  <h3 class="card-title">
                    <a href="product-details.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                  </h3>
                  <div class="card-meta">
                    <p><strong>Fits:</strong> <?= htmlspecialchars($item['model_compatibility']) ?></p>
                    <p style="color: var(--neon-emerald); margin-top: 6px; font-weight: 700; display:flex; align-items:center;">
                      <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981; margin-right:8px; animation:pulseGlow 1.5s infinite;"></span>
                      <span><?= htmlspecialchars($item['compression']) ?></span>
                    </p>
                    <p style="color: var(--text-dim); margin-top: 4px; font-family: var(--font-mono); font-size: 11px;">Verified: <?= htmlspecialchars($item['mileage']) ?></p>
                  </div>
                  <div class="card-footer">
                    <div>
                      <span class="card-price"><?= formatCurrency($item['price']) ?></span>
                      <div style="font-size: 11px; color: var(--neon-emerald); font-weight: 700;">In Stock (<?= $item['stock_quantity'] ?> left)</div>
                    </div>
                    <div class="flex gap-2">
                      <button class="btn btn-primary btn-sm btn-quick-add" data-product-id="<?= $item['id'] ?>">Add to Cart</button>
                      <a href="product-details.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">Details</a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="assets/js/products.js"></script>
</body>
</html>
