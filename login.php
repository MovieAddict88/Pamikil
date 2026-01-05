<?php
require_once 'config/database.php';

session_start();

// Handle login
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch (Exception $e) {
            $error = "Login error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter username and password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e50914">
    <title>Login - CineCraze</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --dark: #0a0a0a;
            --surface: #1a1a1a;
            --surface-light: #2d2d2d;
            --text: #ffffff;
            --text-secondary: #b3b3b3;
            --success: #46d369;
            --danger: #f40612;
            --border-radius: 12px;
            --shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, var(--surface) 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(15px, 5vw, 30px);
        }

        .login-container {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(30px, 6vw, 50px);
            width: 100%;
            max-width: clamp(300px, 90vw, 450px);
            box-shadow: var(--shadow);
            border: 1px solid var(--surface-light);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #ff0000);
        }

        .logo {
            text-align: center;
            margin-bottom: clamp(25px, 5vw, 40px);
        }

        .logo h1 {
            font-size: clamp(24px, 5vw, 32px);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: clamp(8px, 2vw, 12px);
        }

        .logo p {
            color: var(--text-secondary);
            font-size: clamp(14px, 2.5vw, 16px);
        }

        .form-group {
            margin-bottom: clamp(20px, 4vw, 25px);
        }

        label {
            display: block;
            margin-bottom: clamp(6px, 1.5vw, 8px);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: clamp(13px, 2.5vw, 14px);
        }

        input {
            width: 100%;
            padding: clamp(12px, 3vw, 16px);
            border: 2px solid var(--surface-light);
            border-radius: clamp(8px, 2vw, 10px);
            background: var(--dark);
            color: var(--text);
            font-size: clamp(14px, 2.5vw, 16px);
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .btn {
            width: 100%;
            padding: clamp(12px, 3vw, 16px);
            border: none;
            border-radius: clamp(8px, 2vw, 10px);
            font-size: clamp(14px, 2.5vw, 16px);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: clamp(15px, 3vw, 20px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.4);
        }

        .btn-secondary {
            background: var(--surface-light);
            color: var(--text);
            border: 2px solid var(--surface-light);
        }

        .btn-secondary:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .error-message {
            background: rgba(244, 6, 18, 0.1);
            color: var(--danger);
            padding: clamp(12px, 2.5vw, 15px);
            border-radius: clamp(6px, 1.5vw, 8px);
            margin-bottom: clamp(15px, 3vw, 20px);
            border: 1px solid rgba(244, 6, 18, 0.3);
            font-size: clamp(13px, 2.5vw, 14px);
            font-weight: 600;
        }

        .demo-info {
            background: rgba(70, 211, 105, 0.1);
            color: var(--success);
            padding: clamp(12px, 2.5vw, 15px);
            border-radius: clamp(6px, 1.5vw, 8px);
            margin-bottom: clamp(15px, 3vw, 20px);
            border: 1px solid rgba(70, 211, 105, 0.3);
            font-size: clamp(13px, 2.5vw, 14px);
            font-weight: 600;
            text-align: center;
        }

        .footer-links {
            text-align: center;
            margin-top: clamp(20px, 4vw, 30px);
            padding-top: clamp(15px, 3vw, 20px);
            border-top: 1px solid var(--surface-light);
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: clamp(13px, 2.5vw, 14px);
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
        }

        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            width: 30px;
            height: 30px;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><i class="fas fa-play"></i> CineCraze</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="demo-info">
            <i class="fas fa-info-circle"></i>
            <strong>Demo Credentials:</strong><br>
            Username: admin<br>
            Password: admin123
        </div>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required placeholder="Enter username or email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <button type="button" class="btn btn-secondary" onclick="quickLogin('admin')">
                <i class="fas fa-user-shield"></i> Quick Login (Admin)
            </button>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Signing you in...</p>
        </div>

        <div class="footer-links">
            <a href="index.php">
                <i class="fas fa-home"></i> Back to Site
            </a>
        </div>
    </div>

    <script>
        function quickLogin(role) {
            const form = document.getElementById('loginForm');
            const loading = document.getElementById('loading');
            
            if (role === 'admin') {
                form.username.value = 'admin';
                form.password.value = 'admin123';
            }
            
            loading.style.display = 'block';
            form.style.display = 'none';
            
            setTimeout(() => {
                form.submit();
            }, 1000);
        }

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            this.style.display = 'none';
        });

        // Auto-fill demo credentials on focus
        document.getElementById('username').addEventListener('focus', function() {
            if (!this.value) {
                this.value = 'admin';
            }
        });

        document.getElementById('password').addEventListener('focus', function() {
            if (!this.value && document.getElementById('username').value) {
                this.value = 'admin123';
            }
        });
    </script>
</body>
</html>