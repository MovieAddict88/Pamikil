<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (!empty($new_password)) {
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
    }
    
    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $full_name;
            log_transaction($conn, 'UPDATE', 'users', $user_id, 'Profile updated');
            $_SESSION['success'] = 'Profile updated successfully.';
            header('Location: profile.php');
            exit();
        } else {
            $_SESSION['error'] = 'Failed to update profile.';
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$page_title = 'My Profile';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-edit"></i> My Profile</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo escape_output($user['username']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" class="form-control" value="<?php echo escape_output($user['role']); ?>" disabled>
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
            
            <hr style="margin: 2rem 0;">
            
            <h3>Change Password</h3>
            <p class="text-muted">Leave blank if you don't want to change your password.</p>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
                <a href="../dashboard/index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
