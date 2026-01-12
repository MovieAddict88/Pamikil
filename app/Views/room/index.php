<?php 
$title = $room['name'] . " - Karaoke Room";
$bodyClass = "room-page";
$extraScripts = '<script src="https://www.youtube.com/iframe_api"></script>';
ob_start();
?>

<div class="room-header">
    <div class="container">
        <div class="room-info">
            <h1><?= htmlspecialchars($room['name']) ?></h1>
            <div class="room-meta">
                <span class="room-code">
                    <i class="fas fa-key"></i>
                    Code: <strong><?= htmlspecialchars($room['room_code']) ?></strong>
                </span>
                <span class="room-host">
                    <i class="fas fa-crown"></i>
                    Host: <?= htmlspecialchars($room['host_display_name']) ?>
                </span>
                <span class="participant-count">
                    <i class="fas fa-users"></i>
                    <?= count($participants) ?>/<?= $room['max_participants'] ?> participants
                </span>
            </div>
        </div>
        
        <div class="room-actions">
            <button class="btn btn-secondary" onclick="copyRoomCode()">
                <i class="fas fa-copy"></i>
                Copy Code
            </button>
            <?php if ($userRole === 'host'): ?>
            <button class="btn btn-warning" onclick="openRoomSettings()">
                <i class="fas fa-cog"></i>
                Settings
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="room-content">
    <div class="container">
        <div class="room-layout">
            <!-- Video Player Section -->
            <div class="video-section">
                <div class="video-container">
                    <div id="videoPlayer" class="video-player">
                        <?php if ($currentSong): ?>
                            <?php if ($currentSong['youtube_video_id']): ?>
                                <div id="youtubePlayer"></div>
                            <?php elseif ($currentSong['file_path']): ?>
                                <video id="html5Video" controls autoplay>
                                    <source src="<?= htmlspecialchars($currentSong['file_path']) ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php elseif ($currentSong['embed_code']): ?>
                                <div class="embed-container">
                                    <?= $currentSong['embed_code'] ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-video">
                                <i class="fas fa-play-circle"></i>
                                <h3>No song playing</h3>
                                <p>Add some songs to the queue to get started!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Video Controls Overlay -->
                    <?php if ($currentSong && in_array($userRole, ['host', 'moderator'])): ?>
                    <div class="video-controls">
                        <button class="control-btn" onclick="skipSong()" title="Skip Song">
                            <i class="fas fa-forward"></i>
                        </button>
                        <button class="control-btn" onclick="togglePlayPause()" id="playPauseBtn" title="Play/Pause">
                            <i class="fas fa-pause"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Current Song Info -->
                <?php if ($currentSong): ?>
                <div class="current-song-info">
                    <div class="song-details">
                        <h3><?= htmlspecialchars($currentSong['title']) ?></h3>
                        <p>by <?= htmlspecialchars($currentSong['artist']) ?></p>
                        <div class="singer-info">
                            <img src="<?= $currentSong['avatar_url'] ?? asset('images/default-avatar.png') ?>" 
                                 alt="Singer" class="singer-avatar">
                            <span>Performed by <?= htmlspecialchars($currentSong['display_name']) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($currentSong['duration']): ?>
                    <div class="song-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="songProgress"></div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">0:00</span>
                            <span id="totalTime"><?= gmdate("i:s", $currentSong['duration']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="room-sidebar">
                <!-- Queue Section -->
                <div class="queue-section">
                    <div class="section-header">
                        <h3><i class="fas fa-list"></i> Queue</h3>
                        <div class="queue-stats">
                            <?= $queueStats['queued_count'] ?> queued
                        </div>
                    </div>
                    
                    <div class="queue-container">
                        <?php if (empty($queue)): ?>
                            <div class="empty-queue">
                                <i class="fas fa-music"></i>
                                <p>Queue is empty</p>
                                <button class="btn btn-primary btn-sm" onclick="openSongBrowser()">
                                    <i class="fas fa-plus"></i>
                                    Add Songs
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="queue-list" id="queueList">
                                <?php foreach ($queue as $queueItem): ?>
                                <div class="queue-item <?= $queueItem['status'] ?>">
                                    <div class="queue-number"><?= $queueItem['position'] ?></div>
                                    <div class="queue-song-info">
                                        <h4><?= htmlspecialchars($queueItem['title']) ?></h4>
                                        <p><?= htmlspecialchars($queueItem['artist']) ?></p>
                                        <div class="queue-meta">
                                            <img src="<?= $queueItem['avatar_url'] ?? asset('images/default-avatar.png') ?>" 
                                                 alt="Requester" class="requester-avatar">
                                            <span><?= htmlspecialchars($queueItem['display_name']) ?></span>
                                            <?php if ($queueItem['duration']): ?>
                                            <span class="duration">
                                                <i class="fas fa-clock"></i>
                                                <?= gmdate("i:s", $queueItem['duration']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (in_array($userRole, ['host', 'moderator']) || $queueItem['user_id'] == $user['id']): ?>
                                    <div class="queue-actions">
                                        <button class="action-btn" onclick="removeFromQueue(<?= $queueItem['id'] ?>)" title="Remove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add to Queue Button (Mobile) -->
                    <div class="mobile-add-song">
                        <button class="btn btn-primary btn-block" onclick="openSongBrowser()">
                            <i class="fas fa-plus"></i>
                            Add Songs to Queue
                        </button>
                    </div>
                </div>
                
                <!-- Participants Section -->
                <div class="participants-section">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> Participants (<?= count($participants) ?>)</h3>
                    </div>
                    
                    <div class="participants-list">
                        <?php foreach ($participants as $participant): ?>
                        <div class="participant-item <?= $participant['role'] ?>">
                            <img src="<?= $participant['avatar_url'] ?? asset('images/default-avatar.png') ?>" 
                                 alt="<?= htmlspecialchars($participant['display_name']) ?>" 
                                 class="participant-avatar">
                            <div class="participant-info">
                                <span class="participant-name"><?= htmlspecialchars($participant['display_name']) ?></span>
                                <?php if ($participant['role'] !== 'participant'): ?>
                                <span class="participant-role">
                                    <i class="fas fa-<?= $participant['role'] === 'host' ? 'crown' : 'shield-alt' ?>"></i>
                                    <?= ucfirst($participant['role']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Song Browser Modal -->
<div id="songBrowserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-music"></i> Browse Songs</h3>
            <button class="modal-close" onclick="closeSongBrowser()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="search-container">
                <input type="text" id="songSearch" placeholder="Search songs..." class="search-input">
                <button class="search-btn" onclick="searchSongs()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="song-browser-tabs">
                <button class="tab-btn active" onclick="switchSongTab('search')">Search</button>
                <button class="tab-btn" onclick="switchSongTab('popular')">Popular</button>
                <button class="tab-btn" onclick="switchSongTab('recent')">Recent</button>
                <button class="tab-btn" onclick="switchSongTab('by-number')">By Number</button>
            </div>
            
            <div class="song-results" id="songResults">
                <!-- Songs will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Room Settings Modal (for host) -->
<?php if ($userRole === 'host'): ?>
<div id="roomSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-cog"></i> Room Settings</h3>
            <button class="modal-close" onclick="closeRoomSettings()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="roomSettingsForm">
                <div class="setting-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="autoplay" <?= (json_decode($room['settings'], true)['autoplay'] ?? false) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Auto-play next song
                    </label>
                </div>
                
                <div class="setting-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allow_duets" <?= (json_decode($room['settings'], true)['allow_duets'] ?? true) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Allow duets
                    </label>
                </div>
                
                <div class="setting-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="require_approval" <?= (json_decode($room['settings'], true)['require_approval'] ?? false) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Require approval for songs
                    </label>
                </div>
                
                <div class="setting-group">
                    <label for="max_songs_per_user">Max songs per user</label>
                    <select name="max_songs_per_user">
                        <?php 
                        $currentMax = json_decode($room['settings'], true)['max_songs_per_user'] ?? 3;
                        for ($i = 1; $i <= 10; $i++): 
                        ?>
                        <option value="<?= $i ?>" <?= $currentMax == $i ? 'selected' : '' ?>><?= $i ?> song<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRoomSettings()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let currentRoom = {
    code: '<?= htmlspecialchars($room['room_code']) ?>',
    id: <?= $room['id'] ?>,
    isHost: <?= $userRole === 'host' ? 'true' : 'false' ?>,
    isModerator: <?= in_array($userRole, ['host', 'moderator']) ? 'true' : 'false' ?>,
    currentSong: <?= $currentSong ? json_encode($currentSong) : 'null' ?>
};

// Initialize the room
document.addEventListener('DOMContentLoaded', function() {
    initializeRoom();
    startQueuePolling();
    initializeVideoPlayer();
});

// Room management functions
function initializeRoom() {
    // Set up WebSocket connection for real-time updates
    // For now, we'll use polling
    console.log('Room initialized:', currentRoom);
}

function copyRoomCode() {
    navigator.clipboard.writeText('<?= htmlspecialchars($room['room_code']) ?>').then(() => {
        showToast('Room code copied to clipboard!');
    });
}

function openSongBrowser() {
    document.getElementById('songBrowserModal').style.display = 'block';
    loadInitialSongs();
}

function closeSongBrowser() {
    document.getElementById('songBrowserModal').style.display = 'none';
}

function switchSongTab(tab) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Load appropriate songs
    switch(tab) {
        case 'popular':
            loadPopularSongs();
            break;
        case 'recent':
            loadRecentSongs();
            break;
        case 'by-number':
            showSongNumberInput();
            break;
        default:
            // Search tab is already active
            break;
    }
}

function searchSongs() {
    const query = document.getElementById('songSearch').value;
    if (query.trim()) {
        fetch(`/api/songs/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => displaySongs(data.results || []));
    }
}

function loadInitialSongs() {
    loadPopularSongs();
}

function loadPopularSongs() {
    fetch('/api/songs/popular')
        .then(response => response.json())
        .then(data => displaySongs(data.songs || []));
}

function loadRecentSongs() {
    fetch('/api/songs/recent')
        .then(response => response.json())
        .then(data => displaySongs(data.songs || []));
}

function showSongNumberInput() {
    const searchInput = document.getElementById('songSearch');
    searchInput.type = 'number';
    searchInput.placeholder = 'Enter song number...';
    searchInput.min = '1';
    searchInput.focus();
}

function displaySongs(songs) {
    const container = document.getElementById('songResults');
    
    if (songs.length === 0) {
        container.innerHTML = '<p class="no-results">No songs found</p>';
        return;
    }
    
    container.innerHTML = songs.map(song => `
        <div class="song-browser-item">
            <div class="song-info">
                <div class="song-number">#${song.song_number}</div>
                <div class="song-details">
                    <h4>${escapeHtml(song.title)}</h4>
                    <p>${escapeHtml(song.artist)}</p>
                    ${song.duration ? `<small class="duration">${formatDuration(song.duration)}</small>` : ''}
                </div>
            </div>
            <button class="btn btn-sm btn-primary" onclick="addSongToQueue(${song.id})">
                <i class="fas fa-plus"></i>
                Add
            </button>
        </div>
    `).join('');
}

function addSongToQueue(songId) {
    fetch(`/api/room/${currentRoom.code}/queue`, {
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
            showToast('Song added to queue!');
            closeSongBrowser();
        } else {
            showToast('Failed to add song: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to add song to queue', 'error');
    });
}

function removeFromQueue(queueId) {
    if (confirm('Remove this song from the queue?')) {
        fetch(`/api/room/${currentRoom.code}/queue?id=${queueId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Song removed from queue');
            } else {
                showToast('Failed to remove song', 'error');
            }
        });
    }
}

function skipSong() {
    if (confirm('Skip the current song?')) {
        fetch(`/api/room/${currentRoom.code}/queue/skip`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast('Failed to skip song', 'error');
            }
        });
    }
}

