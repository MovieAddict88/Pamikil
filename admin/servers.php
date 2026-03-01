<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_server'])) {
        $name = sanitize($_POST['name']);
        $url = sanitize($_POST['url']);
        $type = sanitize($_POST['type']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($url)) {
            $error = 'Name and URL are required';
        } else {
            $stmt = $db->prepare("INSERT INTO servers (name, url, type, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $url, $type, $isActive]);
            $success = 'Server added successfully!';
        }
    } elseif (isset($_POST['update_server'])) {
        $serverId = (int)$_POST['server_id'];
        $name = sanitize($_POST['name']);
        $url = sanitize($_POST['url']);
        $type = sanitize($_POST['type']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($url)) {
            $error = 'Name and URL are required';
        } else {
            $stmt = $db->prepare("UPDATE servers SET name = ?, url = ?, type = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $url, $type, $isActive, $serverId]);
            $success = 'Server updated successfully!';
        }
    } elseif (isset($_POST['delete_server'])) {
        $serverId = (int)$_POST['server_id'];
        
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM movie_servers WHERE server_id = ?");
        $checkStmt->execute([$serverId]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            $error = "Cannot delete server. It is being used by $count movie(s).";
        } else {
            $stmt = $db->prepare("DELETE FROM servers WHERE id = ?");
            $stmt->execute([$serverId]);
            $success = 'Server deleted successfully!';
        }
    }
}

$servers = $db->query("SELECT s.*, COUNT(ms.id) as movies_count FROM servers s LEFT JOIN movie_servers ms ON s.id = ms.server_id GROUP BY s.id ORDER BY s.created_at DESC")->fetchAll();

$editServer = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM servers WHERE id = ?");
    $stmt->execute([$editId]);
    $editServer = $stmt->fetch();
}

$pageTitle = 'Servers';
include 'header.php';
?>

<div class="dashboard">
    <h1>Video Servers</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <div>
            <div class="dashboard-section">
                <h2><?php echo $editServer ? 'Edit Server' : 'Add New Server'; ?></h2>
                <form method="POST">
                    <?php if ($editServer): ?>
                        <input type="hidden" name="server_id" value="<?php echo $editServer['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Server Name *</label>
                        <input type="text" name="name" required 
                               value="<?php echo $editServer ? htmlspecialchars($editServer['name']) : ''; ?>"
                               placeholder="e.g., Server 1, Premium Server, YouTube">
                    </div>
                    
                    <div class="form-group">
                        <label>Base URL *</label>
                        <input type="url" name="url" required 
                               value="<?php echo $editServer ? htmlspecialchars($editServer['url']) : ''; ?>"
                               placeholder="https://example.com/videos/">
                        <div class="help-text">Base URL where videos are hosted (can be modified per movie)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Server Type</label>
                        <select name="type" required>
                            <option value="direct" <?php echo ($editServer && $editServer['type'] === 'direct') ? 'selected' : ''; ?>>
                                Direct (MP4, MKV, etc.)
                            </option>
                            <option value="youtube" <?php echo ($editServer && $editServer['type'] === 'youtube') ? 'selected' : ''; ?>>
                                YouTube
                            </option>
                            <option value="embed" <?php echo ($editServer && $editServer['type'] === 'embed') ? 'selected' : ''; ?>>
                                Embed/iFrame
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?php echo ($editServer && $editServer['is_active']) || !$editServer ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    
                    <div class="btn-group">
                        <?php if ($editServer): ?>
                            <button type="submit" name="update_server" class="btn btn-success">Update Server</button>
                            <a href="servers.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_server" class="btn btn-success">Add Server</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div>
            <div class="dashboard-section">
                <h2>Existing Servers (<?php echo count($servers); ?>)</h2>
                
                <?php if (empty($servers)): ?>
                    <p style="text-align: center; padding: 20px; color: #7f8c8d;">No servers added yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Movies</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servers as $server): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($server['name']); ?></strong>
                                            <br>
                                            <small style="color: #666; word-break: break-all;">
                                                <?php echo htmlspecialchars(substr($server['url'], 0, 40)) . '...'; ?>
                                            </small>
                                        </td>
                                        <td><?php echo ucfirst($server['type']); ?></td>
                                        <td><?php echo $server['movies_count']; ?></td>
                                        <td>
                                            <?php if ($server['is_active']): ?>
                                                <span style="color: #27ae60;">● Active</span>
                                            <?php else: ?>
                                                <span style="color: #e74c3c;">● Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="servers.php?edit=<?php echo $server['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 13px;">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="server_id" value="<?php echo $server['id']; ?>">
                                                <button type="submit" name="delete_server" class="btn btn-danger" 
                                                        style="padding: 5px 10px; font-size: 13px;"
                                                        onclick="return confirmDelete('Are you sure you want to delete this server?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section" style="margin-top: 30px;">
        <h2>Server Configuration Guide</h2>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
            <h3 style="margin-bottom: 15px;">Server Types:</h3>
            
            <div style="margin-bottom: 20px;">
                <h4>1. Direct Server</h4>
                <p>For self-hosted videos or CDN links. Supports:</p>
                <ul style="margin-left: 20px;">
                    <li>MP4, WebM, MKV files</li>
                    <li>MPD/DASH adaptive streaming</li>
                    <li>Direct video URLs</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h4>2. YouTube Server</h4>
                <p>For movies hosted on YouTube. Simply add the YouTube video URL when adding a movie.</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h4>3. Embed Server</h4>
                <p>For third-party embed players or custom iFrame sources.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
