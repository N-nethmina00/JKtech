<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Format currency in Sri Lankan Rupees
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format((float)$amount, 0, '.', ',');
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash notification system
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Authentication Helpers
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer'
    ];
}

function isAdmin() {
    return isLoggedIn() && (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function requireAuth() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to continue.');
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'Administrator login required to access Admin Console.');
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script, '/admin/') !== false) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit;
    }
}

/**
 * Shopping Cart Management (Session-based)
 */
function initCart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function getCart() {
    initCart();
    return $_SESSION['cart'];
}

function addToCart($productId, $qty = 1, $productData = null) {
    initCart();
    $productId = (int)$productId;
    $qty = max(1, (int)$qty);

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $qty;
    } else {
        if ($productData === null) {
            $productData = fetchProductById($productId);
        }
        if ($productData) {
            $_SESSION['cart'][$productId] = [
                'id' => $productId,
                'name' => $productData['name'],
                'sku' => $productData['sku'] ?? 'JDM-PART',
                'price' => (float)$productData['price'],
                'grade' => $productData['grade'] ?? 'Grade A',
                'image_url' => $productData['image_url'] ?? 'engine_1.svg',
                'quantity' => $qty,
                'stock' => (int)($productData['stock_quantity'] ?? 5)
            ];
        }
    }
}

function updateCartQuantity($productId, $qty) {
    initCart();
    $productId = (int)$productId;
    $qty = (int)$qty;
    if ($qty <= 0) {
        removeFromCart($productId);
    } elseif (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = $qty;
    }
}