function togglePlayPause() {
    // Implementation depends on video player type
    const btn = document.getElementById('playPauseBtn');
    const icon = btn.querySelector('i');
    
    if (icon.classList.contains('fa-pause')) {
        icon.className = 'fas fa-play';
        // Pause video
    } else {
        icon.className = 'fas fa-pause';
        // Play video
    }
}

// Real-time queue updates
function startQueuePolling() {
    setInterval(() => {
        fetch(`/api/room/${currentRoom.code}/queue`)
            .then(response => response.json())
            .then(data => {
                updateQueue(data.queue);
                if (data.currentSong && (!currentRoom.currentSong || data.currentSong.id !== currentRoom.currentSong.id)) {
                    currentRoom.currentSong = data.currentSong;
                    updateCurrentSong(data.currentSong);
                }
            })
            .catch(error => console.error('Queue update failed:', error));
    }, 5000); // Poll every 5 seconds
}

function updateQueue(queue) {
    const queueList = document.getElementById('queueList');
    if (!queueList) return;
    
    queueList.innerHTML = queue.map(item => `
        <div class="queue-item ${item.status}">
            <div class="queue-number">${item.position}</div>
            <div class="queue-song-info">
                <h4>${escapeHtml(item.title)}</h4>
                <p>${escapeHtml(item.artist)}</p>
                <div class="queue-meta">
                    <img src="${item.avatar_url || '/assets/images/default-avatar.png'}" 
                         alt="Requester" class="requester-avatar">
                    <span>${escapeHtml(item.display_name)}</span>
                    ${item.duration ? `<span class="duration"><i class="fas fa-clock"></i>${formatDuration(item.duration)}</span>` : ''}
                </div>
            </div>
            ${(currentRoom.isModerator || item.user_id == <?= $user['id'] ?>) ? `
            <div class="queue-actions">
                <button class="action-btn" onclick="removeFromQueue(${item.id})" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>` : ''}
        </div>
    `).join('');
}

