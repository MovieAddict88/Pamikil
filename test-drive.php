<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
$car = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cars WHERE id = $id AND status = 'Available'"));

if (!$car) {
    header('Location: home.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $email = mysqli_real_escape_string($conn, $_POST['customer_email']);
    $phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $date = mysqli_real_escape_string($conn, $_POST['preferred_date']);
    $time = mysqli_real_escape_string($conn, $_POST['preferred_time']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $query = "INSERT INTO test_drives (car_id, customer_name, customer_email, customer_phone, preferred_date, preferred_time, notes) 
              VALUES ($car_id, '$name', '$email', '$phone', '$date', '$time', '$notes')";
    
    if (mysqli_query($conn, $query)) {
        $success = 'Test drive scheduled successfully! We will contact you to confirm.';
    } else {
        $error = 'Error scheduling test drive. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Test Drive - <?php echo SITE_NAME; ?></title>
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
                <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container" style="padding: clamp(2rem, 4vw, 3rem) clamp(1rem, 3vw, 2rem);">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h1 style="margin-bottom: 1rem;">Schedule Test Drive</h1>
            <p style="color: var(--secondary); margin-bottom: 2rem;">
                Book a test drive for <strong><?php echo htmlspecialchars($car['year'].' '.$car['make'].' '.$car['model']); ?></strong>
            </p>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                
                <div class="form-group">
                    <label for="customer_name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="customer_name" id="customer_name" required>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="customer_email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="customer_email" id="customer_email" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone"><i class="fas fa-phone"></i> Phone</label>
                        <input type="tel" name="customer_phone" id="customer_phone" required>
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="preferred_date"><i class="fas fa-calendar"></i> Preferred Date</label>
                        <input type="date" name="preferred_date" id="preferred_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="preferred_time"><i class="fas fa-clock"></i> Preferred Time</label>
                        <input type="time" name="preferred_time" id="preferred_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes"><i class="fas fa-sticky-note"></i> Additional Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-calendar-check"></i> Schedule Test Drive
                </button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
