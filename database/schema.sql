-- Car Management System Database Schema
-- Compatible with MySQL 5.x and above

-- Drop tables if they exist (for clean installation)
DROP TABLE IF EXISTS `transaction_logs`;
DROP TABLE IF EXISTS `communication_logs`;
DROP TABLE IF EXISTS `service_history`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `locations`;

-- Locations Table
CREATE TABLE `locations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table (Authentication & Role Management)
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20),
  `role` ENUM('Admin', 'Manager', 'Staff') NOT NULL DEFAULT 'Staff',
  `location_id` INT(11),
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_role` (`role`),
  KEY `idx_location` (`location_id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers Table
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50),
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100),
  `phone` VARCHAR(20) NOT NULL,
  `alternate_phone` VARCHAR(20),
  `address` TEXT,
  `city` VARCHAR(50),
  `province` VARCHAR(50),
  `postal_code` VARCHAR(10),
  `id_type` VARCHAR(50),
  `id_number` VARCHAR(50),
  `notes` TEXT,
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`last_name`, `first_name`),
  KEY `idx_phone` (`phone`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vehicles Table
CREATE TABLE `vehicles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `stock_number` VARCHAR(50) NOT NULL UNIQUE,
  `make` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `year` INT(4) NOT NULL,
  `color` VARCHAR(30),
  `vin` VARCHAR(17),
  `plate_number` VARCHAR(20),
  `engine_number` VARCHAR(50),
  `transmission` ENUM('Manual', 'Automatic', 'CVT') DEFAULT 'Manual',
  `fuel_type` ENUM('Gasoline', 'Diesel', 'Hybrid', 'Electric') DEFAULT 'Gasoline',
  `mileage` INT(11) DEFAULT 0,
  `purchase_price` DECIMAL(12,2) DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL,
  `status` ENUM('Available', 'Sold', 'Reserved', 'In Service', 'Inactive') DEFAULT 'Available',
  `location_id` INT(11),
  `description` TEXT,
  `features` TEXT,
  `image_main` VARCHAR(255),
  `image_1` VARCHAR(255),
  `image_2` VARCHAR(255),
  `image_3` VARCHAR(255),
  `image_4` VARCHAR(255),
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock` (`stock_number`),
  KEY `idx_status` (`status`),
  KEY `idx_make_model` (`make`, `model`),
  KEY `idx_location` (`location_id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales Table
CREATE TABLE `sales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `vehicle_id` INT(11) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `sale_price` DECIMAL(12,2) NOT NULL,
  `down_payment` DECIMAL(12,2) DEFAULT 0.00,
  `balance` DECIMAL(12,2) DEFAULT 0.00,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Check', 'Financing', 'Installment') DEFAULT 'Cash',
  `payment_status` ENUM('Paid', 'Partial', 'Pending') DEFAULT 'Pending',
  `sale_date` DATE NOT NULL,
  `delivery_date` DATE,
  `warranty_expiry` DATE,
  `notes` TEXT,
  `discount` DECIMAL(12,2) DEFAULT 0.00,
  `tax` DECIMAL(12,2) DEFAULT 0.00,
  `commission` DECIMAL(12,2) DEFAULT 0.00,
  `sold_by` INT(11),
  `location_id` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_number`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`sale_date`),
  KEY `idx_status` (`payment_status`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`sold_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service History Table
CREATE TABLE `service_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `service_number` VARCHAR(50) NOT NULL UNIQUE,
  `vehicle_id` INT(11) NOT NULL,
  `customer_id` INT(11),
  `service_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `service_date` DATE NOT NULL,
  `scheduled_date` DATE,
  `completion_date` DATE,
  `status` ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `payment_status` ENUM('Paid', 'Pending', 'Cancelled') DEFAULT 'Pending',
  `technician` VARCHAR(100),
  `notes` TEXT,
  `created_by` INT(11),
  `location_id` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_number` (`service_number`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`service_date`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Communication Logs Table
CREATE TABLE `communication_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `user_id` INT(11),
  `communication_type` ENUM('Phone', 'Email', 'SMS', 'In-Person', 'Other') NOT NULL,
  `subject` VARCHAR(200),
  `message` TEXT NOT NULL,
  `communication_date` DATETIME NOT NULL,
  `follow_up_date` DATE,
  `status` ENUM('Completed', 'Pending', 'Follow-up Required') DEFAULT 'Completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`communication_date`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction Logs Table (Audit Trail)
CREATE TABLE `transaction_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11),
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT(11),
  `details` TEXT,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_table` (`table_name`),
  KEY `idx_date` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default location
INSERT INTO `locations` (`name`, `address`, `phone`) VALUES
('Main Branch', 'Manila, Metro Manila, Philippines', '+63 917 123 4567');

-- Insert default admin user
-- Password: admin123 (hashed with password_hash)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `location_id`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@carmanagement.ph', 'Admin', 1);

-- Insert sample data for demo purposes
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`, `location_id`) VALUES
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', 'juan.delacruz@carmanagement.ph', '+63 917 234 5678', 'Manager', 1),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos', 'maria.santos@carmanagement.ph', '+63 918 345 6789', 'Staff', 1);

