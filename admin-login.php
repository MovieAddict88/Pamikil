<?php
session_start();

// If already logged in, redirect to admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple admin authentication (in production, use proper password hashing)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#e50914">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --secondary: #221f1f;
            --background: #0a0a0a;
            --surface: #1a1a1a;
            --surface-light: #2d2d2d;
            --surface-hover: #333333;
            --text: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #808080;
            --success: #46d369;
            --warning: #ffa500;
            --danger: #f40612;
            --accent: #00d4ff;
            --accent-dark: #0099cc;
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --shadow: 0 8px 32px rgba(0,0,0,0.4);
            --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
            --shadow-primary: 0 8px 32px rgba(229, 9, 20, 0.3);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--surface) 0%, var(--background) 70%, var(--secondary) 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            pointer-events: none;
        }

        .login-container {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(40px, 6vw, 60px);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-primary);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(20px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: clamp(2rem, 5vw, 2.5rem);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 1.2em;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: clamp(1rem, 2.5vw, 1.1rem);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: var(--surface-light);
            color: var(--text);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
            background: var(--surface-hover);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: rgba(244, 6, 18, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }

        .back-link a:hover {
            color: var(--primary);
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                padding: 30px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px;
            }
        }

        /* Light Theme Support */
        .light-theme {
            --surface: #f5f5f5;
            --surface-light: #e6e6e6;
            --surface-hover: #d0d0d0;
            --text: #141414;
            --text-secondary: #333;
            --text-muted: #666;
            --background: #ffffff;
            --secondary: #e6e6e6;
        }

        .light-theme body {
            background: linear-gradient(135deg, var(--surface) 0%, var(--background) 70%, var(--secondary) 100%);
        }

        .light-theme .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 0, 0, 0.1);
        }

        .light-theme .form-input {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
            color: var(--text);
        }

        .light-theme .form-input:focus {
            background: rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .light-theme .form-input::placeholder {
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-cogs"></i>
                <?php echo APP_NAME; ?> Admin
            </div>
            <p class="login-subtitle">Sign in to access the admin dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">
                    <i class="fas fa-user"></i>
                    Username
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required 
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    <i class="fas fa-lock"></i>
                    Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="Enter your password"
                    required 
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Main Site
            </a>
        </div>
    </div>

    <script>
        // Theme Management
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
            }
        }

        // Form Handling
        function initForm() {
            const form = document.getElementById('loginForm');
            const btn = document.getElementById('loginBtn');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const username = usernameInput.value.trim();
                const password = passwordInput.value;

                if (!username || !password) {
                    alert('Please fill in all fields');
                    return;
                }

                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<div class="loading-spinner"></div> Signing In...';

                // Simulate authentication (in real implementation, this would be AJAX)
                setTimeout(() => {
                    // For demo purposes, we'll check credentials client-side
                    if (username === 'admin' && password === 'admin123') {
                        btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                        btn.style.background = 'var(--success)';
                        
                        setTimeout(() => {
                            // Redirect will be handled by PHP after form submission
                            form.submit();
                        }, 500);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                        showError('Invalid username or password');
                    }
                }, 1000);
            });

            // Focus on username field
            usernameInput.focus();
        }

        function showError(message) {
            // Remove existing error message
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            // Insert after header
            const header = document.querySelector('.login-header');
            header.parentNode.insertBefore(errorDiv, header.nextSibling);

            // Remove after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        // Keyboard shortcuts
        function initKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    document.getElementById('loginForm').dispatchEvent(new Event('submit'));
                }
                if (e.key === 'Escape') {
                    document.getElementById('password').blur();
                }
            });
        }

        // Demo credentials helper
        function showDemoCredentials() {
            setTimeout(() => {
                const helpDiv = document.createElement('div');
                helpDiv.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: var(--surface);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: var(--border-radius-sm);
                    padding: 15px;
                    font-size: 12px;
                    color: var(--text-muted);
                    max-width: 250px;
                    z-index: 1000;
                    backdrop-filter: blur(10px);
                `;
                
                helpDiv.innerHTML = `
                    <div style="font-weight: 600; color: var(--text); margin-bottom: 8px;">
                        <i class="fas fa-info-circle"></i> Demo Credentials
                    </div>
                    <div>Username: <code style="color: var(--primary);">admin</code></div>
                    <div>Password: <code style="color: var(--primary);">admin123</code></div>
                `;
                
                document.body.appendChild(helpDiv);
                
                // Auto-hide after 10 seconds
                setTimeout(() => {
                    helpDiv.style.opacity = '0';
                    helpDiv.style.transform = 'translateY(20px)';
                    helpDiv.style.transition = 'all 0.3s ease';
                    setTimeout(() => helpDiv.remove(), 300);
                }, 10000);
            }, 2000);
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            initForm();
            initKeyboardShortcuts();
            showDemoCredentials();
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>