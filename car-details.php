<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
$car = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cars WHERE id = $id"));

if (!$car) {
    header('Location: home.php');
    exit();
}

$features = $car['features'] ? explode(',', $car['features']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['year'].' '.$car['make'].' '.$car['model']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="home.php" class="navbar-brand">
                    <i class="fas fa-car"></i> <?php echo SITE_NAME; ?>
                </a>
                <a href="home.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container" style="padding: clamp(2rem, 4vw, 3rem) clamp(1rem, 3vw, 2rem);">
        <div class="grid grid-2">
            <div>
                <img src="<?php echo htmlspecialchars($car['image']); ?>" alt="<?php echo htmlspecialchars($car['make'].' '.$car['model']); ?>" 
                     style="width: 100%; border-radius: 1rem; box-shadow: 0 4px 12px var(--shadow);">
            </div>
            
            <div>
                <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($car['year'].' '.$car['make'].' '.$car['model']); ?></h1>
                <div style="margin-bottom: 1rem;">
                    <span class="badge badge-<?php echo $car['status']==='Available'?'success':'warning'; ?>">
                        <?php echo $car['status']; ?>
                    </span>
                </div>
                <div class="car-price" style="margin-bottom: 1.5rem;"><?php echo formatCurrency($car['price']); ?></div>
                
                <p style="color: var(--secondary); margin-bottom: 2rem; font-size: clamp(0.875rem, 2vw, 1rem);">
                    <?php echo htmlspecialchars($car['description']); ?>
                </p>
                
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <a href="test-drive.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-key"></i> Schedule Test Drive
                    </a>
                    <button onclick="openInquiry()" class="btn btn-outline">
                        <i class="fas fa-envelope"></i> Send Inquiry
                    </button>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom: 1rem;">Specifications</h3>
                    <div class="grid grid-2" style="gap: 1rem;">
                        <div><strong>Make:</strong> <?php echo htmlspecialchars($car['make']); ?></div>
                        <div><strong>Model:</strong> <?php echo htmlspecialchars($car['model']); ?></div>
                        <div><strong>Year:</strong> <?php echo $car['year']; ?></div>
                        <div><strong>Color:</strong> <?php echo htmlspecialchars($car['color']); ?></div>
                        <div><strong>Mileage:</strong> <?php echo number_format($car['mileage']); ?> km</div>
                        <div><strong>Body Type:</strong> <?php echo $car['body_type']; ?></div>
                        <div><strong>Fuel Type:</strong> <?php echo $car['fuel_type']; ?></div>
                        <div><strong>Transmission:</strong> <?php echo $car['transmission']; ?></div>
                        <div><strong>Doors:</strong> <?php echo $car['doors']; ?></div>
                        <div><strong>Seats:</strong> <?php echo $car['seats']; ?></div>
                        <div><strong>Engine:</strong> <?php echo htmlspecialchars($car['engine_size']); ?></div>
                        <div><strong>Horsepower:</strong> <?php echo $car['horsepower']; ?> HP</div>
                        <div><strong>VIN:</strong> <?php echo htmlspecialchars($car['vin']); ?></div>
                        <div><strong>License:</strong> <?php echo htmlspecialchars($car['license_plate']); ?></div>
                    </div>
                </div>
                
                <?php if (count($features) > 0): ?>
                <div class="card" style="margin-top: 1rem;">
                    <h3 style="margin-bottom: 1rem;">Features</h3>
                    <div class="grid grid-2" style="gap: 0.5rem;">
                        <?php foreach ($features as $feature): ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                <span><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inquire About This Car</h3>
                <button class="close-modal" onclick="closeInquiry()"><i class="fas fa-times"></i></button>
            </div>
            <form action="submit-inquiry.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Message</label>
                        <textarea name="message" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeInquiry()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openInquiry() { document.getElementById('inquiryModal').classList.add('active'); }
        function closeInquiry() { document.getElementById('inquiryModal').classList.remove('active'); }
        document.getElementById('inquiryModal').addEventListener('click', e => { if (e.target.id === 'inquiryModal') closeInquiry(); });
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