function removeFromCart($productId) {
    initCart();
    $productId = (int)$productId;
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function getCartCount() {
    initCart();
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function getCartSubtotal() {
    initCart();
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ($item['price'] * $item['quantity']);
    }
    return $total;
}

function getCartShipping() {
    $subtotal = getCartSubtotal();
    if ($subtotal === 0) return 0;
    // Flat delivery rate across Sri Lanka for heavy JDM parts
    return 2500.00;
}

function getCartTotal() {
    $subtotal = getCartSubtotal();
    if ($subtotal === 0) return 0;
    return $subtotal + getCartShipping();
}

/**
 * Data Access Helpers (with Fallback Mock Data if DB isn't yet migrated)
 */
function getFallbackProducts() {
    return [
        [
            'id' => 1,
            'name' => 'Toyota 1NZ-FE VVT-i Engine Assembly',
            'sku' => 'ENG-TOY-1NZ-01',
            'category_id' => 1,
            'category_name' => 'Engine Assemblies',
            'brand_id' => 1,
            'brand_name' => 'Toyota',
            'model_compatibility' => 'Corolla NZE121/141, Allion NZT240/260, Premio, Vios, Probox',
            'year_from' => 2004,
            'year_to' => 2016,
            'grade' => 'Grade A+',
            'mileage' => '48,200 km',
            'compression' => '12.8 bar across all cylinders',
            'price' => 185000.00,
            'stock_quantity' => 4,
            'image_url' => 'engine_1.svg',
            'description' => 'Genuine JDM reconditioned Toyota 1NZ-FE 1.5L DOHC 16-Valve engine with VVT-i. Directly sourced from Kanagawa yard, compression verified, cold-start tested, comes complete with intake manifold, fuel injectors, alternator and throttle body.',
            'is_featured' => 1
        ],
        [
            'id' => 2,
            'name' => 'Nissan HR15DE Automatic CVT Transmission',
            'sku' => 'TRN-NIS-HR15-02',
            'category_id' => 2,
            'category_name' => 'Transmission & Gearbox',
            'brand_id' => 2,
            'brand_name' => 'Nissan',
            'model_compatibility' => 'Nissan Tiida C11, Wingroad Y12, Latio, Note E11',
            'year_from' => 2006,
            'year_to' => 2014,
            'grade' => 'Grade A',
            'mileage' => '54,000 km',
            'compression' => 'Pressure tested 5.2 bar',
            'price' => 95000.00,
            'stock_quantity' => 3,
            'image_url' => 'transmission_1.svg',
            'description' => 'Smooth shifting XTRONIC CVT transmission pulled from a low-mileage Nissan Tiida in Chiba. Clean transmission fluid, torque converter included. 90-day replacement warranty.',
            'is_featured' => 1
        ],
        [
            'id' => 3,
            'name' => 'Tein Street Advance Z Coilover Damper Kit',
            'sku' => 'SUS-HON-TEIN-03',
            'category_id' => 3,
            'category_name' => 'Suspension & Steering',
            'brand_id' => 3,
            'brand_name' => 'Honda',
            'model_compatibility' => 'Honda Civic FD1, FD2, FA1, Mugen RR',
            'year_from' => 2006,
            'year_to' => 2012,
            'grade' => 'Grade A',
            'mileage' => '31,000 km',
            'compression' => '16-level damping verified',
            'price' => 145000.00,
            'stock_quantity' => 5,
            'image_url' => 'suspension_1.svg',
            'description' => 'Authentic Tein Japan height-adjustable twin-tube suspension system. Complete set of 4 dampers with springs, undamaged dust boots, no oil leaks. Enhances stance and cornering stability.',
            'is_featured' => 1
        ],
        [
            'id' => 4,
            'name' => 'Brembo Front 4-Pot Monobloc Calipers & Rotors',
            'sku' => 'BRK-SUB-BREM-04',
            'category_id' => 4,
            'category_name' => 'Brake Calipers & Rotors',
            'brand_id' => 6,
            'brand_name' => 'Subaru',
            'model_compatibility' => 'Subaru Impreza WRX STI GDB/GRB, Forester SG9, Legacy BL5',
            'year_from' => 2003,
            'year_to' => 2011,
            'grade' => 'Grade A',
            'mileage' => '42,000 km',
            'compression' => 'Piston seal integrity tested',
            'price' => 120000.00,
            'stock_quantity' => 2,
            'image_url' => 'brake_1.svg',
            'description' => 'Factory Gold Brembo 4-pot radial mount front calipers paired with 326mm slotted rotors. Reconditioned with fresh seals and high-friction JDM brake pads. Outstanding stopping power.',
            'is_featured' => 1
        ],
        [
            'id' => 5,
            'name' => 'Mitsubishi 4G63T EVO IX Turbocharger TD05HR',
            'sku' => 'TUR-MIT-4G63-05',
            'category_id' => 7,
            'category_name' => 'Turbochargers & Intakes',
            'brand_id' => 5,
            'brand_name' => 'Mitsubishi',
            'model_compatibility' => 'Lancer Evolution VII, VIII, IX (CT9A)',
            'year_from' => 2003,
            'year_to' => 2008,
            'grade' => 'Grade A+',
            'mileage' => '39,000 km',
            'compression' => 'Twin scroll balanced, 0 shaft play',
            'price' => 165000.00,
            'stock_quantity' => 2,
            'image_url' => 'turbo_1.svg',
            'description' => 'Original titanium-aluminide turbine wheel twin-scroll TD05HR turbocharger. Direct bolt-on for CT9A chassis. Clean compressor housing, wastegate actuator holding factory 1.1 bar.',
            'is_featured' => 1
        ],
        [
            'id' => 6,
            'name' => 'Honda K20A Type-R Red Top Engine Assembly',
            'sku' => 'ENG-HON-K20A-06',
            'category_id' => 1,
            'category_name' => 'Engine Assemblies',
            'brand_id' => 3,
            'brand_name' => 'Honda',
            'model_compatibility' => 'Integra DC5 Type-R, Civic EP3, Accord Euro-R CL7',
            'year_from' => 2002,
            'year_to' => 2006,
            'grade' => 'Grade A+',
            'mileage' => '41,500 km',
            'compression' => '14.1 bar balanced compression',
            'price' => 480000.00,
            'stock_quantity' => 1,
            'image_url' => 'engine_2.svg',
            'description' => 'High-revving naturally aspirated JDM 2.0L i-VTEC DOHC engine producing 220PS. Sourced from a genuine DC5 Type-R. Pristine red valve cover, high-cam profiles verified.',
            'is_featured' => 1
        ],
        [
            'id' => 7,
            'name' => 'Denso JDM High-Output Alternator & Starter',
            'sku' => 'ELC-DEN-ALT-10',
            'category_id' => 5,
            'category_name' => 'Electrical & ECUs',
            'brand_id' => 1,
            'brand_name' => 'Toyota',
            'model_compatibility' => 'Toyota Vitz, Corolla, Belta, Ractis, Passo',
            'year_from' => 2005,
            'year_to' => 2017,
            'grade' => 'Grade A+',
            'mileage' => '25,000 km',
            'compression' => '14.4V charging output tested',
            'price' => 32000.00,
            'stock_quantity' => 8,
            'image_url' => 'electrical_1.svg',
            'description' => 'Original Denso 12V 90A alternator matched with rapid-crank starter motor. Bench tested under electrical load. Guaranteed trouble-free ignition and charging.',
            'is_featured' => 0
        ],
        [
            'id' => 8,
            'name' => 'Koyorad Full Aluminum Dual-Core Racing Radiator',
            'sku' => 'CLG-KOY-ALU-11',
            'category_id' => 8,
            'category_name' => 'Cooling & Radiators',
            'brand_id' => 3,
            'brand_name' => 'Honda',
            'model_compatibility' => 'Honda Civic FD2 / FD1, CR-V RE3, Stream RN6',
            'year_from' => 2006,
            'year_to' => 2014,
            'grade' => 'Grade A+',
            'mileage' => 'New Old Stock JDM',
            'compression' => 'Leak-down pressure tested to 2.5 bar',
            'price' => 42000.00,
            'stock_quantity' => 6,
            'image_url' => 'cooling_1.svg',
            'description' => 'High efficiency 48mm dual-row all-aluminum core radiator. Sourced from Osaka performance warehouse. Drop-in fitment for enhanced cooling under tropical Sri Lankan heat.',
            'is_featured' => 0
        ]
    ];
}

function fetchAllProducts($filters = []) {
    $pdo = getDbConnection();
    if (!$pdo) {
        $products = getFallbackProducts();
        // apply filters in-memory
        if (!empty($filters['search'])) {
            $s = strtolower($filters['search']);
            $products = array_filter($products, function($p) use ($s) {
                return strpos(strtolower($p['name']), $s) !== false || strpos(strtolower($p['model_compatibility']), $s) !== false;
            });
        }
        if (!empty($filters['category_id'])) {
            $catId = (int)$filters['category_id'];
            $products = array_filter($products, function($p) use ($catId) {
                return $p['category_id'] == $catId;
            });
        }
        if (!empty($filters['brand_id'])) {
            $bId = (int)$filters['brand_id'];
            $products = array_filter($products, function($p) use ($bId) {
                return $p['brand_id'] == $bId;
            });
        }
        if (!empty($filters['featured'])) {
            $products = array_filter($products, function($p) {
                return !empty($p['is_featured']);
            });
        }
        if (!empty($filters['max_price'])) {
            $max = (float)$filters['max_price'];
            $products = array_filter($products, function($p) use ($max) {
                return $p['price'] <= $max;
            });
        }
        return array_values($products);
    }

    $sql = "SELECT p.*, c.name AS category_name, b.name AS brand_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (p.name LIKE ? OR p.model_compatibility LIKE ? OR p.description LIKE ?)";
        $term = '%' . $filters['search'] . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    if (!empty($filters['category_id'])) {
        $sql .= " AND p.category_id = ?";
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['brand_id'])) {
        $sql .= " AND p.brand_id = ?";
        $params[] = (int)$filters['brand_id'];
    }
    if (!empty($filters['featured'])) {
        $sql .= " AND p.is_featured = 1";
    }
    if (!empty($filters['grade'])) {
        $sql .= " AND p.grade = ?";
        $params[] = $filters['grade'];
    }
    if (!empty($filters['max_price'])) {
        $sql .= " AND p.price <= ?";
        $params[] = (float)$filters['max_price'];
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchProductById($id) {
    $id = (int)$id;
    $pdo = getDbConnection();
    if (!$pdo) {
        $products = getFallbackProducts();
        foreach ($products as $p) {
            if ($p['id'] === $id) return $p;
        }
        return null;
    }

    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, b.name AS brand_name 
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           LEFT JOIN brands b ON p.brand_id = b.id
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function fetchCategories() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            ['id' => 1, 'name' => 'Engine Assemblies', 'slug' => 'engine-assemblies', 'icon' => 'engine', 'count' => 124],
            ['id' => 2, 'name' => 'Transmission & Gearbox', 'slug' => 'transmissions', 'icon' => 'gearbox', 'count' => 86],
            ['id' => 3, 'name' => 'Suspension & Steering', 'slug' => 'suspension', 'icon' => 'shock', 'count' => 92],
            ['id' => 4, 'name' => 'Brake Calipers & Rotors', 'slug' => 'braking', 'icon' => 'disc', 'count' => 64],
            ['id' => 5, 'name' => 'Electrical & ECUs', 'slug' => 'electrical', 'icon' => 'chip', 'count' => 110],
            ['id' => 6, 'name' => 'Body Panels & Exterior', 'slug' => 'body-panels', 'icon' => 'car', 'count' => 150],
            ['id' => 7, 'name' => 'Turbochargers & Intakes', 'slug' => 'turbo-intake', 'icon' => 'turbo', 'count' => 43],
            ['id' => 8, 'name' => 'Cooling & Radiators', 'slug' => 'cooling', 'icon' => 'radiator', 'count' => 58]
        ];
    }
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) AS count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id ORDER BY c.id ASC");
    return $stmt->fetchAll();
}

