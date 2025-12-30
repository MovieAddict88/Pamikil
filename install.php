<?php
session_start();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $admin_user = $_POST['admin_user'];
    $admin_pass = $_POST['admin_pass'];
    $admin_email = $_POST['admin_email'];

    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        $error_message = 'Please fill in all the required fields.';
    } else {
        // Ensure the config directory exists
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }

        // Create config file
        $config_content = "<?php\n\nreturn [\n    'host' => '{$db_host}',\n    'name' => '{$db_name}',\n    'user' => '{$db_user}',\n    'pass' => '{$db_pass}',\n];\n";
        if (!file_put_contents('config/database.php', $config_content)) {
            $error_message = 'Could not write database configuration file. Please check permissions.';
        } else {
            try {
                // Connect to MySQL server
                $pdo = new PDO("mysql:host={$db_host}", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}`");
                $pdo->exec("USE `{$db_name}`");

                // Import schema
                $schema = file_get_contents('database/schema.sql');
                $pdo->exec($schema);

                // Create admin user
                $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
                $stmt->execute([$admin_user, $password_hash, $admin_email]);

                $success_message = 'Installation complete! You can now delete this install.php file. <a href="public/admin/">Go to Admin Login</a>';

            } catch (PDOException $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                // Clean up config file on error
                unlink('config/database.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karaoke App Installation</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; max-width: 600px; margin: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: #D8000C; background-color: #FFBABA; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #4F8A10; background-color: #DFF2BF; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Karaoke App Installation</h1>
    <?php if ($error_message): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="success"><?php echo $success_message; ?></div>
    <?php else: ?>
    <form action="install.php" method="post">
        <h2>Database Settings</h2>
        <div class="form-group">
            <label for="db_host">Database Host</label>
            <input type="text" id="db_host" name="db_host" value="127.0.0.1" required>
        </div>
        <div class="form-group">
            <label for="db_name">Database Name</label>
            <input type="text" id="db_name" name="db_name" required>
        </div>
        <div class="form-group">
            <label for="db_user">Database User</label>
            <input type="text" id="db_user" name="db_user" required>
        </div>
        <div class="form-group">
            <label for="db_pass">Database Password</label>
            <input type="password" id="db_pass" name="db_pass">
        </div>

        <h2>Admin Account</h2>
        <div class="form-group">
            <label for="admin_user">Admin Username</label>
            <input type="text" id="admin_user" name="admin_user" required>
        </div>
        <div class="form-group">
            <label for="admin_pass">Admin Password</label>
            <input type="password" id="admin_pass" name="admin_pass" required>
        </div>
         <div class="form-group">
            <label for="admin_email">Admin Email</label>
            <input type="email" id="admin_email" name="admin_email" required>
        </div>

        <button type="submit">Install</button>
    </form>
    <?php endif; ?>
</body>
</html>
