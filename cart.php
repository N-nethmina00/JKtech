<?php
// cart.php - Shopping Cart Handler & Modern UI
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($action === 'add') {
        $qty = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        addToCart($productId, $qty);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cart_count' => getCartCount(),
                'cart_subtotal' => formatCurrency(getCartSubtotal())
            ]);
            exit;
        }
        setFlash('success', 'Part added to cart.');
        header('Location: cart.php');
        exit;
    }

    if ($action === 'update') {
        $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        updateCartQuantity($productId, $qty);
        setFlash('success', 'Cart quantities updated.');
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove') {
        removeFromCart($productId);
        setFlash('info', 'Item removed from your cart.');
        header('Location: cart.php');
        exit;
    }

    if ($action === 'clear') {
        clearCart();
        setFlash('info', 'Shopping cart cleared.');
        header('Location: cart.php');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    addToCart((int)$_GET['id'], 1);
    header('Location: cart.php');
    exit;
}

$cart = getCart();
$cartCount = getCartCount();
$subtotal = getCartSubtotal();
$shipping = getCartShipping();
$total = getCartTotal();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart (<?= $cartCount ?> Items) | JK Tech Motors</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .cart-layout {
      display: grid;
      grid-template-columns: 1.45fr 0.55fr;
      gap: 40px;
      padding: 44px 0 90px;
    }
    .cart-table-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    .cart-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 14px;
    }
    .cart-table th {
      background: #090e18;
      padding: 18px 24px;
      color: var(--text-muted);
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.8px;
      border-bottom: 1px solid var(--border-subtle);
      font-family: var(--font-display);
      font-weight: 800;
    }
    .cart-table td {
      padding: 22px 24px;
      border-bottom: 1px solid var(--border-subtle);
      vertical-align: middle;
    }
    .cart-item-row {
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .cart-item-img {
      width: 80px;
      height: 64px;
      border-radius: var(--radius-sm);
      background: #040711;
      overflow: hidden;
      flex-shrink: 0;
      border: 1px solid var(--border-subtle);
    }
    .cart-item-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .cart-item-info h4 {
      font-family: var(--font-display);
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }
    .cart-item-info span {
      font-size: 12px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }
    .cart-stepper {
      display: inline-flex;
      align-items: center;
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-sm);
    }
    .cart-stepper button {
      background: transparent;
      border: none;
      color: var(--text-main);
      padding: 8px 12px;
      cursor: pointer;
      font-size: 14px;
      transition: color 0.2s;
    }
    .cart-stepper button:hover {
      color: var(--neon-cyan);
    }
    .cart-stepper input {
      background: transparent;
      border: none;
      color: #fff;
      width: 36px;
      text-align: center;
      font-weight: 800;
      font-size: 13px;
      font-family: var(--font-display);
    }
    .btn-remove {
      background: transparent;
      border: none;
      color: var(--text-dim);
      cursor: pointer;
      padding: 8px;
      transition: all 0.2s;
      border-radius: 4px;
    }
    .btn-remove:hover {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.1);
    }
    .summary-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: var(--radius-md);
      padding: 28px;
      height: fit-content;
      position: sticky;
      top: 98px;
      box-shadow: var(--shadow-sm);
    }
    .summary-title {
      font-family: var(--font-display);
      font-size: 18px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 22px;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 14px;
      letter-spacing: 0.5px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 16px;
      font-size: 14px;
      color: var(--text-muted);
    }
    .summary-row.total {
      font-family: var(--font-display);
      font-size: 20px;
      font-weight: 900;
      color: #fff;
      border-top: 1px solid var(--border-subtle);
      padding-top: 18px;
      margin-top: 18px;
    }
    @media (max-width: 992px) {
      .cart-layout {
        grid-template-columns: 1fr;
      }
      .summary-card {
        position: static;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- Page Header -->
  <div style="background: #090e18; border-bottom: 1px solid var(--border-subtle); padding: 36px 0;">
    <div class="container">
      <h1 style="font-family: var(--font-display); font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -0.4px;">
        SHOPPING CART (<?= $cartCount ?> ITEMS)
      </h1>
      <p style="color: var(--text-muted); font-size: 14px;">Review your reconditioned components before proceeding to secure checkout.</p>
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
    <?php if (empty($cart)): ?>
      <div style="padding: 90px 0; text-align: center;">
        <div style="width: 80px; height: 80px; background: var(--bg-card); border-radius: 50%; border: 1px solid var(--border-card); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: var(--text-dim);">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
          </svg>
        </div>
        <h3 style="font-family: var(--font-display); font-size: 22px; color: #fff; margin-bottom: 8px;">Your Shopping Cart is Empty</h3>
        <p style="color: var(--text-muted); font-size: 15px; max-width: 440px; margin: 0 auto 28px;">
          Explore our certified Japanese spare parts directory to find engines, gearboxes, and suspension systems.
        </p>
        <a href="products.php" class="btn btn-primary btn-lg">Browse Parts Directory &rarr;</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <!-- Left Table -->
        <div>
          <div class="cart-table-card">
            <table class="cart-table">
              <thead>
                <tr>
                  <th>Component / Assembly</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Subtotal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart as $item): ?>
                  <tr>
                    <td>
                      <div class="cart-item-row">
                        <div class="cart-item-img">
                          <img src="<?= htmlspecialchars(getProductImageUrl($item['image_url'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="cart-item-info">
                          <h4><?= htmlspecialchars($item['name']) ?></h4>
                          <span>SKU: <?= htmlspecialchars($item['sku']) ?> • <?= htmlspecialchars($item['grade']) ?></span>
                        </div>
                      </div>
                    </td>
                    <td style="color: #fff; font-weight: 700; font-family: var(--font-display);">
                      <?= formatCurrency($item['price']) ?>
                    </td>
                    <td>
                      <div class="cart-stepper">
                        <button type="button" class="qty-minus" data-id="<?= $item['id'] ?>">-</button>
                        <input type="number" class="cart-qty-input" data-id="<?= $item['id'] ?>" value="<?= $item['quantity'] ?>" min="1" max="10">
                        <button type="button" class="qty-plus" data-id="<?= $item['id'] ?>">+</button>
                      </div>
                    </td>
                    <td style="color: #fff; font-weight: 800; font-family: var(--font-display); font-size: 16px;">
                      <?= formatCurrency($item['price'] * $item['quantity']) ?>
                    </td>
                    <td>
                      <form method="POST" action="cart.php" style="display:inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-remove" title="Remove part">
                          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                          </svg>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between" style="margin-top: 24px;">
            <a href="products.php" class="btn btn-outline btn-sm">&larr; Continue Shopping</a>
            <form method="POST" action="cart.php">
              <input type="hidden" name="action" value="clear">
              <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Clear entire cart?')">Clear All Items</button>
            </form>
          </div>
        </div>

        <!-- Right Summary -->
        <div>
          <div class="summary-card">
            <h3 class="summary-title">ORDER SUMMARY</h3>
            <div class="summary-row">
              <span>Items Subtotal:</span>
              <strong style="color: #fff; font-family: var(--font-display);"><?= formatCurrency($subtotal) ?></strong>
            </div>
            <div class="summary-row">
              <span>Islandwide Freight:</span>
              <span style="color: var(--neon-emerald); font-weight: 700;">Fixed <?= formatCurrency($shipping) ?></span>
            </div>
            <div class="summary-row">
              <span>JEVIC Testing &amp; Tax:</span>
              <span style="color: var(--neon-cyan); font-weight: 700;">Included</span>
            </div>

            <div class="summary-row total">
              <span>Total Amount:</span>
              <span style="color: #fff;"><?= formatCurrency($total) ?></span>
            </div>

            <a href="checkout.php" class="btn btn-primary btn-lg btn-block" style="margin-top: 26px;">
              <span>Proceed to Checkout</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
              </svg>
            </a>

            <div style="margin-top: 20px; font-size: 12px; color: var(--text-dim); text-align: center; line-height: 1.5;">
              🔒 256-bit Encrypted Checkout • 90-Day Replacement Warranty
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="assets/js/cart.js"></script>
</body>
</html>