function fetchBrands() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            ['id' => 1, 'name' => 'Toyota'],
            ['id' => 2, 'name' => 'Nissan'],
            ['id' => 3, 'name' => 'Honda'],
            ['id' => 4, 'name' => 'Mazda'],
            ['id' => 5, 'name' => 'Mitsubishi'],
            ['id' => 6, 'name' => 'Subaru'],
            ['id' => 7, 'name' => 'Suzuki']
        ];
    }
    $stmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Resolve displayable URL for a product image
 * Supports uploaded photos in assets/uploads/products/ as well as built-in SVGs in assets/images/
 */
function getProductImageUrl($image) {
    if (empty($image)) {
        return 'assets/images/engine_1.svg';
    }
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }
    if (str_starts_with($image, 'assets/')) {
        return $image;
    }
    if (str_starts_with($image, 'uploads/')) {
        return 'assets/' . $image;
    }
    // Check if uploaded file exists in uploads folder
    $uploadPath = __DIR__ . '/../assets/uploads/products/' . $image;
    if (file_exists($uploadPath) && !is_dir($uploadPath)) {
        return 'assets/uploads/products/' . $image;
    }
    // Check built-in images folder
    $builtinPath = __DIR__ . '/../assets/images/' . $image;
    if (file_exists($builtinPath) && !is_dir($builtinPath)) {
        return 'assets/images/' . $image;
    }
    // Default to uploaded path or fallback
    return 'assets/uploads/products/' . $image;
}

