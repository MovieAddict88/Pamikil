CREATE DATABASE IF NOT EXISTS car_management;
USE car_management;

CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(30) NOT NULL,
    vin VARCHAR(17) UNIQUE NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    mileage INT NOT NULL,
    fuel_type ENUM('Gasoline', 'Diesel', 'Electric', 'Hybrid') NOT NULL,
    transmission ENUM('Manual', 'Automatic', 'CVT', 'Semi-Automatic') NOT NULL,
    body_type ENUM('Sedan', 'SUV', 'Truck', 'Van', 'Coupe', 'Hatchback', 'Convertible') NOT NULL,
    doors INT NOT NULL,
    seats INT NOT NULL,
    engine_size VARCHAR(20),
    horsepower INT,
    description TEXT,
    features TEXT,
    status ENUM('Available', 'Sold', 'Reserved', 'Maintenance') DEFAULT 'Available',
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    province VARCHAR(50) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    date_of_birth DATE,
    license_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_id INT NOT NULL,
    customer_id INT NOT NULL,
    sale_price DECIMAL(12, 2) NOT NULL,
    sale_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Financing', 'Credit Card') NOT NULL,
    payment_status ENUM('Paid', 'Partial', 'Pending') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS maintenance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    service_provider VARCHAR(100),
    status ENUM('Completed', 'Pending', 'In Progress') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New', 'Contacted', 'Closed') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS test_drives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, password, email, full_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@drivehub.com', 'System Administrator');

-- Sample data for cars
INSERT INTO cars (make, model, year, color, vin, license_plate, price, mileage, fuel_type, transmission, body_type, doors, seats, engine_size, horsepower, description, features, status, image) VALUES
('Toyota', 'Vios', 2023, 'White Pearl', '1HGCM82633A123456', 'ABC1234', 850000.00, 5000, 'Gasoline', 'Automatic', 'Sedan', 4, 5, '1.3L', 98, 'Brand new Toyota Vios with low mileage. Perfect for city driving.', 'ABS, Airbags, Power Steering, Air Conditioning, Audio System', 'Available', 'https://images.unsplash.com/photo-1621839673705-6617adf9e890?w=400'),
('Honda', 'CR-V', 2022, 'Modern Steel', '2HGCM82633A789012', 'XYZ5678', 1650000.00, 15000, 'Gasoline', 'CVT', 'SUV', 4, 7, '1.5L Turbo', 190, 'Spacious Honda CR-V SUV with 7-seater capacity. Excellent condition.', 'Leather Seats, Sunroof, Backup Camera, Honda Sensing, Keyless Entry', 'Available', 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=400'),
('Mitsubishi', 'Montero Sport', 2023, 'Titanium Gray', '3HGCM82633A345678', 'DEF9012', 2100000.00, 8000, 'Diesel', 'Automatic', 'SUV', 4, 7, '2.4L', 181, 'Powerful and reliable Mitsubishi Montero Sport. Perfect for families.', '4WD, Hill Descent Control, Leather Interior, Touchscreen Display', 'Available', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=400'),
('Nissan', 'Navara', 2022, 'Burning Red', '4HGCM82633A901234', 'GHI3456', 1350000.00, 25000, 'Diesel', 'Manual', 'Truck', 4, 5, '2.5L', 190, 'Rugged Nissan Navara pickup truck. Great for work and adventure.', 'Cargo Bed Cover, Tow Bar, Off-Road Tires, Differential Lock', 'Available', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=400'),
('Mazda', 'CX-5', 2023, 'Soul Red Crystal', '5HGCM82633A567890', 'JKL7890', 1850000.00, 3000, 'Gasoline', 'Automatic', 'SUV', 4, 5, '2.5L', 187, 'Stylish Mazda CX-5 with premium features and stunning design.', 'Bose Sound System, Head-Up Display, Adaptive Cruise Control, LED Lights', 'Reserved', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=400'),
('Ford', 'Ranger Raptor', 2022, 'Conquer Gray', '6HGCM82633A234567', 'MNO2345', 2450000.00, 18000, 'Diesel', 'Automatic', 'Truck', 4, 5, '2.0L Bi-Turbo', 213, 'High-performance Ford Ranger Raptor. Off-road beast.', 'Fox Shocks, Terrain Management, Sport Bar, All-Terrain Tires', 'Available', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=400'),
('Hyundai', 'Tucson', 2023, 'Phantom Black', '7HGCM82633A890123', 'PQR6789', 1550000.00, 7000, 'Hybrid', 'Automatic', 'SUV', 4, 5, '1.6L Turbo', 230, 'Eco-friendly Hyundai Tucson Hybrid with excellent fuel economy.', 'Panoramic Sunroof, Wireless Charging, Smart Cruise Control, 360 Camera', 'Available', 'https://images.unsplash.com/photo-1621839673705-6617adf9e890?w=400'),
('Chevrolet', 'Trailblazer', 2022, 'Summit White', '8HGCM82633A456789', 'STU0123', 1750000.00, 12000, 'Diesel', 'Automatic', 'SUV', 4, 7, '2.8L', 200, 'Spacious Chevrolet Trailblazer with powerful diesel engine.', '7-Seater, Apple CarPlay, Android Auto, Rear AC, Parking Sensors', 'Available', 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=400');
