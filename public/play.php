<?php
$song = null;
$error_message = '';
$youtube_video_id = null;

if (!isset($_GET['id'])) {
    $error_message = "No song ID provided.";
} else {
    $song_id = $_GET['id'];
    $config = require __DIR__ . '/../config/database.php';

    if (!$config) {
        $error_message = "Configuration file not found. Please run the installation script.";
    } else {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
            $stmt->execute([$song_id]);
            $song = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$song) {
                $error_message = "Song not found.";
            } else {
                // Extract YouTube Video ID from URL
                $url = $song['source_url'];
                $video_id = '';
                if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
                    $video_id = $matches[1];
                } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
                    $video_id = $matches[1];
                }
                if($video_id) {
                    $youtube_video_id = $video_id;
                } else {
                    $error_message = "Could not parse the YouTube video URL.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Singing: <?php echo $song ? htmlspecialchars($song['title']) : 'Karaoke'; ?></title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --background-color: #ecf0f1;
            --text-color: #34495e;
            --border-color: #bdc3c7;
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
            padding: 2rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .player-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            background-color: #000;
            border-radius: 8px;
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .song-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .song-info h1 {
            margin: 0 0 0.5rem 0;
        }

        .controls {
            padding: 1.5rem;
            background-color: white;
            border-radius: 8px;
        }

        .controls h3 {
            margin-top: 0;
        }

        .message {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
        }

        @media (max-width: 900px) {
            .player-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<main class="container">
    <a href="index.php" class="back-link">&larr; Back to Song List</a>
    <?php if ($error_message): ?>
        <p class="message"><?php echo $error_message; ?></p>
    <?php elseif ($song): ?>
        <div class="song-info">
            <h1><?php echo htmlspecialchars($song['title']); ?></h1>
            <p>by <?php echo htmlspecialchars($song['artist']); ?></p>
        </div>

        <div class="player-container">
            <div class="video-wrapper">
                <?php if ($youtube_video_id): ?>
                    <iframe
                        src="https://www.youtube.com/embed/<?php echo $youtube_video_id; ?>?autoplay=1&controls=0"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                     <p style="color:white; text-align:center; padding-top: 45%;">Could not load video.</p>
                <?php endif; ?>
            </div>

            <aside class="controls">
                <h3>Real-Time Scoring</h3>
                <p><em>(Feature coming soon)</em></p>
                <div class="score">Score: 0</div>

                <h3 style="margin-top: 2rem;">Lyrics</h3>
                 <div class="lyrics-display" style="height: 200px; overflow-y: auto; border: 1px solid var(--border-color); padding: 10px; border-radius: 4px;">
                    <p><em>(Synchronized lyrics coming soon)</em></p>
                    <?php if(!empty($song['lyrics'])): ?>
                        <pre><?php echo htmlspecialchars($song['lyrics']); ?></pre>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

    <?php endif; ?>
</main>

</body>
</html>
