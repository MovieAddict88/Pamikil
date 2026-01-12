<?php 
$title = "Admin Dashboard";
$bodyClass = "admin-page";
ob_start();
?>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
        <p>Manage your karaoke application</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_users']) ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-music"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_songs']) ?></h3>
                    <p>Total Songs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_rooms']) ?></h3>
                    <p>Total Rooms</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_songs_played']) ?></h3>
                    <p>Songs Played</p>
                </div>
            </div>
        </div>
        
        <div class="admin-grid">
            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="action-buttons">
                        <a href="/admin/songs/new" class="btn btn-primary">
                            <i class="fas fa-music"></i>
                            Add Song
                        </a>
                        <a href="/admin/youtube" class="btn btn-danger">
                            <i class="fab fa-youtube"></i>
                            YouTube Import
                        </a>
                        <a href="/admin/upload" class="btn btn-secondary">
                            <i class="fas fa-upload"></i>
                            Upload Media
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-clock"></i> Recent Users</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($recentUsers)): ?>
                        <p class="empty-state">No recent users</p>
                    <?php else: ?>
                        <div class="user-list">
                            <?php foreach (array_slice($recentUsers, 0, 5) as $user): ?>
                            <div class="user-item">
                                <img src="<?= $user['avatar_url'] ?? asset('images/default-avatar.png') ?>" 
                                     alt="<?= htmlspecialchars($user['display_name']) ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h4><?= htmlspecialchars($user['display_name']) ?></h4>
                                    <p>@<?= htmlspecialchars($user['username']) ?></p>
                                    <small><?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                                </div>
                                <?php if ($user['is_admin']): ?>
                                <span class="admin-badge">
                                    <i class="fas fa-crown"></i>
                                    Admin
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Songs -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-music"></i> Recent Songs</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($recentSongs)): ?>
                        <p class="empty-state">No recent songs</p>
                    <?php else: ?>
                        <div class="song-list">
                            <?php foreach (array_slice($recentSongs, 0, 5) as $song): ?>
                            <div class="song-item">
                                <div class="song-number">#<?= $song['song_number'] ?></div>
                                <div class="song-details">
                                    <h4><?= htmlspecialchars($song['title']) ?></h4>
                                    <p><?= htmlspecialchars($song['artist']) ?></p>
                                    <small><?= date('M j, Y', strtotime($song['created_at'])) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Public Rooms -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-door-open"></i> Active Rooms</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($publicRooms)): ?>
                        <p class="empty-state">No active rooms</p>
                    <?php else: ?>
                        <div class="room-list">
                            <?php foreach (array_slice($publicRooms, 0, 5) as $room): ?>
                            <div class="room-item">
                                <div class="room-info">
                                    <h4><?= htmlspecialchars($room['name']) ?></h4>
                                    <p>by <?= htmlspecialchars($room['host_display_name']) ?></p>
                                    <div class="room-meta">
                                        <span><?= $room['participant_count'] ?>/<?= $room['max_participants'] ?> users</span>
                                        <span><?= htmlspecialchars($room['room_code']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-800);
}

.stat-content p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.admin-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.admin-card .card-header {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.admin-card .card-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-800);
}

.admin-card .card-content {
    padding: 1.5rem;
}

.user-list,
.song-list,
.room-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-info h4 {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-800);
}

.user-info p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.user-info small {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.admin-badge {
    background: var(--warning-color);
    color: var(--gray-900);
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: auto;
}

.song-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.song-number {
    background: var(--primary-color);
    color: white;
    padding: 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
    font-size: 0.75rem;
    min-width: 40px;
    text-align: center;
}

.song-details h4 {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-800);
}

.song-details p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.song-details small {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.room-item {
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
}

.room-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.875rem;
    color: var(--gray-800);
}

.room-info p {
    margin: 0 0 0.5rem 0;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.room-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--gray-500);
}
</style>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>