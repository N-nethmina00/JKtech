# JDM Japanese Reconditioned Auto Parts E-Commerce Platform

A complete, high-fidelity dark-themed e-commerce web application for Japanese reconditioned automotive spare parts (engines, gearboxes, suspension, brakes, electricals) built with **PHP, MySQL, HTML5, CSS3, and JavaScript**, strictly following the provided UI design and project folder structure.

---

## 📁 Exact Project Folder Structure

```
jdm-autoparts/
├── admin/
│   ├── customers.php        # Registered customer and garage directory
│   ├── index.php            # Admin KPI dashboard (Revenue, Orders, Low-Stock alerts)
│   ├── inventory.php        # Warehouse stock monitor & quick level adjustments (+/-)
│   ├── orders.php           # Order management and status updates (Pending -> Delivered)
│   └── products.php         # Product catalog management (Add, Edit, Delete, Stock)
├── assets/
│   ├── css/
│   │   ├── home.css         # Hero, vehicle finder, assembly cards, timeline styles
│   │   └── style.css        # Global dark design tokens, components, navbar, footer
│   ├── images/              # Authentic SVG schematics, badges, product illustrations & logo
│   │   ├── brake_1.svg
│   │   ├── cooling_1.svg
│   │   ├── electrical_1.svg
│   │   ├── engine_1.svg
│   │   ├── engine_2.svg
│   │   ├── logo.svg
│   │   ├── suspension_1.svg
│   │   ├── transmission_1.svg
│   │   └── turbo_1.svg
│   └── js/
│       ├── admin.js         # Modal windows, status toggling, delete confirmations
│       ├── cart.js          # Cart steppers, live quantity sync, subtotal calculations
│       ├── main.js          # Mobile navigation, search, toast alerts, vehicle finder
│       └── products.js      # Real-time catalog filtering, price range slider, sort
├── config/
│   └── database.php         # PDO MySQL connection handler with error recovery
├── database/
│   └── schema.sql           # Complete MySQL database schema with authentic JDM seed data
├── includes/
│   ├── footer.php           # Site-wide dark footer with logistics hubs & links
│   ├── functions.php        # Currency formatting (Rs.), session cart, auth helpers
│   └── navbar.php           # Sticky navbar with search, cart counter & account dropdown
├── cart.php                 # Shopping cart with item list and order summary
├── checkout.php             # Secure checkout with address and payment mode selection
├── index.php                # Homepage matching the provided hero & section layouts
├── login.php                # Customer & admin authentication
├── logout.php               # Session termination and safe redirection
├── orders.php               # Customer order history and dispatch status tracker
├── product-details.php      # Product page with compression ratings, mileage & gallery
├── products.php             # Full directory with vehicle make, category & price filters
├── profile.php              # Customer workshop details and shipping address management
├── README.md                # Documentation and setup guide
└── register.php             # Customer registration with input validation
```

---

## 🚀 Getting Started & Setup Guide

### Prerequisites
- **PHP**: Version 7.4 or 8.x
- **MySQL / MariaDB**: MySQL 5.7+ or MariaDB 10.3+
- **Server**: XAMPP, WampServer, Laragon, or PHP's built-in server.

---

### Step 1: Database Setup

1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or your MySQL client (e.g. MySQL Workbench, HeidiSQL, or CLI).
2. Create a database named `jdm_autoparts` (or let the script create it automatically):
   ```sql
   CREATE DATABASE jdm_autoparts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the file:
   `database/schema.sql`
   - In phpMyAdmin: Click **Import** > Choose `database/schema.sql` > Click **Go**.
   - Or via MySQL CLI:
     ```bash
     mysql -u root -p jdm_autoparts < database/schema.sql
     ```

### Step 2: Database Connection Configuration
Open `config/database.php` and ensure your database credentials match your local environment:
```php
$host     = '127.0.0.1';
$port     = '3307';
$dbname   = 'jdm_autoparts';
$username = 'root';        // Default in XAMPP
$password = '';            // Blank by default in XAMPP
```

> **Note**: The application is built with a resilient fallback system. If MySQL is not connected or running, all catalog views, products, shopping cart, and mock authentication still function seamlessly in demo mode!

---

### Step 3: Run the Application

#### Option A: Using PHP Built-In Server (Quickest)
Open your terminal in the project directory `C:\Users\ASUS\.gemini\antigravity\scratch\jdm-autoparts\` and execute:
```bash
php -S localhost:8000
```
Then open your browser and navigate to:
```
http://localhost:8000
```

#### Option B: Using XAMPP
1. Copy or move the `jdm-autoparts` directory into your XAMPP web root:
   `C:\xampp\htdocs\jdm-autoparts`
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser and navigate to:
   `http://localhost/jdm-autoparts/`

---

## 🔐 Default Login Credentials

| Role | Email Address | Password | Destination |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@jdmparts.com` | `admin123` | Admin Operations Console (`admin/index.php`) |
| **Customer / Garage** | `customer@gmail.com` | `customer123` | Customer Portal & Cart |

---

## 🌟 Key Features

1. **Dark Automotive Aesthetics**:
   - Deep obsidian backgrounds (`#0B0F17`, `#111827`) with neon cyan (`#06B6D4`) and glowing orange (`#FF4D2D`) accents.
   - Accurate Sri Lankan Rupee currency formatting (`Rs. 185,000`).

2. **Homepage Showcase (`index.php`)**:
   - Live Vehicle & Assembly Finder widget (Make, Model, Year, Category).
   - Metrics counter (12,500+ Spares in Stock, 3,200+ Engines Sold).
   - Component Assembly Grid (8 critical groups with automotive icons).
   - Featured Grade-A Stock with instant detail links.
   - 4-step import timeline: "Yokohama Yard to Sri Lankan Roads".
   - Customer and garage workshop testimonials.

3. **Filterable Parts Directory (`products.php`)**:
   - Multi-attribute sidebar filtering by Vehicle Make (Toyota, Nissan, Honda, etc.), Component Group, Quality Grade, and Real-time Price Slider.
   - Quick Add to Cart via AJAX with notification toasts.

4. **Detailed Technical Product Page (`product-details.php`)**:
   - Interactive image gallery with thumbnails.
   - Verified cylinder compression figures (e.g. `12.8 bar across all cylinders`).
   - Verified Japanese yard mileage and chassis compatibility lists.
   - 90-day replacement warranty guarantee.
   - "You May Also Like" related parts recommendations.

5. **Shopping Cart & Checkout (`cart.php` & `checkout.php`)**:
   - Real-time quantity steppers (- / +), item removal, and subtotal calculation.
   - Delivery details with workshop landmark and crane/hoist notes.
   - Payment selection (Cash on Delivery, Bank Transfer, Online Card).
   - Stock auto-decrement upon order placement.

6. **Customer Tracking (`orders.php` & `profile.php`)**:
   - Order history with live dispatch status tags (`Pending`, `Processing`, `Shipped`, `Delivered`).
   - Workshop address and phone profile updates.

7. **Admin Operations Management (`admin/`)**:
   - **Dashboard**: Revenue KPI, total orders, critical low stock alerts, registered garages.
   - **Products**: Full CRUD (Add new part modal, Edit part, Delete part, Feature toggle).
   - **Orders**: Status update dropdown to progress orders from Pending to Delivered.
   - **Inventory**: Stock controller with quick `+` and `-` unit adjustments and low-stock indicators.
   - **Customers**: Workshop directory with contact numbers and purchase counts.