/**
 * Fetch all gallery photo paths associated with a product (Primary photo is always index 0)
 */
function fetchProductImages($productId) {
    $productId = (int)$productId;
    $pdo = getDbConnection();
    $images = [];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
            $stmt->execute([$productId]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Table might be initializing
        }
    }

    // If no records in product_images, fallback to product's image_url
    if (empty($images)) {
        $p = fetchProductById($productId);
        if ($p && !empty($p['image_url'])) {
            $images[] = $p['image_url'];
        } else {
            $images[] = 'engine_1.svg';
        }
    }

    return array_values(array_unique($images));
}

/**
 * Fetch all photos for a product with full metadata (id, image_path, is_primary)
 */
function fetchProductPhotosMeta($productId) {
    $productId = (int)$productId;
    $pdo = getDbConnection();
    $photos = [];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT id, image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
            $stmt->execute([$productId]);
            $photos = $stmt->fetchAll();
        } catch (Exception $e) {}
    }

    if (empty($photos)) {
        $p = fetchProductById($productId);
        $primary = ($p && !empty($p['image_url'])) ? $p['image_url'] : 'engine_1.svg';
        $photos[] = [
            'id' => 0,
            'image_path' => $primary,
            'is_primary' => 1
        ];
    }

    return $photos;
}

