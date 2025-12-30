<?php
$config_path = __DIR__ . '/../config/database.php';
$error_message = '';
$songs = [];

if (!file_exists($config_path)) {
    $error_message = "Application not installed. Please go to <a href='/install.php'>install.php</a> to set it up.";
} else {
    $config = require $config_path;
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $songs = $pdo->query("SELECT * FROM songs ORDER BY artist, title")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Database connection error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karaoke Night</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --background-color: #ecf0f1;
            --card-background: #ffffff;
            --text-color: #34495e;
            --shadow-color: rgba(0,0,0,0.1);

            font-size: clamp(1rem, 1.5vw, 1.1rem);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: clamp(1rem, 5vw, 2rem);
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: clamp(1.5rem, 5vw, 2.5rem) 0;
            text-align: center;
            margin-bottom: 2rem;
        }

        header h1 {
            margin: 0;
            font-size: clamp(2rem, 8vw, 3.5rem);
            font-weight: 700;
        }

        .song-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(280px, 30vw, 350px), 1fr));
            gap: clamp(1rem, 3vw, 1.5rem);
        }

        .song-card {
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 15px var(--shadow-color);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .song-card h2 {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
        }

        .song-card p {
            margin: 0 0 1.5rem 0;
            font-style: italic;
            color: var(--text-color);
        }

        .song-card a {
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .song-card a:hover {
            background-color: #2980b9;
        }

        .message {
            text-align: center;
            padding: 2rem;
            background-color: var(--card-background);
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <header>
        <h1>Karaoke Night</h1>
    </header>

    <main class="container">
        <?php if ($error_message): ?>
            <p class="message"><?php echo $error_message; ?></p>
        <?php elseif (empty($songs)): ?>
            <p class="message">No songs have been added yet. Check back soon!</p>
        <?php else: ?>
            <div class="song-grid">
                <?php foreach ($songs as $song): ?>
                    <div class="song-card">
                        <div>
                            <h2><?php echo htmlspecialchars($song['title']); ?></h2>
                            <p>by <?php echo htmlspecialchars($song['artist']); ?></p>
                        </div>
                        <a href="play.php?id=<?php echo $song['id']; ?>">Sing Now!</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
