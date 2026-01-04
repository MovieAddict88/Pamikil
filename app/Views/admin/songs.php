<?php 
$title = "Song Management";
$bodyClass = "admin-page";
ob_start();
?>

<div class="page-header">
    <div class="container">
        <div class="header-content">
            <div class="header-info">
                <h1><i class="fas fa-music"></i> Song Management</h1>
                <p>Manage your karaoke song database</p>
            </div>
            <div class="header-actions">
                <a href="/admin/songs/new" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Song
                </a>
                <button class="btn btn-secondary" onclick="openYouTubeImport()">
                    <i class="fab fa-youtube"></i>
                    Import from YouTube
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <!-- Search and Filter -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text" 
                           name="search" 
                           placeholder="Search songs..." 
                           value="<?= htmlspecialchars($search ?? '') ?>"
                           class="search-input">
                </div>
                
                <div class="filter-group">
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= ($selectedCategory ?? 0) == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    <a href="/admin/songs" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Songs List -->
        <div class="songs-container">
            <?php if (empty($songs)): ?>
                <div class="empty-state">
                    <i class="fas fa-music"></i>
                    <h3>No songs found</h3>
                    <p>Start building your karaoke library by adding some songs.</p>
                    <a href="/admin/songs/new" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Your First Song
                    </a>
                </div>
            <?php else: ?>
                <div class="songs-table">
                    <div class="table-header">
                        <div class="header-cell number">#</div>
                        <div class="header-cell title">Song</div>
                        <div class="header-cell artist">Artist</div>
                        <div class="header-cell duration">Duration</div>
                        <div class="header-cell categories">Categories</div>
                        <div class="header-cell status">Status</div>
                        <div class="header-cell actions">Actions</div>
                    </div>
                    
                    <div class="table-body">
                        <?php foreach ($songs as $song): ?>
                        <div class="table-row">
                            <div class="table-cell number">
                                <span class="song-number"><?= $song['song_number'] ?></span>
                            </div>
                            
                            <div class="table-cell title">
                                <div class="song-title">
                                    <?= htmlspecialchars($song['title']) ?>
                                    <?php if ($song['youtube_video_id']): ?>
                                    <i class="fab fa-youtube youtube-badge" title="YouTube video"></i>
                                    <?php endif; ?>
                                    <?php if ($song['file_path']): ?>
                                    <i class="fas fa-file-video file-badge" title="Uploaded file"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if ($song['album']): ?>
                                <div class="song-album"><?= htmlspecialchars($song['album']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="table-cell artist">
                                <?= htmlspecialchars($song['artist']) ?>
                            </div>
                            
                            <div class="table-cell duration">
                                <?php if ($song['duration']): ?>
                                    <?= gmdate("i:s", $song['duration']) ?>
                                <?php else: ?>
                                    <span class="text-muted">--:--</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="table-cell categories">
                                <?php if ($song['categories']): ?>
                                    <?php 
                                    $categoryList = explode(',', $song['categories']);
                                    foreach (array_slice($categoryList, 0, 2) as $category): 
                                    ?>
                                    <span class="category-tag"><?= htmlspecialchars(trim($category)) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($categoryList) > 2): ?>
                                    <span class="category-more">+<?= count($categoryList) - 2 ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No categories</span>
                                <?php endif; ?>
            </div>
                            
                            <div class="table-cell status">
                                <span class="status-badge <?= $song['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $song['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            
                            <div class="table-cell actions">
                                <div class="action-buttons">
                                    <a href="/admin/songs/<?= $song['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="previewSong(<?= $song['id'] ?>)" 
                                            title="Preview">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    
                                    <form method="POST" action="/admin/songs/<?= $song['id'] ?>/toggle" 
                                          style="display: inline;">
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-<?= $song['is_active'] ? 'warning' : 'success' ?>" 
                                                title="<?= $song['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $song['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteSong(<?= $song['id'] ?>)" 
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($hasMore): ?>
                <div class="pagination">
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-chevron-down"></i>
                        Load More
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- YouTube Import Modal -->
<div id="youtubeImportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fab fa-youtube"></i> Import from YouTube</h3>
            <button class="modal-close" onclick="closeYouTubeImport()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="import-tabs">
                <button class="tab-btn active" onclick="switchImportTab('search')">Search & Import</button>
                <button class="tab-btn" onclick="switchImportTab('playlist')">Import Playlist</button>
            </div>
            
            <!-- Search and Import -->
            <div id="searchImportTab" class="import-tab active">
                <div class="search-container">
                    <input type="text" id="youtubeSearch" placeholder="Search YouTube videos..." class="search-input">
                    <button onclick="searchYouTube()" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </div>
                
                <div id="youtubeResults" class="youtube-results">
                    <!-- Results will be populated here -->
                </div>
            </div>
            
            <!-- Playlist Import -->
            <div id="playlistImportTab" class="import-tab">
                <div class="form-group">
                    <label for="playlistUrl">YouTube Playlist URL</label>
                    <input type="url" id="playlistUrl" placeholder="https://www.youtube.com/playlist?list=..." class="form-control">
                </div>
                
                <button onclick="importPlaylist()" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    Import Playlist
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-info h1 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.search-input {
    min-width: 300px;
}

.filter-select {
    min-width: 200px;
}

