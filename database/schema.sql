-- database/schema.sql
-- JDM Japanese Reconditioned Auto Parts Database Schema

CREATE DATABASE IF NOT EXISTS `jdm_autoparts` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jdm_autoparts`;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT 'Colombo',
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `categories`
-- -----------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'engine',
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `brands`
-- -----------------------------------------------------
CREATE TABLE `brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `country` VARCHAR(50) DEFAULT 'Japan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `products`
-- -----------------------------------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(60) NOT NULL UNIQUE,
  `category_id` INT UNSIGNED NOT NULL,
  `brand_id` INT UNSIGNED NOT NULL,
  `model_compatibility` VARCHAR(255) NOT NULL,
  `year_from` INT DEFAULT 2002,
  `year_to` INT DEFAULT 2018,
  `grade` VARCHAR(20) DEFAULT 'Grade A',
  `mileage` VARCHAR(50) DEFAULT '48,000 km',
  `compression` VARCHAR(80) DEFAULT '12.8 bar across all cylinders',
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT NOT NULL DEFAULT 5,
  `image_url` VARCHAR(255) DEFAULT 'engine_1.svg',
  `description` TEXT,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `orders`
-- -----------------------------------------------------
CREATE TABLE `orders` (
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `order_items`
-- -----------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `price` DECIMAL(12, 2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `product_images` (Multi-photo Support)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Seed: Users
-- Passwords:
-- admin@jdmparts.com -> admin123 ($2y$10$eA3aU8aU/9hD... generated via password_hash('admin123', PASSWORD_DEFAULT))
-- customer@gmail.com -> customer123
-- -----------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `city`, `role`) VALUES
('JDM Admin', 'admin@jdmparts.com', '$2y$10$w85vWp8e61nlyWfX3y2Q6.7aYkC.M427v74o4Lh7H925UoZ0m6BWy', '+94 77 123 4567', '45 Yokohama Avenue, Industrial Zone', 'Colombo 10', 'admin'),
('Kasun Perera', 'customer@gmail.com', '$2y$10$w85vWp8e61nlyWfX3y2Q6.7aYkC.M427v74o4Lh7H925UoZ0m6BWy', '+94 71 987 6543', '128 Kandy Road, Kiribathgoda', 'Kelaniya', 'customer'),
('Dinesh Fernando', 'dinesh.auto@gmail.com', '$2y$10$w85vWp8e61nlyWfX3y2Q6.7aYkC.M427v74o4Lh7H925UoZ0m6BWy', '+94 76 554 4332', '88 High Level Road, Pannipitiya', 'Maharagama', 'customer');

-- -----------------------------------------------------
-- Seed: Categories
-- -----------------------------------------------------
INSERT INTO `categories` (`name`, `slug`, `icon`, `description`) VALUES
('Engine Assemblies', 'engine-assemblies', 'engine', 'Complete low-mileage tested petrol and diesel assemblies direct from Japan auctions.'),
('Transmission & Gearbox', 'transmissions', 'gearbox', 'Automatic, CVT, and 5/6-Speed Manual gearboxes with torque converters.'),
('Suspension & Steering', 'suspension', 'shock', 'JDM OEM struts, coilovers, lower arms, stabilizer bars, and steering racks.'),
('Brake Calipers & Rotors', 'braking', 'disc', 'Multi-pot calipers, ventilated rotors, brake boosters, and ABS modules.'),
('Electrical & ECUs', 'electrical', 'chip', 'OEM engine control units, sensors, wiring harnesses, alternators, and starter motors.'),
('Body Panels & Exterior', 'body-panels', 'car', 'Rust-free genuine doors, bonnets, boot lids, bumpers, and xenon headlights.'),
('Turbochargers & Intakes', 'turbo-intake', 'turbo', 'OEM & aftermarket JDM turbos, intercoolers, blow-off valves, and intake manifolds.'),
('Cooling & Radiators', 'cooling', 'radiator', 'Aluminum dual-core radiators, electric fan assemblies, and water pumps.');

-- -----------------------------------------------------
-- Seed: Brands
-- -----------------------------------------------------
INSERT INTO `brands` (`name`, `country`) VALUES
('Toyota', 'Japan'),
('Nissan', 'Japan'),
('Honda', 'Japan'),
('Mazda', 'Japan'),
('Mitsubishi', 'Japan'),
('Subaru', 'Japan'),
('Suzuki', 'Japan');

-- -----------------------------------------------------
-- Seed: Products
-- -----------------------------------------------------
INSERT INTO `products` (`name`, `sku`, `category_id`, `brand_id`, `model_compatibility`, `year_from`, `year_to`, `grade`, `mileage`, `compression`, `price`, `stock_quantity`, `image_url`, `description`, `is_featured`) VALUES
('Toyota 1NZ-FE VVT-i Engine Assembly', 'ENG-TOY-1NZ-01', 1, 1, 'Corolla NZE121/141, Allion NZT240/260, Premio, Vios, Probox', 2004, 2016, 'Grade A+', '48,200 km', '12.8 bar across all 4 cylinders', 185000.00, 4, 'engine_1.svg', 'Genuine JDM reconditioned Toyota 1NZ-FE 1.5L DOHC 16-Valve engine with VVT-i. Directly sourced from Kanagawa yard, compression verified, cold-start tested, comes complete with intake manifold, fuel injectors, alternator and throttle body.', 1),

('Nissan HR15DE Automatic CVT Transmission', 'TRN-NIS-HR15-02', 2, 2, 'Nissan Tiida C11, Wingroad Y12, Latio, Note E11', 2006, 2014, 'Grade A', '54,000 km', 'Pressure tested 5.2 bar', 95000.00, 3, 'transmission_1.svg', 'Smooth shifting XTRONIC CVT transmission pulled from a low-mileage Nissan Tiida in Chiba. Clean transmission fluid, torque converter included. 90-day replacement warranty.', 1),

('Tein Street Advance Z Coilover Damper Kit', 'SUS-HON-TEIN-03', 3, 3, 'Honda Civic FD1, FD2, FA1, Mugen RR', 2006, 2012, 'Grade A', '31,000 km', '16-level damping verified', 145000.00, 5, 'suspension_1.svg', 'Authentic Tein Japan height-adjustable twin-tube suspension system. Complete set of 4 dampers with springs, undamaged dust boots, no oil leaks. Enhances stance and cornering stability.', 1),

('Brembo Front 4-Pot Monobloc Calipers & Rotors', 'BRK-SUB-BREM-04', 4, 6, 'Subaru Impreza WRX STI GDB/GRB, Forester SG9, Legacy BL5', 2003, 2011, 'Grade A', '42,000 km', 'Piston seal integrity tested', 120000.00, 2, 'brake_1.svg', 'Factory Gold Brembo 4-pot radial mount front calipers paired with 326mm slotted rotors. Reconditioned with fresh seals and high-friction JDM brake pads. Outstanding stopping power.', 1),

('Mitsubishi 4G63T EVO IX Turbocharger TD05HR-16G6', 'TUR-MIT-4G63-05', 7, 5, 'Lancer Evolution VII, VIII, IX (CT9A)', 2003, 2008, 'Grade A+', '39,000 km', 'Zero shaft play, twin scroll balanced', 165000.00, 2, 'turbo_1.svg', 'Original titanium-aluminide turbine wheel twin-scroll TD05HR turbocharger. Direct bolt-on for CT9A chassis. Clean compressor housing, wastegate actuator holding factory 1.1 bar.', 1),

('Honda K20A Type-R Red Top Engine Assembly', 'ENG-HON-K20A-06', 1, 3, 'Integra DC5 Type-R, Civic EP3, Accord Euro-R CL7', 2002, 2006, 'Grade A+', '41,500 km', '14.1 bar balanced compression', 480000.00, 1, 'engine_2.svg', 'High-revving naturally aspirated JDM 2.0L i-VTEC DOHC engine producing 220PS. Sourced from a genuine DC5 Type-R. Pristine red valve cover, high-cam profiles verified, PRD intake manifold included.', 1),

('Toyota 2ZR-FAE Valvematic Complete Engine', 'ENG-TOY-2ZR-07', 1, 1, 'Premio ZRT260, Allion ZRT260, Wish ZGE20, Auris', 2008, 2018, 'Grade A', '51,000 km', '13.0 bar compression tested', 265000.00, 3, 'engine_1.svg', '1.8L Dual VVT-i Valvematic engine assembly. Perfect drop-in replacement for worn Sri Lankan Premio/Allion engines. Clean oil galleries, no sludge, inspected under valve cover.', 1),

('Nissan VQ35DE 3.5L V6 Engine Assembly', 'ENG-NIS-VQ35-08', 1, 2, 'Fairlady 350Z Z33, Skyline V35, Murano Z50, Teana J31', 2003, 2008, 'Grade A', '58,000 km', '12.5 bar even across 6 cylinders', 310000.00, 2, 'engine_2.svg', 'Powerful 3.5-liter V6 motor removed from a 350Z in Osaka. Complete with harness, coil packs, intake plenum, power steering pump and alternator.', 0),

('Mazda LF-DE 2.0L Engine & 4-Speed Auto Gearbox', 'ENG-MAZ-LFDE-09', 1, 4, 'Mazda 3 Axela BK5P/BKEP, Mazda 6 Atenza GG3S', 2004, 2009, 'Grade A', '62,000 km', '12.6 bar compression verified', 195000.00, 3, 'engine_1.svg', 'Complete engine and automatic gearbox package for Mazda Axela/Atenza. Sourced directly from Chiba dismantling yard. Excellent condition.', 0),

('Denso JDM High-Output Alternator & Starter Combo', 'ELC-DEN-ALT-10', 5, 1, 'Toyota Vitz, Corolla, Belta, Ractis, Passo', 2005, 2017, 'Grade A+', '25,000 km', '14.4V charging output tested', 32000.00, 8, 'electrical_1.svg', 'Original Denso 12V 90A alternator matched with rapid-crank starter motor. Bench tested under electrical load. Guaranteed trouble-free ignition and charging.', 0),

('Koyorad Full Aluminum Dual-Core Racing Radiator', 'CLG-KOY-ALU-11', 8, 3, 'Honda Civic FD2 / FD1, CR-V RE3, Stream RN6', 2006, 2014, 'Grade A+', 'New Old Stock JDM', 'Leak-down pressure tested to 2.5 bar', 42000.00, 6, 'cooling_1.svg', 'High efficiency 48mm dual-row all-aluminum core radiator. Sourced from Osaka performance warehouse. Drop-in fitment for enhanced cooling under tropical Sri Lankan heat.', 0),

('Subaru EJ20X Turbocharged Boxer Engine Assembly', 'ENG-SUB-EJ20-12', 1, 6, 'Legacy BP5/BL5 GT Spec.B, Impreza GH8', 2004, 2009, 'Grade A', '47,000 km', '11.8 bar balanced compression', 390000.00, 2, 'engine_2.svg', 'JDM Twin-scroll turbocharged 2.0L quad-cam boxer engine. Sourced from BP5 Legacy GT Spec.B. Complete with intake manifold, VF38 turbo, downpipe flange and intercooler.', 1);

-- -----------------------------------------------------
-- Seed: Orders & Order Items
-- -----------------------------------------------------
INSERT INTO `orders` (`user_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `city`, `postal_code`, `payment_method`, `total_amount`, `status`, `notes`) VALUES
(2, 'Kasun Perera', 'customer@gmail.com', '+94 71 987 6543', '128 Kandy Road, Kiribathgoda', 'Kelaniya', '11600', 'cod', 185000.00, 'Delivered', 'Please deliver to garage address directly.'),
(3, 'Dinesh Fernando', 'dinesh.auto@gmail.com', '+94 76 554 4332', '88 High Level Road, Pannipitiya', 'Maharagama', '10230', 'bank_transfer', 240000.00, 'Processing', 'Payment slip sent via WhatsApp.'),
(2, 'Kasun Perera', 'customer@gmail.com', '+94 71 987 6543', '128 Kandy Road, Kiribathgoda', 'Kelaniya', '11600', 'cod', 145000.00, 'Pending', 'Confirm delivery before dispatching.');

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 'Toyota 1NZ-FE VVT-i Engine Assembly', 185000.00, 1, 185000.00),
(2, 4, 'Brembo Front 4-Pot Monobloc Calipers & Rotors', 120000.00, 2, 240000.00),
(3, 3, 'Tein Street Advance Z Coilover Damper Kit', 145000.00, 1, 145000.00);
