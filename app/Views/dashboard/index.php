<?php 
$title = "Dashboard";
$bodyClass = "dashboard-page";
ob_start();
?>

<div class="dashboard-header">
    <div class="container">
        <h1>Welcome back, <?= htmlspecialchars($user['display_name']) ?>!</h1>
        <p>Ready to sing some karaoke?</p>
    </div>
</div>

<div class="dashboard-content">
    <div class="container">
        <div class="dashboard-grid">
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="action-buttons">
                        <a href="/dashboard/create-room" class="btn btn-primary">
                            <i class="fas fa-door-open"></i>
                            Create Room
                        </a>
                        <button class="btn btn-secondary" onclick="browseSongs()">
                            <i class="fas fa-music"></i>
                            Browse Songs
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- User Stats -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Your Stats</h3>
                </div>
                <div class="card-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= $userStats['rooms_joined'] ?></div>
                            <div class="stat-label">Rooms Joined</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $userStats['songs_sung'] ?></div>
                            <div class="stat-label">Songs Sung</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= number_format($userStats['avg_score'], 0) ?></div>
                            <div class="stat-label">Avg Score</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= number_format($userStats['best_score'], 0) ?></div>
                            <div class="stat-label">Best Score</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Public Rooms -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-door-open"></i> Public Rooms</h3>
                    <a href="/dashboard/rooms" class="card-link">View All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($publicRooms)): ?>
                        <p class="empty-state">No public rooms available</p>
                    <?php else: ?>
                        <div class="room-list">
                            <?php foreach ($publicRooms as $room): ?>
                            <div class="room-item">
                                <div class="room-info">
                                    <h4><?= htmlspecialchars($room['name']) ?></h4>
                                    <p class="room-host">by <?= htmlspecialchars($room['host_display_name']) ?></p>
                                    <div class="room-meta">
                                        <span class="room-participants">
                                            <i class="fas fa-users"></i>
                                            <?= $room['participant_count'] ?>/<?= $room['max_participants'] ?>
                                        </span>
                                        <span class="room-code"><?= htmlspecialchars($room['room_code']) ?></span>
                                    </div>
                                </div>
                                <div class="room-actions">
                                    <form method="POST" action="/room/<?= htmlspecialchars($room['room_code']) ?>/join" style="display: inline;">
                                        <button type="submit" class="btn btn-sm btn-success">Join</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- My Rooms -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-home"></i> My Rooms</h3>
                    <a href="/dashboard/rooms" class="card-link">Manage All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($userRooms)): ?>
                        <p class="empty-state">You haven't created any rooms yet</p>
                        <a href="/dashboard/create-room" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i>
                            Create Your First Room
                        </a>
                    <?php else: ?>
                        <div class="room-list">
                            <?php foreach ($userRooms as $room): ?>
                            <div class="room-item">
                                <div class="room-info">
                                    <h4>
                                        <a href="/room/<?= htmlspecialchars($room['room_code']) ?>">
                                            <?= htmlspecialchars($room['name']) ?>
                                        </a>
                                    </h4>
                                    <div class="room-meta">
                                        <span class="room-participants">
                                            <i class="fas fa-users"></i>
                                            <?= $room['participant_count'] ?>/<?= $room['max_participants'] ?>
                                        </span>
                                        <span class="room-status <?= $room['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $room['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </div>
                                    <p class="room-description"><?= htmlspecialchars($room['description'] ?? '') ?></p>
                                </div>
                                <div class="room-actions">
                                    <a href="/room/<?= htmlspecialchars($room['room_code']) ?>" class="btn btn-sm btn-primary">Enter</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Popular Songs -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-fire"></i> Popular Songs</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($popularSongs)): ?>
                        <p class="empty-state">No popular songs yet</p>
                    <?php else: ?>
                        <div class="song-list">
                            <?php foreach (array_slice($popularSongs, 0, 5) as $song): ?>
                            <div class="song-item">
                                <div class="song-info">
                                    <div class="song-number">#<?= $song['song_number'] ?></div>
                                    <div class="song-details">
                                        <h4><?= htmlspecialchars($song['title']) ?></h4>
                                        <p class="song-artist"><?= htmlspecialchars($song['artist']) ?></p>
                                        <div class="song-meta">
                                            <span class="play-count">
                                                <i class="fas fa-play"></i>
                                                <?= $song['play_count'] ?? 0 ?> plays
                                            </span>
                                            <?php if ($song['duration']): ?>
                                            <span class="duration">
                                                <i class="fas fa-clock"></i>
                                                <?= gmdate("i:s", $song['duration']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="addToQueue(<?= $song['id'] ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Songs -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Recent Additions</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($recentSongs)): ?>
                        <p class="empty-state">No recent songs</p>
                    <?php else: ?>
                        <div class="song-list">
                            <?php foreach (array_slice($recentSongs, 0, 5) as $song): ?>
                            <div class="song-item">
                                <div class="song-info">
                                    <div class="song-number">#<?= $song['song_number'] ?></div>
                                    <div class="song-details">
                                        <h4><?= htmlspecialchars($song['title']) ?></h4>
                                        <p class="song-artist"><?= htmlspecialchars($song['artist']) ?></p>
                                        <div class="song-meta">
                                            <span class="added-date">
                                                <i class="fas fa-calendar"></i>
                                                <?= date('M j', strtotime($song['created_at'])) ?>
                                            </span>
                                            <?php if ($song['duration']): ?>
                                            <span class="duration">
                                                <i class="fas fa-clock"></i>
                                                <?= gmdate("i:s", $song['duration']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="addToQueue(<?= $song['id'] ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function browseSongs() {
    // Open song browser modal
    window.open('/songs', '_blank', 'width=800,height=600');
}

function addToQueue(songId) {
    // This would typically open a room selection modal
    const roomCode = prompt('Enter room code to add this song to:');
    if (roomCode) {
        fetch(`/api/room/${roomCode}/queue`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ song_id: songId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Song added to queue!');
            } else {
                alert('Failed to add song: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add song to queue');
        });
    }
}
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>