.songs-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.songs-table {
    width: 100%;
}

.table-header {
    display: grid;
    grid-template-columns: 60px 2fr 1fr 80px 1fr 80px 150px;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-700);
}

.table-row {
    display: grid;
    grid-template-columns: 60px 2fr 1fr 80px 1fr 80px 150px;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
    transition: var(--transition);
}

.table-row:hover {
    background: var(--gray-50);
}

.table-cell {
    display: flex;
    align-items: center;
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

.song-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.youtube-badge {
    color: #ff0000;
}

.file-badge {
    color: var(--info-color);
}

.song-album {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}

.category-tag {
    background: var(--primary-light);
    color: var(--primary-color);
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    margin-right: 0.5rem;
    margin-bottom: 0.25rem;
    display: inline-block;
}

.category-more {
    background: var(--gray-200);
    color: var(--gray-600);
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.active {
    background: rgba(81, 207, 102, 0.1);
    color: var(--success-color);
}

.status-badge.inactive {
    background: rgba(108, 117, 125, 0.1);
    color: var(--gray-600);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.pagination {
    padding: 1.5rem;
    text-align: center;
    border-top: 1px solid var(--gray-200);
}

.import-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.tab-btn {
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    color: var(--gray-600);
    transition: var(--transition);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.import-tab {
    display: none;
}

.import-tab.active {
    display: block;
}

.youtube-results {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1rem;
}

.youtube-result-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
}

.youtube-result-item:last-child {
    border-bottom: none;
}

.youtube-thumbnail {
    width: 120px;
    height: 68px;
    object-fit: cover;
    border-radius: 0.25rem;
}

.youtube-info {
    flex: 1;
}

.youtube-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.youtube-channel {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.youtube-duration {
    font-size: 0.75rem;
    color: var(--gray-500);
}

@media (max-width: 1024px) {
    .table-header,
    .table-row {
        grid-template-columns: 60px 1fr 1fr 80px 1fr 80px 120px;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input {
        min-width: auto;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .table-header {
        display: none;
    }
    
    .table-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        padding: 1rem;
    }
    
    .table-cell {
        justify-content: space-between;
        padding: 0.25rem 0;
    }
    
    .table-cell::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .header-actions {
        justify-content: center;
    }
}
</style>

<script>
function openYouTubeImport() {
    document.getElementById('youtubeImportModal').style.display = 'block';
}

function closeYouTubeImport() {
    document.getElementById('youtubeImportModal').style.display = 'none';
}

function switchImportTab(tab) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide content
    document.querySelectorAll('.import-tab').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'ImportTab').classList.add('active');
}

function searchYouTube() {
    const query = document.getElementById('youtubeSearch').value;
    if (!query.trim()) {
        showToast('Please enter a search term', 'warning');
        return;
    }
    
    fetch(`/admin/youtube/search?q=${encodeURIComponent(query.trim())}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayYouTubeResults(data.results);
            } else {
                showToast('Search failed: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('YouTube search error:', error);
            showToast('Search failed', 'error');
        });
}

function displayYouTubeResults(results) {
    const container = document.getElementById('youtubeResults');
    
    if (results.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No results found</p>';
        return;
    }
    
    container.innerHTML = results.map(video => `
        <div class="youtube-result-item">
            <img src="${video.thumbnail}" alt="${escapeHtml(video.title)}" class="youtube-thumbnail">
            <div class="youtube-info">
                <div class="youtube-title">${escapeHtml(video.title)}</div>
                <div class="youtube-channel">${escapeHtml(video.channel_title)}</div>
                ${video.duration ? `<div class="youtube-duration">${video.duration}</div>` : ''}
            </div>
            <button class="btn btn-sm btn-primary" onclick="importVideo('${video.video_id}')">
                <i class="fas fa-download"></i>
                Import
            </button>
        </div>
    `).join('');
}

function importVideo(videoId) {
    fetch('/admin/youtube/import', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ video_id: videoId, type: 'video' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Video imported successfully!');
            // Refresh the page or update the song list
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Import failed: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        showToast('Import failed', 'error');
    });
}

function importPlaylist() {
    const playlistUrl = document.getElementById('playlistUrl').value;
    if (!playlistUrl.trim()) {
        showToast('Please enter a playlist URL', 'warning');
        return;
    }
    
    // Extract playlist ID from URL
    const playlistId = extractPlaylistId(playlistUrl);
    if (!playlistId) {
        showToast('Invalid playlist URL', 'error');
        return;
    }
    
    fetch('/admin/youtube/import', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ playlist_id: playlistId, type: 'playlist' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Playlist imported! Added ${data.imported.length} songs.`);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showToast('Import failed: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Playlist import error:', error);
        showToast('Import failed', 'error');
    });
}

function extractPlaylistId(url) {
    const match = url.match(/[?&]list=([^&]+)/);
    return match ? match[1] : null;
}

function deleteSong(songId) {
    if (!confirm('Are you sure you want to delete this song? This action cannot be undone.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/songs/${songId}/delete`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrfToken);
    
    document.body.appendChild(form);
    form.submit();
}

function previewSong(songId) {
    // Open song preview (could be a modal or new window)
    window.open(`/songs/preview/${songId}`, '_blank', 'width=800,height=600');
}
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>