/**
 * Set a specific photo as the primary cover photo for a product
 */
function setPrimaryProductPhoto($photoId, $productId) {
    $photoId = (int)$photoId;
    $productId = (int)$productId;
    $pdo = getDbConnection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->execute([$photoId, $productId]);
        $path = $stmt->fetchColumn();
        if (!$path) return false;

        $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$photoId]);
        $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$path, $productId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a single photo from a product and disk
 */
function deleteProductPhoto($photoId, $productId) {
    $photoId = (int)$photoId;
    $productId = (int)$productId;
    $pdo = getDbConnection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("SELECT image_path, is_primary FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->execute([$photoId, $productId]);
        $photo = $stmt->fetch();
        if (!$photo) return false;

        // Delete from disk if in uploads directory
        $baseName = basename($photo['image_path']);
        $filePath = __DIR__ . '/../assets/uploads/products/' . $baseName;
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }

        // Delete DB record
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$photoId]);

        // If it was primary cover photo, promote next photo to primary
        if (!empty($photo['is_primary'])) {
            $next = $pdo->prepare("SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1");
            $next->execute([$productId]);
            $nextPhoto = $next->fetch();
            if ($nextPhoto) {
                $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$nextPhoto['id']]);
                $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$nextPhoto['image_path'], $productId]);
            } else {
                $pdo->prepare("UPDATE products SET image_url = 'engine_1.svg' WHERE id = ?")->execute([$productId]);
            }
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a product and all its uploaded image files from disk and database
 */
