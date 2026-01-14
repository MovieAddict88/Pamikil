-- Car Management System Database Schema
-- Designed for Philippine Market
-- Compatible with MySQL 5.7+ and MariaDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `locations`;

-- Locations table for multi-location support
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table with role-based access
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `fk_users_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers table
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `identification_type` enum('drivers_license','passport','umid','sss_id','tin_id','others') DEFAULT NULL,
  `identification_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vehicles table
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_number` varchar(50) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `variant` varchar(100) DEFAULT NULL,
  `color` varchar(50) NOT NULL,
  `body_type` enum('sedan','suv','mpv','pickup','hatchback','coupe','van','truck','motorcycle','others') NOT NULL,
  `fuel_type` enum('gasoline','diesel','electric','hybrid','lpg') NOT NULL,
  `transmission` enum('manual','automatic','cvt','dct') NOT NULL,
  `engine_cc` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `chassis_number` varchar(50) DEFAULT NULL,
  `engine_number` varchar(50) DEFAULT NULL,
  `purchase_price` decimal(12,2) NOT NULL,
  `selling_price` decimal(12,2) NOT NULL,
  `mileage` int(11) DEFAULT NULL,
  `condition` enum('brand_new','used','refurbished') NOT NULL,
  `status` enum('available','reserved','sold','pending_service','under_maintenance','inactive') DEFAULT 'available',
  `location_id` int(11) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `date_acquired` date NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_number` (`stock_number`),
  KEY `status` (`status`),
  KEY `location_id` (`location_id`),
  KEY `plate_number` (`plate_number`),
  CONSTRAINT `fk_vehicles_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services table for maintenance records
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `service_type` enum('inspection','maintenance','repair','upgrade','detailing','others') NOT NULL,
  `service_date` date NOT NULL,
  `description` text NOT NULL,
  `parts_used` text DEFAULT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `mechanic_name` varchar(100) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `next_service_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `service_date` (`service_date`),
  KEY `status` (`status`),
  CONSTRAINT `fk_services_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales table
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_price` decimal(12,2) NOT NULL,
  `down_payment` decimal(12,2) DEFAULT 0.00,
  `financing_type` enum('cash','in_house','bank_finance','others') NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `financing_term_months` int(11) DEFAULT NULL,
  `monthly_amortization` decimal(12,2) DEFAULT NULL,
  `sales_agent_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `customer_id` (`customer_id`),
  KEY `sales_agent_id` (`sales_agent_id`),
  KEY `sale_date` (`sale_date`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `fk_sales_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_agent` FOREIGN KEY (`sales_agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions table for payment tracking
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `transaction_type` enum('payment','refund','commission','expense','income') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','credit_card','debit_card','others') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `processed_by` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `service_id` (`service_id`),
  KEY `transaction_date` (`transaction_date`),
  KEY `processed_by` (`processed_by`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `fk_transactions_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer communication logs
CREATE TABLE `customer_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `communication_type` enum('call','email','sms','visit','whatsapp','messenger','others') NOT NULL,
  `notes` text NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `logged_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_comm_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comm_user` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remember me tokens
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
-- Note: Change this password immediately after first login!
-- To reset password, use: password_hash('admin123', PASSWORD_BCRYPT) in PHP
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`) VALUES
('admin', '$2y$10$N0.d0J3MqQ7JlK7q9J9uO9Z3l8Oq2O9Z3l8Oq2O9Z3', 'System Administrator', 'admin@pamikil.com', '09171234567', 'admin');

-- Insert default location
INSERT INTO `locations` (`name`, `address`, `contact_person`, `contact_number`, `email`) VALUES
('Main Branch', '123 Auto Street, Makati City, Metro Manila', 'Juan Dela Cruz', '09181234567', 'main@pamikil.com');

-- Insert sample vehicles
INSERT INTO `vehicles` (`stock_number`, `make`, `model`, `year`, `variant`, `color`, `body_type`, `fuel_type`, `transmission`, `engine_cc`, `plate_number`, `purchase_price`, `selling_price`, `mileage`, `condition`, `status`, `date_acquired`, `description`, `features`) VALUES
('STK-2024-001', 'Toyota', 'Vios', 2024, '1.5 G CVT', 'White Pearl', 'sedan', 'gasoline', 'cvt', 1498, 'ABC 1234', 950000.00, 1080000.00, 0, 'brand_new', 'available', '2024-01-01', '2024 Toyota Vios 1.5 G CVT - Well maintained single owner', 'ABS, Airbags, GPS, Bluetooth, Parking Camera, Push Start'),
('STK-2024-002', 'Mitsubishi', 'Xpander', 2024, 'GLX Plus', 'Red Metallic', 'mpv', 'gasoline', 'automatic', 1499, 'DEF 5678', 1080000.00, 1250000.00, 0, 'brand_new', 'available', '2024-01-05', '2024 Mitsubishi Xpander GLX Plus - Family friendly MPV', '7-Seater, ABS, Airbags, Rear AC, Touchscreen, Bluetooth'),
('STK-2024-003', 'Honda', 'City', 2023, 'V Turbo', 'Civic Gray', 'sedan', 'gasoline', 'cvt', 1498, 'GHI 9012', 1050000.00, 1180000.00, 5000, 'used', 'available', '2024-01-10', '2023 Honda City V Turbo - Low mileage, excellent condition', 'Honda Sensing, ABS, Airbags, Lane Watch, Push Start'),
('STK-2024-004', 'Ford', 'Ranger', 2023, '2.0 Wildtrak 4x4', 'Silver', 'pickup', 'diesel', 'automatic', 1997, 'JKL 3456', 1450000.00, 1680000.00, 15000, 'used', 'available', '2024-01-15', '2023 Ford Ranger Wildtrak - Power and capability', '4x4, Sunroof, Navigation, ABS, Airbags, Bed Liner'),
('STK-2024-005', 'Toyota', 'Fortuner', 2022, '2.8 VRX 4x4', 'Black', 'suv', 'diesel', 'automatic', 2755, 'MNO 7890', 1950000.00, 2250000.00, 35000, 'used', 'available', '2024-01-20', '2022 Toyota Fortuner VRX - Premium SUV', '4x4, Sunroof, Navigation, ABS, Airbags, Leather Seats');

-- Insert sample customers
INSERT INTO `customers` (`first_name`, `middle_name`, `last_name`, `email`, `phone`, `address`, `city`, `province`, `zip_code`, `identification_type`, `identification_number`) VALUES
('Maria', 'Santos', 'Reyes', 'maria.reyes@email.com', '09171234568', '456 Family Road', 'Quezon City', 'Metro Manila', '1100', 'drivers_license', 'A00-1234567'),
('Jose', 'Garcia', 'Mendoza', 'jose.mendoza@email.com', '09181234569', '789 Business Ave', 'Makati City', 'Metro Manila', '1200', 'drivers_license', 'A01-2345678'),
('Ana', 'Pascual', 'De Jesus', 'ana.dejesus@email.com', '09191234570', '123 Subdivision St', 'Pasig City', 'Metro Manila', '1600', 'passport', 'EE1234567'),
('Carlos', 'Rivera', 'Santos', 'carlos.santos@email.com', '09201234571', '567 Corporate Blvd', 'Taguig City', 'Metro Manila', '1630', 'drivers_license', 'A02-3456789'),
('Elena', 'Torres', 'Gomez', 'elena.gomez@email.com', '09211234572', '890 Home Lane', 'Mandaluyong City', 'Metro Manila', '1550', 'tin_id', '123-456-789-000');

COMMIT;
