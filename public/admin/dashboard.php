<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$config = require __DIR__ . '/../../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
$error_message = '';
$success_message = '';

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submission for adding a new song
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_song'])) {
        $title = $_POST['title'];
        $artist = $_POST['artist'];
        $source_url = $_POST['source_url'];

        // For now, we'll use a source_id of 1 (e.g., "YouTube") and empty lyrics
        $stmt = $pdo->prepare("INSERT INTO songs (title, artist, source_id, source_url, lyrics) VALUES (?, ?, 1, ?, '')");
        $stmt->execute([$title, $artist, $source_url]);
        $success_message = 'Song added successfully!';
    }

    // Fetch all songs
    $songs = $pdo->query("SELECT * FROM songs ORDER BY artist, title")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        h1, h2 { text-align: center; }
        nav { background-color: #f4f4f4; padding: 10px; text-align: right; margin-bottom: 20px; }
        nav a { text-decoration: none; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-container { padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: #D8000C; background-color: #FFBABA; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #4F8A10; background-color: #DFF2BF; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="logout.php">Logout</a>
        </nav>
        <h1>Admin Dashboard</h1>

        <div class="form-container">
            <h2>Add New Song</h2>
             <?php if ($success_message): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <form action="dashboard.php" method="post">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="artist">Artist</label>
                    <input type="text" id="artist" name="artist" required>
                </div>
                <div class="form-group">
                    <label for="source_url">YouTube URL</label>
                    <input type="text" id="source_url" name="source_url" required>
                </div>
                <button type="submit" name="add_song">Add Song</button>
            </form>
        </div>

        <h2>Song List</h2>
         <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Artist</th>
                    <th>Title</th>
                    <th>Source URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($songs)): ?>
                    <tr><td colspan="4">No songs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($song['artist']); ?></td>
                            <td><?php echo htmlspecialchars($song['title']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($song['source_url']); ?>" target="_blank">Link</a></td>
                            <td><a href="#">Edit</a> | <a href="#">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
