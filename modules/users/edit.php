<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $_SESSION['error'] = 'User not found.';
    header('Location: index.php');
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

$locations = $conn->query("SELECT * FROM locations WHERE is_active = 1");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $role = sanitize_input($_POST['role']);
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $new_password = $_POST['new_password'];
    
    $errors = [];
    
    if (empty($full_name) || empty($email)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, location_id = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssisi", $full_name, $email, $phone, $role, $location_id, $hashed_password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, location_id = ? WHERE id = ?");
            $stmt->bind_param("sssiii", $full_name, $email, $phone, $role, $location_id, $id);
        }
        
        if ($stmt->execute()) {
            log_transaction($conn, 'UPDATE', 'users', $id, "Updated user: {$user['username']}");
            $_SESSION['success'] = 'User updated successfully.';
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Failed to update user.';
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Edit User';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo escape_output($user['username']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="role" class="required">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="Staff" <?php echo $user['role'] == 'Staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="Manager" <?php echo $user['role'] == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="Admin" <?php echo $user['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="full_name" class="required">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo escape_output($user['full_name']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo escape_output($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo escape_output($user['phone']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="location_id">Location</label>
                <select id="location_id" name="location_id" class="form-control">
                    <option value="">Select Location</option>
                    <?php while ($location = $locations->fetch_assoc()): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo $user['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                        <?php echo escape_output($location['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <hr>
            <h3>Change Password</h3>
            <p class="text-muted">Leave blank to keep current password</p>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