-- Insert sample customers
INSERT INTO `customers` (`first_name`, `middle_name`, `last_name`, `email`, `phone`, `address`, `city`, `province`, `created_by`) VALUES
('Pedro', 'Garcia', 'Reyes', 'pedro.reyes@email.com', '+63 917 111 2222', '123 Rizal Street, Barangay San Jose', 'Manila', 'Metro Manila', 1),
('Ana', 'Lopez', 'Cruz', 'ana.cruz@email.com', '+63 918 222 3333', '456 Bonifacio Avenue, Barangay Poblacion', 'Quezon City', 'Metro Manila', 1),
('Carlos', 'Miguel', 'Fernandez', 'carlos.fernandez@email.com', '+63 919 333 4444', '789 Aguinaldo Highway', 'Cavite City', 'Cavite', 1);

-- Insert sample vehicles
INSERT INTO `vehicles` (`stock_number`, `make`, `model`, `year`, `color`, `transmission`, `fuel_type`, `mileage`, `purchase_price`, `selling_price`, `status`, `location_id`, `description`, `created_by`) VALUES
('VH-2024-001', 'Toyota', 'Vios', 2023, 'White', 'Automatic', 'Gasoline', 5000, 650000.00, 750000.00, 'Available', 1, 'Well-maintained, single owner, complete papers', 1),
('VH-2024-002', 'Honda', 'City', 2022, 'Silver', 'Automatic', 'Gasoline', 15000, 600000.00, 700000.00, 'Available', 1, 'Low mileage, excellent condition', 1),
('VH-2024-003', 'Mitsubishi', 'Montero Sport', 2021, 'Black', 'Automatic', 'Diesel', 35000, 1200000.00, 1400000.00, 'Available', 1, '4x4, leather seats, sunroof', 1),
('VH-2024-004', 'Ford', 'Ranger', 2020, 'Red', 'Manual', 'Diesel', 50000, 800000.00, 950000.00, 'Sold', 1, 'Pickup truck, heavy duty', 1),
('VH-2024-005', 'Hyundai', 'Tucson', 2023, 'Blue', 'Automatic', 'Gasoline', 8000, 1100000.00, 1250000.00, 'Reserved', 1, 'SUV, premium features', 1);

-- Insert sample sale
INSERT INTO `sales` (`invoice_number`, `vehicle_id`, `customer_id`, `sale_price`, `down_payment`, `balance`, `payment_method`, `payment_status`, `sale_date`, `sold_by`, `location_id`) VALUES
('INV-2024-0001', 4, 1, 950000.00, 200000.00, 750000.00, 'Financing', 'Partial', '2024-01-15', 2, 1);

-- Update vehicle status after sale
UPDATE `vehicles` SET `status` = 'Sold' WHERE `id` = 4;

-- Insert sample service records
INSERT INTO `service_history` (`service_number`, `vehicle_id`, `customer_id`, `service_type`, `description`, `service_date`, `status`, `cost`, `payment_status`, `technician`, `created_by`, `location_id`) VALUES
('SVC-2024-001', 1, NULL, 'Preventive Maintenance', 'Oil change, filter replacement, general checkup', '2024-01-20', 'Completed', 3500.00, 'Paid', 'Technician Jose Ramirez', 1, 1),
('SVC-2024-002', 2, 2, 'Repair', 'Brake pad replacement', '2024-01-25', 'Scheduled', 8000.00, 'Pending', 'Technician Maria Gomez', 1, 1),
('SVC-2024-003', 3, NULL, 'Inspection', 'Pre-sale inspection and detailing', '2024-01-28', 'In Progress', 5000.00, 'Pending', 'Technician Roberto Aquino', 1, 1);

-- Insert sample communication logs
INSERT INTO `communication_logs` (`customer_id`, `user_id`, `communication_type`, `subject`, `message`, `communication_date`, `status`) VALUES
(1, 2, 'Phone', 'Follow-up on payment', 'Called customer regarding monthly payment due date', '2024-02-01 10:30:00', 'Completed'),
(2, 3, 'Email', 'Service appointment confirmation', 'Sent confirmation email for brake service appointment', '2024-01-24 14:00:00', 'Completed'),
(3, 2, 'In-Person', 'Vehicle inquiry', 'Customer visited showroom to inquire about available SUVs', '2024-02-02 16:45:00', 'Follow-up Required');
