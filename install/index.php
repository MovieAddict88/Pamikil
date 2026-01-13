<?php
// Simple installation script for VPN Panel

// Define the lock file path
$lock_file = __DIR__ . '/install.lock';

// Check if the installation is already locked
if (file_exists($lock_file)) {
    die("Installation is already complete. Please remove the /install directory for security reasons.");
}

// Include the database configuration
require_once __DIR__ . '/../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get admin user details from the form
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    // Validate input
    if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
        $error = "Please fill in all admin account details.";
    } else {
        // Hash the admin password
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

        // Create a connection to the MySQL server
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

        // Check connection
        if ($conn->connect_error) {
            $error = "Connection failed: " . $conn->connect_error;
        } else {
            // Create the database
            $sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
            if ($conn->query($sql_create_db) === TRUE) {
                $conn->select_db(DB_NAME);

                // Read the SQL schema file (without the admin insert)
                $sql_schema = file_get_contents('schema.sql');
                if ($sql_schema === false) {
                    $error = "Error reading schema.sql file.";
                } else {
                    // Execute the multi-query to create tables
                    if ($conn->multi_query($sql_schema)) {
                        while ($conn->next_result()) { if (!$conn->more_results()) break; }

                        // Now, insert the admin user
                        $stmt = $conn->prepare("INSERT INTO `users` (`username`, `password`, `email`, `is_active`) VALUES (?, ?, ?, 1)");
                        $stmt->bind_param('sss', $admin_username, $hashed_password, $admin_email);
                        if ($stmt->execute()) {
                            // Create the lock file
                            file_put_contents($lock_file, 'Installed on ' . date('c'));
                            $success = "Installation complete! Please delete the /install directory.";
                        } else {
                            $error = "Error creating admin user: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = "Error creating tables: " . $conn->error;
                    }
                }
            } else {
                $error = "Error creating database: " . $conn->error;
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPN Panel Installation</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; }
        .error { color: red; }
        .success { color: green; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>VPN Panel Installation</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php else: ?>
            <p>Please create your admin account to begin the installation.</p>
            <form method="post">
                <label for="admin_username">Admin Username</label>
                <input type="text" id="admin_username" name="admin_username" required>

                <label for="admin_email">Admin Email</label>
                <input type="email" id="admin_email" name="admin_email" required>

                <label for="admin_password">Admin Password</label>
                <input type="password" id="admin_password" name="admin_password" required>

                <button type="submit">Install Now</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
