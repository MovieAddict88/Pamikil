<?php
require_once 'config.php';

$search = $_GET['search'] ?? '';
$body_type = $_GET['body_type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$where = ["status = 'Available'"];
if ($search) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where[] = "(make LIKE '%$search_escaped%' OR model LIKE '%$search_escaped%' OR year LIKE '%$search_escaped%')";
}
if ($body_type) {
    $where[] = "body_type = '" . mysqli_real_escape_string($conn, $body_type) . "'";
}
if ($min_price) {
    $where[] = "price >= " . floatval($min_price);
}
if ($max_price) {
    $where[] = "price <= " . floatval($max_price);
}

$where_clause = 'WHERE ' . implode(' AND ', $where);
$cars = mysqli_query($conn, "SELECT * FROM cars $where_clause ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Premium Car Dealership</title>
    <meta name="description" content="Find your dream car at <?php echo SITE_NAME; ?>. Browse our extensive collection of quality vehicles.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="home.php" class="navbar-brand">
                    <i class="fas fa-car"></i>
                    <?php echo SITE_NAME; ?>
                </a>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="navbar-menu" id="navbarMenu">
                    <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#inventory"><i class="fas fa-car"></i> Inventory</a></li>
                    <li><a href="#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="#contact"><i class="fas fa-envelope"></i> Contact</a></li>
                    <li><a href="login.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Admin</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <section class="hero">
        <div class="container">
            <h1>Find Your Dream Car</h1>
            <p>Browse our extensive collection of premium vehicles</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="#inventory" class="btn btn-primary" style="background: white; color: var(--primary);">
                    <i class="fas fa-search"></i> Browse Inventory
                </a>
                <button onclick="showInstallPrompt()" class="btn btn-outline" id="installBtn" style="display: none; border-color: white; color: white;">
                    <i class="fas fa-download"></i> Install App
                </button>
            </div>
        </div>
    </section>
    
    <section class="car-grid" id="inventory">
        <div class="container">
            <div class="card" style="margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1rem;">Search & Filter</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Search by make, model, year..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                        </div>
                        <div class="filter-group">
                            <select name="body_type" class="form-control">
                                <option value="">All Body Types</option>
                                <option value="Sedan" <?php echo $body_type === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                                <option value="SUV" <?php echo $body_type === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                                <option value="Truck" <?php echo $body_type === 'Truck' ? 'selected' : ''; ?>>Truck</option>
                                <option value="Van" <?php echo $body_type === 'Van' ? 'selected' : ''; ?>>Van</option>
                                <option value="Coupe" <?php echo $body_type === 'Coupe' ? 'selected' : ''; ?>>Coupe</option>
                                <option value="Hatchback" <?php echo $body_type === 'Hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <input type="number" name="min_price" placeholder="Min Price" value="<?php echo htmlspecialchars($min_price); ?>" class="form-control">
                        </div>
                        <div class="filter-group">
                            <input type="number" name="max_price" placeholder="Max Price" value="<?php echo htmlspecialchars($max_price); ?>" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="home.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <h2 style="margin-bottom: 1.5rem;">Available Cars</h2>
            
            <div class="grid grid-3">
                <?php if (mysqli_num_rows($cars) > 0): ?>
                    <?php while ($car = mysqli_fetch_assoc($cars)): ?>
                        <div class="car-card">
                            <img src="<?php echo htmlspecialchars($car['image']); ?>" alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" class="car-image">
                            <div class="car-info">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?></h3>
                                <div class="car-price"><?php echo formatCurrency($car['price']); ?></div>
                                
                                <div class="car-details">
                                    <div class="car-detail">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <?php echo number_format($car['mileage']); ?> km
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-gas-pump"></i>
                                        <?php echo $car['fuel_type']; ?>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-cogs"></i>
                                        <?php echo $car['transmission']; ?>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-car-side"></i>
                                        <?php echo $car['body_type']; ?>
                                    </div>
                                </div>
                                
                                <p style="color: var(--secondary); font-size: 0.875rem; margin: 0.75rem 0;">
                                    <?php echo htmlspecialchars(substr($car['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="car-actions">
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a>
                                    <button onclick="openInquiryModal(<?php echo $car['id']; ?>, '<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>')" class="btn btn-outline">
                                        <i class="fas fa-envelope"></i> Inquire
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1;">
                        <div class="empty-state">
                            <i class="fas fa-car"></i>
                            <p>No cars match your search criteria</p>
                            <a href="home.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-sync"></i> View All Cars
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <section id="about" style="padding: clamp(3rem, 6vw, 5rem) 0; background: white;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 1rem;">About <?php echo SITE_NAME; ?></h2>
            <p style="text-align: center; max-width: 800px; margin: 0 auto 3rem; color: var(--secondary); font-size: clamp(1rem, 2vw, 1.125rem);">
                Your trusted partner in finding the perfect vehicle. We offer a wide selection of quality cars with excellent service.
            </p>
            
            <div class="grid grid-3">
                <div class="card" style="text-align: center;">
                    <div style="font-size: clamp(2rem, 4vw, 3rem); color: var(--primary); margin-bottom: 1rem;">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Quality Assured</h3>
                    <p style="color: var(--secondary);">All vehicles undergo thorough inspection</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: clamp(2rem, 4vw, 3rem); color: var(--primary); margin-bottom: 1rem;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Best Deals</h3>
                    <p style="color: var(--secondary);">Competitive pricing and financing options</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: clamp(2rem, 4vw, 3rem); color: var(--primary); margin-bottom: 1rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Expert Support</h3>
                    <p style="color: var(--secondary);">Dedicated team to assist you</p>
                </div>
            </div>
        </div>
    </section>
    
    <section id="contact" style="padding: clamp(3rem, 6vw, 5rem) 0;">
        <div class="container">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <h2 style="text-align: center; margin-bottom: 1rem;">Contact Us</h2>
                <p style="text-align: center; color: var(--secondary); margin-bottom: 2rem;">
                    Have questions? We're here to help!
                </p>
                
                <form action="submit-inquiry.php" method="POST">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" id="phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message"><i class="fas fa-comment"></i> Message</label>
                        <textarea name="message" id="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Your trusted partner in finding the perfect vehicle.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="home.php">Home</a>
                    <a href="#inventory">Inventory</a>
                    <a href="#about">About</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-phone"></i> +63 123 456 7890</p>
                    <p><i class="fas fa-envelope"></i> info@drivehub.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div style="display: flex; gap: 1rem; font-size: 1.5rem;">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inquire About <span id="carName"></span></h3>
                <button class="close-modal" onclick="closeInquiryModal()"><i class="fas fa-times"></i></button>
            </div>
            <form action="submit-inquiry.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="car_id" id="inquiry_car_id">
                    <div class="form-group">
                        <label for="inquiry_name"><i class="fas fa-user"></i> Name</label>
                        <input type="text" name="name" id="inquiry_name" required>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="inquiry_email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" id="inquiry_email" required>
                        </div>
                        <div class="form-group">
                            <label for="inquiry_phone"><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" id="inquiry_phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inquiry_message"><i class="fas fa-comment"></i> Message</label>
                        <textarea name="message" id="inquiry_message" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeInquiryModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Inquiry</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="install-banner" id="installBanner">
        <div class="install-banner-content">
            <i class="fas fa-mobile-alt"></i>
            <div>
                <strong>Install <?php echo SITE_NAME; ?> App</strong>
                <p style="font-size: 0.875rem; margin: 0;">Get quick access to our inventory</p>
            </div>
        </div>
        <div class="install-banner-buttons">
            <button onclick="installPWA()" class="btn btn-primary btn-sm">Install</button>
            <button onclick="dismissInstallBanner()" class="btn btn-secondary btn-sm">Later</button>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
