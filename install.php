<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

if (app_is_installed()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$success = false;

$defaults = [
    'db_host' => 'localhost',
    'db_name' => 'cinecraze',
    'db_user' => 'root',
    'db_pass' => '',
    'admin_password' => '',
];

$values = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['db_host'] = trim((string)($_POST['db_host'] ?? ''));
    $values['db_name'] = trim((string)($_POST['db_name'] ?? ''));
    $values['db_user'] = trim((string)($_POST['db_user'] ?? ''));
    $values['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $values['admin_password'] = (string)($_POST['admin_password'] ?? '');

    if ($values['db_host'] === '' || $values['db_name'] === '' || $values['db_user'] === '') {
        $errors[] = 'Database host, name and username are required.';
    }

    if (strlen($values['admin_password']) < 6) {
        $errors[] = 'Admin password must be at least 6 characters.';
    }

    if ($errors === []) {
        try {
            $host = $values['db_host'];
            $dbName = $values['db_name'];
            $user = $values['db_user'];
            $pass = $values['db_pass'];

            $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS app_settings (
                  setting_key VARCHAR(191) NOT NULL PRIMARY KEY,
                  setting_value LONGTEXT NOT NULL,
                  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $defaultCatalogPath = __DIR__ . '/pagsure.json';
            if (is_file($defaultCatalogPath)) {
                $catalogJson = file_get_contents($defaultCatalogPath);
            } else {
                $catalogJson = json_encode([
                    'Categories' => [
                        [
                            'MainCategory' => 'Movies',
                            'SubCategories' => ['Action', 'Drama'],
                            'Entries' => [],
                        ],
                        [
                            'MainCategory' => 'TV Series',
                            'SubCategories' => ['Drama', 'Comedy'],
                            'Entries' => [],
                        ],
                        [
                            'MainCategory' => 'Live TV',
                            'SubCategories' => ['Entertainment'],
                            'Entries' => [],
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $adminHash = password_hash($values['admin_password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
            );

            $stmt->execute(['k' => 'catalog_json', 'v' => (string)$catalogJson]);
            $stmt->execute(['k' => 'admin_password_hash', 'v' => (string)$adminHash]);
            $stmt->execute(['k' => 'installed_at', 'v' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM)]);

            $config = [
                'db' => [
                    'host' => $host,
                    'name' => $dbName,
                    'user' => $user,
                    'pass' => $pass,
                    'charset' => 'utf8mb4',
                ],
                'app' => [
                    'name' => 'CineCraze',
                ],
            ];

            $configPhp = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";

            $written = file_put_contents(__DIR__ . '/config.php', $configPhp);
            if ($written === false) {
                throw new RuntimeException('Could not write config.php. Check file permissions.');
            }

            $success = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze – Install</title>
    <style>
        :root{--bg:#0b0b0b;--panel:#141414;--text:#fff;--muted:#b3b3b3;--primary:#e50914;--border:#2a2a2a;}
        *{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
        body{margin:0;min-height:100vh;background:radial-gradient(ellipse at top,#1a1a1a 0%,#0b0b0b 65%);color:var(--text);display:flex;align-items:center;justify-content:center;padding:24px;}
        .card{width:min(720px,100%);background:linear-gradient(180deg,var(--panel),#0f0f0f);border:1px solid var(--border);border-radius:16px;box-shadow:0 18px 60px rgba(0,0,0,.55);padding:24px;}
        h1{margin:0 0 6px;font-size:clamp(20px,3vw,30px);}p{margin:0 0 18px;color:var(--muted);}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        @media (max-width:640px){.grid{grid-template-columns:1fr;}}
        label{display:block;font-size:13px;color:var(--muted);margin:0 0 6px;}
        input{width:100%;padding:12px 12px;border-radius:12px;border:1px solid var(--border);background:#0c0c0c;color:var(--text);outline:none;}
        input:focus{border-color:var(--primary);box-shadow:0 0 0 2px rgba(229,9,20,.25);}
        .actions{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:18px;flex-wrap:wrap;}
        button{padding:12px 16px;border-radius:12px;border:0;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;}
        .msg{border-radius:12px;padding:12px 14px;margin:0 0 14px;border:1px solid var(--border);background:#101010;}
        .msg.error{border-color:rgba(229,9,20,.55);}
        .msg.success{border-color:rgba(70,211,105,.55);}
        a{color:#fff;text-decoration:none;border-bottom:1px dotted rgba(255,255,255,.35)}
    </style>
</head>
<body>
    <div class="card">
        <h1>CineCraze Installation</h1>
        <p>Connect CineCraze to MySQL and create the required tables. This runs once.</p>

        <?php if ($success): ?>
            <div class="msg success">
                Installed successfully. You can now <a href="/admin/login.php">sign in to the Admin Dashboard</a> or open the <a href="/index.php">public site</a>.
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
        <?php endforeach; ?>

        <?php if (!$success): ?>
            <form method="post" autocomplete="off">
                <div class="grid">
                    <div>
                        <label>DB Host</label>
                        <input name="db_host" value="<?php echo htmlspecialchars($values['db_host'], ENT_QUOTES); ?>" placeholder="localhost" required>
                    </div>
                    <div>
                        <label>DB Name</label>
                        <input name="db_name" value="<?php echo htmlspecialchars($values['db_name'], ENT_QUOTES); ?>" placeholder="cinecraze" required>
                    </div>
                    <div>
                        <label>DB Username</label>
                        <input name="db_user" value="<?php echo htmlspecialchars($values['db_user'], ENT_QUOTES); ?>" placeholder="root" required>
                    </div>
                    <div>
                        <label>DB Password</label>
                        <input name="db_pass" type="password" value="<?php echo htmlspecialchars($values['db_pass'], ENT_QUOTES); ?>">
                    </div>
                    <div style="grid-column:1/-1">
                        <label>Admin Password</label>
                        <input name="admin_password" type="password" value="" placeholder="Choose a strong password" required>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Install</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
