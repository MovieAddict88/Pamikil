<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $middle_name = sanitize_input($_POST['middle_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $alternate_phone = sanitize_input($_POST['alternate_phone']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $province = sanitize_input($_POST['province']);
    $postal_code = sanitize_input($_POST['postal_code']);
    $id_type = sanitize_input($_POST['id_type']);
    $id_number = sanitize_input($_POST['id_number']);
    $notes = sanitize_input($_POST['notes']);
    
    $errors = [];
    
    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $errors[] = 'First name, last name, and phone are required.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO customers (first_name, middle_name, last_name, email, phone, alternate_phone, address, city, province, postal_code, id_type, id_number, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("sssssssssssssi", $first_name, $middle_name, $last_name, $email, $phone, $alternate_phone, $address, $city, $province, $postal_code, $id_type, $id_number, $notes, $user_id);
        
        if ($stmt->execute()) {
            $customer_id = $stmt->insert_id();
            log_transaction($conn, 'CREATE', 'customers', $customer_id, "Added customer: {$first_name} {$last_name}");
            $_SESSION['success'] = 'Customer added successfully.';
            header('Location: view.php?id=' . $customer_id);
            exit();
        } else {
            $errors[] = 'Failed to add customer.';
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Add Customer';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-plus"></i> Add Customer</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate">
            <h3>Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="required">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="required">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
            </div>
            
            <h3 class="mt-3">Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone" class="required">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+63 917 123 4567" required>
                </div>
                
                <div class="form-group">
                    <label for="alternate_phone">Alternate Phone</label>
                    <input type="tel" id="alternate_phone" name="alternate_phone" class="form-control" placeholder="+63 917 123 4567">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
            </div>
            
            <h3 class="mt-3">Address</h3>
            <div class="form-group">
                <label for="address">Street Address</label>
                <textarea id="address" name="address" class="form-control" rows="2"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control">
                </div>
            </div>
            
            <h3 class="mt-3">Identification</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="id_type">ID Type</label>
                    <select id="id_type" name="id_type" class="form-control">
                        <option value="">Select ID Type</option>
                        <option value="Driver's License">Driver's License</option>
                        <option value="SSS">SSS</option>
                        <option value="UMID">UMID</option>
                        <option value="Passport">Passport</option>
                        <option value="PRC ID">PRC ID</option>
                        <option value="Postal ID">Postal ID</option>
                        <option value="Voter's ID">Voter's ID</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input type="text" id="id_number" name="id_number" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Additional information about the customer"></textarea>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Customer
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
