<?php
// Global Helper Functions

// Sanitize input data
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Escape output for display
function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Format currency in Philippine Peso
function format_currency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

// Format date to Philippine format
function format_date($date) {
    if (empty($date) || $date == '0000-00-00') return 'N/A';
    return date(DATE_FORMAT, strtotime($date));
}

// Format datetime to Philippine format
function format_datetime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date(DATETIME_FORMAT, strtotime($datetime));
}

// Validate Philippine mobile number
function validate_phone($phone) {
    return preg_match(PHONE_PATTERN, $phone);
}

// Format Philippine mobile number
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '9') {
        return '+63 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    } elseif (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
        return '+63 ' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    } elseif (strlen($phone) == 12 && substr($phone, 0, 2) == '63') {
        return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
    }
    return $phone;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function has_role($role) {
    if (!is_logged_in()) return false;
    if (is_array($role)) {
        return in_array($_SESSION['user_role'], $role);
    }
    return $_SESSION['user_role'] === $role;
}

// Require authentication
function require_auth() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit();
    }
}

// Require specific role
function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
        exit();
    }
}

// Log transaction
function log_transaction($conn, $action, $table_name, $record_id = null, $details = null) {
    if (!isset($_SESSION['user_id'])) return;
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO transaction_logs (user_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $details, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Generate unique stock number
function generate_stock_number($conn) {
    $prefix = 'VH-' . date('Y') . '-';
    $query = "SELECT stock_number FROM vehicles WHERE stock_number LIKE '{$prefix}%' ORDER BY stock_number DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = intval(substr($row['stock_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

// Generate unique invoice number
function generate_invoice_number($conn) {
    $prefix = 'INV-' . date('Y') . '-';
    $query = "SELECT invoice_number FROM sales WHERE invoice_number LIKE '{$prefix}%' ORDER BY invoice_number DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = intval(substr($row['invoice_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

// Generate unique service number
function generate_service_number($conn) {
    $prefix = 'SVC-' . date('Y') . '-';
    $query = "SELECT service_number FROM service_history WHERE service_number LIKE '{$prefix}%' ORDER BY service_number DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = intval(substr($row['service_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

// Handle file upload
function upload_file($file, $prefix = '') {
    if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error.'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'File size exceeds maximum limit (5MB).'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, and GIF allowed.'];
    }
    
    $filename = $prefix . '_' . uniqid() . '.' . $file_extension;
    $target_path = UPLOAD_DIR . $filename;
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return ['error' => 'Failed to upload file.'];
}

// Delete uploaded file
function delete_file($filename) {
    if (empty($filename)) return false;
    $file_path = UPLOAD_DIR . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// Get pagination data
function get_pagination($total_records, $current_page, $records_per_page = RECORDS_PER_PAGE) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'total_records' => $total_records
    ];
}

// Generate alert message HTML
function get_alert_html() {
    $html = '';
    
    if (isset($_SESSION['success'])) {
        $html .= '<div class="alert alert-success">' . escape_output($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        $html .= '<div class="alert alert-error">' . escape_output($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        $html .= '<div class="alert alert-warning">' . escape_output($_SESSION['warning']) . '</div>';
        unset($_SESSION['warning']);
    }
    
    if (isset($_SESSION['info'])) {
        $html .= '<div class="alert alert-info">' . escape_output($_SESSION['info']) . '</div>';
        unset($_SESSION['info']);
    }
    
    return $html;
}

// Send email (basic implementation)
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . APP_NAME . ' <noreply@carmanagement.ph>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Get customer full name
function get_customer_name($customer) {
    $name = $customer['first_name'];
    if (!empty($customer['middle_name'])) {
        $name .= ' ' . substr($customer['middle_name'], 0, 1) . '.';
    }
    $name .= ' ' . $customer['last_name'];
    return $name;
}

// Status badge HTML
function status_badge($status) {
    $class = 'badge-default';
    
    switch(strtolower($status)) {
        case 'available':
        case 'completed':
        case 'paid':
        case 'active':
            $class = 'badge-success';
            break;
        case 'sold':
        case 'partial':
        case 'in progress':
            $class = 'badge-info';
            break;
        case 'reserved':
        case 'scheduled':
        case 'pending':
            $class = 'badge-warning';
            break;
        case 'inactive':
        case 'cancelled':
        case 'in service':
            $class = 'badge-error';
            break;
    }
    
    return '<span class="badge ' . $class . '">' . escape_output($status) . '</span>';
}

// Check if table is empty
function is_table_empty($conn, $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM {$table}");
    $row = $result->fetch_assoc();
    return $row['count'] == 0;
}

// Export to CSV
function export_to_csv($filename, $data, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}
?>