function deleteProductById($productId) {
    $productId = (int)$productId;
    $pdo = getDbConnection();
    if (!$pdo) return false;

    try {
        // 1. Fetch images to delete from disk
        $photos = fetchProductImages($productId);
        $prod = fetchProductById($productId);
        if ($prod && !empty($prod['image_url'])) {
            $photos[] = $prod['image_url'];
        }

        foreach (array_unique($photos) as $photo) {
            $baseName = basename($photo);
            $filePath = __DIR__ . '/../assets/uploads/products/' . $baseName;
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        // 2. Delete database records
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Register a new user in database with full error checking
 */
function registerUser($name, $email, $password, $phone = '', $city = 'Colombo', $role = 'customer') {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection failed. Please ensure MySQL is running.'];
    }

    try {
        // Check duplicate email
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            return ['success' => false, 'error' => "An account with the email '{$email}' already exists. Please sign in."];
        }

        // Check duplicate phone if provided
        if (!empty($phone)) {
            $pchk = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $pchk->execute([$phone]);
            if ($pchk->fetch()) {
                return ['success' => false, 'error' => "An account with phone '{$phone}' already exists. Please sign in."];
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, ?, '', ?, ?)");
        $ins->execute([$name, $email, $hash, $phone, $city, $role]);
        $newId = (int)$pdo->lastInsertId();

        $fetch = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $fetch->execute([$newId]);
        $user = $fetch->fetch();

        return ['success' => true, 'user_id' => $newId, 'user' => $user];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Authenticate user with Email OR Phone Number
 */
function authenticateUser($loginInput, $password) {
    $loginInput = trim($loginInput);
    $loginLower = strtolower($loginInput);
    $password = trim($password);

    $pdo = getDbConnection();
    if (!$pdo) {
        // Fallback demo logins if database unavailable
        if (in_array($loginLower, ['admin', 'admin@jktechmotors.com', 'admin@jdmparts.com']) && $password === 'admin123') {
            return ['success' => true, 'user' => ['id' => 1, 'name' => 'JK Tech Admin', 'email' => 'admin@jktechmotors.com', 'role' => 'admin']];
        }
        if ($loginLower === 'customer@gmail.com' && $password === 'customer123') {
            return ['success' => true, 'user' => ['id' => 2, 'name' => 'Kasun Perera', 'email' => 'customer@gmail.com', 'role' => 'customer']];
        }
        return ['success' => false, 'error' => 'Database connection unavailable and invalid demo credentials.'];
    }

    try {
        // 1. Admin direct login support (accepts 'admin', 'admin@jktechmotors.com', 'admin@jdmparts.com' with admin123)
        if (in_array($loginLower, ['admin', 'admin@jktechmotors.com', 'admin@jdmparts.com']) && $password === 'admin123') {
            $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
            $user = $stmt->fetch();
            if ($user) {
                return ['success' => true, 'user' => $user];
            }
            // Auto-create admin if somehow missing
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute(['JK Tech Admin', 'admin@jktechmotors.com', $adminPass, '+94 77 837 6481', '45 Yokohama Avenue', 'Colombo 10', 'admin']);
            $newAdmin = $pdo->query("SELECT * FROM users WHERE email = 'admin@jktechmotors.com'")->fetch();
            return ['success' => true, 'user' => $newAdmin];
        }

        // 2. Standard Case-Insensitive Check
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR phone = ?");
        $stmt->execute([$loginLower, $loginInput]);
        $user = $stmt->fetch();

        if ($user) {
            // Check password (bcrypt or plain 'admin123' if role is admin)
            if (($user['role'] === 'admin' && $password === 'admin123') || password_verify($password, $user['password'])) {
                return ['success' => true, 'user' => $user];
            }
        }
        return ['success' => false, 'error' => 'Invalid email/mobile number or password.'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Authentication error: ' . $e->getMessage()];
    }
}

/**
 * Find or create user via Mobile Number (OTP Login)
 */
function findOrCreateMobileUser($mobileNumber) {
    $pdo = getDbConnection();
    if (!$pdo) return null;

    $clean = preg_replace('/[^0-9+]/', '', $mobileNumber);
    if (empty($clean)) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR phone LIKE ?");
        $stmt->execute([$clean, '%' . substr($clean, -9)]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        // Auto-register new mobile user in database
        $autoName = "Client (" . substr($clean, -4) . ")";
        $autoEmail = "mobile_" . preg_replace('/[^0-9]/', '', $clean) . "@jktech.lk";
        $autoPass = password_hash("client_" . time(), PASSWORD_DEFAULT);

        $ins = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, ?, 'Registered via Mobile OTP', 'Colombo', 'customer')");
        $ins->execute([$autoName, $autoEmail, $autoPass, $clean]);
        $newId = (int)$pdo->lastInsertId();

        $fetch = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $fetch->execute([$newId]);
        return $fetch->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Find or create user via Social Provider (Google / Facebook)
 */
function findOrCreateSocialUser($provider, $email, $name) {
    $pdo = getDbConnection();
    if (!$pdo) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        $autoPass = password_hash(uniqid($provider . '_'), PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, '', 'Signed in with " . ucfirst($provider) . "', 'Colombo', 'customer')");
        $ins->execute([$name, $email, $autoPass]);
        $newId = (int)$pdo->lastInsertId();

        $fetch = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $fetch->execute([$newId]);
        return $fetch->fetch();
    } catch (Exception $e) {
        return null;
    }
}

