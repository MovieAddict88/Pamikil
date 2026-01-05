<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    exit;
}

require_once '../config.php';
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_email'] = $row['email'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CineCraze</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(15px, 3vw, 20px);
        }
        
        .login-container {
            background: white;
            border-radius: clamp(12px, 2vw, 20px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            padding: clamp(35px, 6vw, 50px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: clamp(30px, 5vw, 40px);
        }
        
        .logo i {
            font-size: clamp(3rem, 8vw, 4rem);
            color: #e50914;
            margin-bottom: 15px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: clamp(10px, 2vw, 15px);
            font-size: clamp(1.5rem, 4vw, 2rem);
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: clamp(30px, 5vw, 40px);
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .form-group {
            margin-bottom: clamp(20px, 3vw, 25px);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        input {
            width: 100%;
            padding: clamp(12px, 2.5vw, 15px);
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: clamp(14px, 3vw, 18px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: clamp(1rem, 2.2vw, 1.1rem);
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: clamp(12px, 2.5vw, 15px);
            border-radius: 8px;
            margin-bottom: clamp(20px, 3vw, 25px);
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-film"></i>
            <h1>CineCraze</h1>
            <p class="subtitle">Admin Panel Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Site</a>
        </div>
    </div>
</body>
</html>