function updateCurrentSong(song) {
    // Update current song display
    console.log('Current song updated:', song);
}

function initializeVideoPlayer() {
    if (currentRoom.currentSong && currentRoom.currentSong.youtube_video_id) {
        initializeYouTubePlayer();
    }
}

function initializeYouTubePlayer() {
    if (typeof YT !== 'undefined' && YT.Player) {
        // YouTube API is ready
        new YT.Player('youtubePlayer', {
            height: '100%',
            width: '100%',
            videoId: currentRoom.currentSong.youtube_video_id,
            playerVars: {
                autoplay: 1,
                controls: 0,
                disablekb: 1,
                enablejsapi: 1,
                fs: 0,
                iv_load_policy: 3,
                modestbranding: 1,
                playsinline: 1,
                rel: 0
            },
            events: {
                'onReady': onYouTubePlayerReady,
                'onStateChange': onYouTubePlayerStateChange
            }
        });
    }
}

function onYouTubePlayerReady(event) {
    event.target.playVideo();
}

function onYouTubePlayerStateChange(event) {
    const playPauseBtn = document.getElementById('playPauseBtn');
    if (playPauseBtn) {
        const icon = playPauseBtn.querySelector('i');
        icon.className = event.data === YT.PlayerState.PLAYING ? 'fas fa-pause' : 'fas fa-play';
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function showToast(message, type = 'success') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Room settings (host only)
<?php if ($userRole === 'host'): ?>
function openRoomSettings() {
    document.getElementById('roomSettingsModal').style.display = 'block';
}

function closeRoomSettings() {
    document.getElementById('roomSettingsModal').style.display = 'none';
}

document.getElementById('roomSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const settings = Object.fromEntries(formData.entries());
    
    fetch(`/api/room/${currentRoom.code}/settings`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Settings updated successfully');
            closeRoomSettings();
        } else {
            showToast('Failed to update settings', 'error');
        }
    });
});
<?php endif; ?>
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>