<?php
declare(strict_types=1);

require __DIR__ . '/../includes/app.php';

app_require_install();
app_start_session();

if (admin_is_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $hash = app_setting_get('admin_password_hash', null);

    if (!$hash || !password_verify($password, $hash)) {
        $errors[] = 'Invalid password.';
    } else {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin/index.php');
        exit;
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze Admin – Login</title>
    <style>
        :root{--bg:#0b0b0b;--panel:#141414;--text:#fff;--muted:#b3b3b3;--primary:#e50914;--border:#2a2a2a;}
        *{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
        body{margin:0;min-height:100vh;background:radial-gradient(ellipse at top,#1a1a1a 0%,#0b0b0b 65%);color:var(--text);display:flex;align-items:center;justify-content:center;padding:24px;}
        .card{width:min(520px,100%);background:linear-gradient(180deg,var(--panel),#0f0f0f);border:1px solid var(--border);border-radius:16px;box-shadow:0 18px 60px rgba(0,0,0,.55);padding:24px;}
        h1{margin:0 0 6px;font-size:clamp(20px,3vw,28px);}p{margin:0 0 18px;color:var(--muted);}
        label{display:block;font-size:13px;color:var(--muted);margin:0 0 6px;}
        input{width:100%;padding:12px 12px;border-radius:12px;border:1px solid var(--border);background:#0c0c0c;color:var(--text);outline:none;}
        input:focus{border-color:var(--primary);box-shadow:0 0 0 2px rgba(229,9,20,.25);}
        button{width:100%;padding:12px 16px;border-radius:12px;border:0;background:var(--primary);color:#fff;font-weight:800;cursor:pointer;}
        .msg{border-radius:12px;padding:12px 14px;margin:0 0 14px;border:1px solid rgba(229,9,20,.55);background:#101010;}
        a{color:#fff;text-decoration:none;border-bottom:1px dotted rgba(255,255,255,.35)}
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Dashboard</h1>
        <p>Sign in to manage Movies / Series / Live TV and export your catalog.</p>

        <?php foreach ($errors as $error): ?>
            <div class="msg"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
        <?php endforeach; ?>

        <form method="post" autocomplete="off">
            <label>Password</label>
            <input type="password" name="password" required autofocus>
            <div style="height:14px"></div>
            <button type="submit">Sign In</button>
        </form>

        <p style="margin-top:14px"><a href="/index.php">Back to site</a></p>
    </div>
</body>
</html>
