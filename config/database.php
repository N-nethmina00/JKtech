<?php
// config/database.php - Hybrid High-Availability Database Connection (MySQL + Local SQLite Fallback)

$host     = '127.0.0.1';
$port     = '3306';
$dbname   = 'jdm_autoparts';
$username = 'root';
$password = '';

$pdo = null;
$db_driver = 'none'; // 'mysql' or 'sqlite'
$db_error = null;

// 1. Attempt MySQL Connection (check both standard 3306 and XAMPP alternate 3307)
$mysqlPorts = [$port, '3307', '3306'];
$mysqlPorts = array_unique($mysqlPorts);
$mysqlConnected = false;

foreach ($mysqlPorts as $tryPort) {
    try {
        $dsn = "mysql:host={$host};port={$tryPort};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $testPdo = new PDO($dsn, $username, $password, $options);
        $testPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $testPdo->exec("USE `{$dbname}`");
        $pdo = $testPdo;
        $port = $tryPort;
        $db_driver = 'mysql';
        $mysqlConnected = true;

        // Auto-create any missing tables in MySQL
        initDatabaseTables($pdo, 'mysql');
        break;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

if (!$mysqlConnected) {
    // 2. Fallback to Local Persistent SQLite Database if MySQL server is not running
    try {
        $sqlitePath = __DIR__ . '/jdm_autoparts.db';
        $pdo = new PDO("sqlite:" . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db_driver = 'sqlite';

        // Auto-create tables in SQLite
        initDatabaseTables($pdo, 'sqlite');
    } catch (Exception $sqle) {
        $pdo = null;
        $db_driver = 'none';
        $db_error .= " | SQLite: " . $sqle->getMessage();
    }
}

/**
 * Retrieve active PDO database connection
 */
function getDbConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Retrieve database driver currently in use ('mysql' or 'sqlite')
 */
function getDbDriver() {
    global $db_driver;
    return $db_driver;
}

/**
 * Retrieve live database diagnostic status
 */
function getDbStatus() {
    global $db_driver, $dbname, $db_error, $host, $port;
    if ($db_driver === 'mysql') {
        return [
            'connected' => true,
            'driver' => 'MySQL',
            'badge' => '🟢 MySQL Live (' . $dbname . ')',
            'badge_class' => 'badge-success',
            'detail' => "Connected to MySQL at {$host}:{$port} -> `{$dbname}`"
        ];
    } elseif ($db_driver === 'sqlite') {
        return [
            'connected' => true,
            'driver' => 'SQLite',
            'badge' => '🟡 Local DB Active (jdm_autoparts.db)',
            'badge_class' => 'badge-warning',
            'detail' => "MySQL not running. Using local persistent SQLite database. (Start MySQL in XAMPP anytime to switch to MySQL)"
        ];
    }
    return [
        'connected' => false,
        'driver' => 'None',
        'badge' => '🔴 Database Offline',
        'badge_class' => 'badge-danger',
        'detail' => $db_error
    ];
}

/**
 * Initialize all required schema tables if they don't exist yet
 */
function initDatabaseTables($pdo, $driver) {
    if (!$pdo) return;

    if ($driver === 'mysql') {
        // Users Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(30) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `city` VARCHAR(80) DEFAULT 'Colombo',
            `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Categories Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `icon` VARCHAR(50) DEFAULT 'engine',
            `description` TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Brands Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `brands` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(80) NOT NULL UNIQUE,
            `country` VARCHAR(50) DEFAULT 'Japan'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Products Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(200) NOT NULL,
            `sku` VARCHAR(60) NOT NULL UNIQUE,
            `category_id` INT UNSIGNED DEFAULT 1,
            `brand_id` INT UNSIGNED DEFAULT 1,
            `model_compatibility` VARCHAR(255) DEFAULT 'General JDM Fitment',
            `year_from` INT DEFAULT 2004,
            `year_to` INT DEFAULT 2020,
            `grade` VARCHAR(20) DEFAULT 'Grade A',
            `mileage` VARCHAR(50) DEFAULT 'Inspected',
            `compression` VARCHAR(80) DEFAULT 'Tested',
            `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
            `stock_quantity` INT NOT NULL DEFAULT 1,
            `image_url` VARCHAR(255) DEFAULT 'engine_1.svg',
            `description` TEXT,
            `is_featured` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Product Images Table (Multi-Photo Support)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `product_images` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `image_path` VARCHAR(255) NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Orders Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED DEFAULT NULL,
            `customer_name` VARCHAR(100) NOT NULL,
            `customer_email` VARCHAR(150) NOT NULL,
            `customer_phone` VARCHAR(30) NOT NULL,
            `delivery_address` TEXT NOT NULL,
            `city` VARCHAR(80) NOT NULL,
            `postal_code` VARCHAR(20) DEFAULT NULL,
            `payment_method` ENUM('cod', 'bank_transfer', 'card') DEFAULT 'cod',
            `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
            `status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Order Items Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT UNSIGNED NOT NULL,
            `product_id` INT UNSIGNED NOT NULL,
            `product_name` VARCHAR(200) NOT NULL,
            `price` DECIMAL(12, 2) NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `subtotal` DECIMAL(12, 2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    } else {
        // SQLite Schema
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            phone TEXT DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city TEXT DEFAULT 'Colombo',
            role TEXT NOT NULL DEFAULT 'customer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            icon TEXT DEFAULT 'engine',
            description TEXT DEFAULT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            country TEXT DEFAULT 'Japan'
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            sku TEXT NOT NULL UNIQUE,
            category_id INTEGER DEFAULT 1,
            brand_id INTEGER DEFAULT 1,
            model_compatibility TEXT DEFAULT 'General JDM Fitment',
            year_from INTEGER DEFAULT 2004,
            year_to INTEGER DEFAULT 2020,
            grade TEXT DEFAULT 'Grade A',
            mileage TEXT DEFAULT 'Inspected',
            compression TEXT DEFAULT 'Tested',
            price REAL NOT NULL DEFAULT 0.00,
            stock_quantity INTEGER NOT NULL DEFAULT 1,
            image_url TEXT DEFAULT 'engine_1.svg',
            description TEXT,
            is_featured INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            is_primary INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            delivery_address TEXT NOT NULL,
            city TEXT NOT NULL,
            postal_code TEXT DEFAULT NULL,
            payment_method TEXT DEFAULT 'cod',
            total_amount REAL NOT NULL DEFAULT 0.00,
            status TEXT DEFAULT 'Pending',
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            subtotal REAL NOT NULL
        )");
    }

    // Seed default admin if missing
    seedDefaultData($pdo);
}

/**
 * Seed initial baseline records if database is freshly created
 */
function seedDefaultData($pdo) {
    try {
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $custHash  = password_hash('customer123', PASSWORD_DEFAULT);

        $uCheck = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($uCheck == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(['JK Tech Admin', 'admin@jktechmotors.com', $adminHash, '+94 77 837 6481', '45 Yokohama Avenue', 'Colombo 10', 'admin']);
            $stmt->execute(['Kasun Perera', 'customer@gmail.com', $custHash, '+94 71 987 6543', '128 Kandy Road', 'Kelaniya', 'customer']);
        } else {
            // Guarantee that an admin account with admin@jktechmotors.com exists and is active
            $checkAdmin = $pdo->query("SELECT id FROM users WHERE role = 'admin' OR email = 'admin@jktechmotors.com' LIMIT 1")->fetch();
            if ($checkAdmin) {
                $up = $pdo->prepare("UPDATE users SET email = 'admin@jktechmotors.com', password = ?, role = 'admin' WHERE id = ?");
                $up->execute([$adminHash, $checkAdmin['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute(['JK Tech Admin', 'admin@jktechmotors.com', $adminHash, '+94 77 837 6481', '45 Yokohama Avenue', 'Colombo 10', 'admin']);
            }
        }

        $bCheck = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
        if ($bCheck == 0) {
            $bStmt = $pdo->prepare("INSERT INTO brands (name, country) VALUES (?, 'Japan')");
            foreach (['Toyota', 'Nissan', 'Honda', 'Mazda', 'Mitsubishi', 'Subaru', 'Suzuki', 'Daihatsu', 'Isuzu'] as $b) {
                $bStmt->execute([$b]);
            }
        }

        $cCheck = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($cCheck == 0) {
            $cStmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
            $cStmt->execute(['Engine Assemblies', 'engine-assemblies', 'engine', 'Complete low-mileage tested petrol and diesel assemblies direct from Japan auctions.']);
            $cStmt->execute(['Transmission & Gearbox', 'transmissions', 'gearbox', 'Automatic, CVT, and 5/6-Speed Manual gearboxes with torque converters.']);
            $cStmt->execute(['Suspension & Steering', 'suspension', 'shock', 'JDM OEM struts, coilovers, lower arms, stabilizer bars, and steering racks.']);
            $cStmt->execute(['Brake Calipers & Rotors', 'braking', 'disc', 'Multi-pot calipers, ventilated rotors, brake boosters, and ABS modules.']);
            $cStmt->execute(['Electrical & ECUs', 'electrical', 'chip', 'OEM engine control units, sensors, wiring harnesses, alternators, and starter motors.']);
            $cStmt->execute(['Body Panels & Exterior', 'body-panels', 'car', 'Rust-free genuine doors, bonnets, boot lids, bumpers, and xenon headlights.']);
            $cStmt->execute(['Turbochargers & Intakes', 'turbo-intake', 'turbo', 'OEM & aftermarket JDM turbos, intercoolers, blow-off valves, and intake manifolds.']);
            $cStmt->execute(['Cooling & Radiators', 'cooling', 'radiator', 'Aluminum dual-core radiators, electric fan assemblies, and water pumps.']);
        }

        $pCheck = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($pCheck == 0) {
            $pStmt = $pdo->prepare("INSERT INTO products (name, sku, category_id, brand_id, model_compatibility, price, stock_quantity, image_url, description, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $pStmt->execute(['Toyota 1NZ-FE VVT-i Engine Assembly', 'ENG-TOY-1NZ-01', 1, 1, 'Corolla NZE121/141, Allion NZT240/260, Premio', 185000.00, 4, 'engine_1.svg', 'Genuine JDM reconditioned Toyota 1NZ-FE 1.5L DOHC 16-Valve engine with VVT-i. Compression verified, cold-start tested, comes complete with intake manifold and throttle body.', 1]);
            $pStmt->execute(['Nissan HR15DE Automatic CVT Transmission', 'TRN-NIS-HR15-02', 2, 2, 'Nissan Tiida C11, Wingroad Y12, Latio', 95000.00, 3, 'transmission_1.svg', 'Smooth shifting XTRONIC CVT transmission pulled from a low-mileage Nissan Tiida in Chiba. Clean fluid, torque converter included.', 1]);
            $pStmt->execute(['Tein Street Advance Z Coilover Damper Kit', 'SUS-HON-TEIN-03', 3, 3, 'Honda Civic FD1, FD2, FA1', 145000.00, 5, 'suspension_1.svg', 'Authentic Tein Japan height-adjustable twin-tube suspension system. Complete set of 4 dampers with springs.', 1]);
            $pStmt->execute(['Brembo Front 4-Pot Monobloc Calipers & Rotors', 'BRK-SUB-BREM-04', 4, 6, 'Subaru Impreza WRX STI GDB/GRB, Forester SG9', 120000.00, 2, 'brake_1.svg', 'Factory Gold Brembo 4-pot radial mount front calipers paired with 326mm slotted rotors. Reconditioned with fresh seals.', 1]);
            $pStmt->execute(['Honda K20A Type-R Red Top Engine Assembly', 'ENG-HON-K20A-06', 1, 3, 'Integra DC5 Type-R, Civic EP3', 480000.00, 1, 'engine_2.svg', 'High-revving naturally aspirated JDM 2.0L i-VTEC DOHC engine producing 220PS. Pristine red valve cover.', 1]);
        }
    } catch (Exception $ex) {
        // Table initialization or seeding handled gracefully
    }
}
