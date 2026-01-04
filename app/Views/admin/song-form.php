<?php 
$title = isset($song) ? "Edit Song" : "Add New Song";
$bodyClass = "admin-page";
ob_start();
?>

<div class="page-header">
    <div class="container">
        <div class="header-content">
            <div class="header-info">
                <h1>
                    <i class="fas fa-<?= isset($song) ? 'edit' : 'plus' ?>"></i>
                    <?= isset($song) ? 'Edit Song' : 'Add New Song' ?>
                </h1>
                <p><?= isset($song) ? 'Update song information' : 'Add a new song to the database' ?></p>
            </div>
            <div class="header-actions">
                <a href="/admin/songs" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Songs
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Song Information</h2>
                </div>
                <div class="card-content">
                    <?php if (!empty($errors ?? [])): ?>
                    <div class="error-messages">
                        <?php foreach ($errors ?? [] as $error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="song-form" id="songForm">
                        <?= csrf_field() ?>
                        
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3>Basic Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="song_number">Song Number *</label>
                                    <div class="input-container">
                                        <i class="fas fa-hashtag input-icon"></i>
                                        <input type="number" 
                                               id="song_number" 
                                               name="song_number" 
                                               value="<?= htmlspecialchars($song['song_number'] ?? '') ?>"
                                               required 
                                               min="1"
                                               placeholder="Enter song number">
                                    </div>
                                    <small class="form-help">Unique number for this song (e.g., 001, 002)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="title">Title *</label>
                                    <div class="input-container">
                                        <i class="fas fa-music input-icon"></i>
                                        <input type="text" 
                                               id="title" 
                                               name="title" 
                                               value="<?= htmlspecialchars($song['title'] ?? '') ?>"
                                               required 
                                               maxlength="255"
                                               placeholder="Enter song title">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="artist">Artist *</label>
                                    <div class="input-container">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" 
                                               id="artist" 
                                               name="artist" 
                                               value="<?= htmlspecialchars($song['artist'] ?? '') ?>"
                                               required 
                                               maxlength="255"
                                               placeholder="Enter artist name">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="album">Album</label>
                                    <div class="input-container">
                                        <i class="fas fa-compact-disc input-icon"></i>
                                        <input type="text" 
                                               id="album" 
                                               name="album" 
                                               value="<?= htmlspecialchars($song['album'] ?? '') ?>"
                                               maxlength="255"
                                               placeholder="Enter album name">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="duration">Duration (seconds)</label>
                                    <div class="input-container">
                                        <i class="fas fa-clock input-icon"></i>
                                        <input type="number" 
                                               id="duration" 
                                               name="duration" 
                                               value="<?= htmlspecialchars($song['duration'] ?? '') ?>"
                                               min="0"
                                               max="3600"
                                               placeholder="Duration in seconds">
                                    </div>
                                    <small class="form-help">Duration in seconds (e.g., 180 for 3 minutes)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="year_released">Year Released</label>
                                    <div class="input-container">
                                        <i class="fas fa-calendar input-icon"></i>
                                        <input type="number" 
                                               id="year_released" 
                                               name="year_released" 
                                               value="<?= htmlspecialchars($song['year_released'] ?? '') ?>"
                                               min="1900"
                                               max="<?= date('Y') ?>"
                                               placeholder="Year">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Media Information -->
                        <div class="form-section">
                            <h3>Media Information</h3>
                            
                            <div class="media-tabs">
                                <button type="button" class="tab-btn active" onclick="switchMediaTab('youtube')">
                                    <i class="fab fa-youtube"></i>
                                    YouTube
                                </button>
                                <button type="button" class="tab-btn" onclick="switchMediaTab('file')">
                                    <i class="fas fa-upload"></i>
                                    Upload File
                                </button>
                                <button type="button" class="tab-btn" onclick="switchMediaTab('embed')">
                                    <i class="fas fa-code"></i>
                                    Embed Code
                                </button>
                                <button type="button" class="tab-btn" onclick="switchMediaTab('url')">
                                    <i class="fas fa-link"></i>
                                    External URL
                                </button>
                            </div>
                            
                            <!-- YouTube Tab -->
                            <div id="youtubeTab" class="media-tab active">
                                <div class="form-group">
                                    <label for="youtube_video_id">YouTube Video ID</label>
                                    <div class="input-container">
                                        <i class="fab fa-youtube input-icon"></i>
                                        <input type="text" 
                                               id="youtube_video_id" 
                                               name="youtube_video_id" 
                                               value="<?= htmlspecialchars($song['youtube_video_id'] ?? '') ?>"
                                               placeholder="e.g., dQw4w9WgXcQ">
                                    </div>
                                    <small class="form-help">The video ID from YouTube URL</small>
                                    <?php if ($song['youtube_video_id'] ?? false): ?>
                                    <div class="preview-container">
                                        <iframe width="300" height="169" 
                                                src="https://www.youtube.com/embed/<?= htmlspecialchars($song['youtube_video_id']) ?>"
                                                frameborder="0" allowfullscreen></iframe>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- File Upload Tab -->
                            <div id="fileTab" class="media-tab">
                                <div class="form-group">
                                    <label for="file_path">File Path</label>
                                    <div class="input-container">
                                        <i class="fas fa-file-video input-icon"></i>
                                        <input type="text" 
                                               id="file_path" 
                                               name="file_path" 
                                               value="<?= htmlspecialchars($song['file_path'] ?? '') ?>"
                                               placeholder="/uploads/videos/song.mp4">
                                    </div>
                                    <small class="form-help">Path to uploaded video file</small>
                                </div>
                            </div>
                            
                            <!-- Embed Tab -->
                            <div id="embedTab" class="media-tab">
                                <div class="form-group">
                                    <label for="embed_code">Embed Code</label>
                                    <textarea id="embed_code" 
                                              name="embed_code" 
                                              rows="4"
                                              placeholder="Paste embed code here..."><?= htmlspecialchars($song['embed_code'] ?? '') ?></textarea>
                                    <small class="form-help">HTML embed code from video platforms</small>
                                </div>
                            </div>
                            
                            <!-- External URL Tab -->
                            <div id="urlTab" class="media-tab">
                                <div class="form-group">
                                    <label for="external_url">External URL</label>
                                    <div class="input-container">
                                        <i class="fas fa-link input-icon"></i>
                                        <input type="url" 
                                               id="external_url" 
                                               name="external_url" 
                                               value="<?= htmlspecialchars($song['external_url'] ?? '') ?>"
                                               placeholder="https://example.com/video.mp4">
                                    </div>
                                    <small class="form-help">Direct URL to video file</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-section">
                            <h3>Additional Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="language">Language</label>
                                    <select id="language" name="language">
                                        <option value="">Select Language</option>
                                        <option value="English" <?= ($song['language'] ?? '') == 'English' ? 'selected' : '' ?>>English</option>
                                        <option value="Spanish" <?= ($song['language'] ?? '') == 'Spanish' ? 'selected' : '' ?>>Spanish</option>
                                        <option value="French" <?= ($song['language'] ?? '') == 'French' ? 'selected' : '' ?>>French</option>
                                        <option value="German" <?= ($song['language'] ?? '') == 'German' ? 'selected' : '' ?>>German</option>
                                        <option value="Italian" <?= ($song['language'] ?? '') == 'Italian' ? 'selected' : '' ?>>Italian</option>
                                        <option value="Portuguese" <?= ($song['language'] ?? '') == 'Portuguese' ? 'selected' : '' ?>>Portuguese</option>
                                        <option value="Japanese" <?= ($song['language'] ?? '') == 'Japanese' ? 'selected' : '' ?>>Japanese</option>
                                        <option value="Korean" <?= ($song['language'] ?? '') == 'Korean' ? 'selected' : '' ?>>Korean</option>
                                        <option value="Chinese" <?= ($song['language'] ?? '') == 'Chinese' ? 'selected' : '' ?>>Chinese</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="genre">Genre</label>
                                    <select id="genre" name="genre">
                                        <option value="">Select Genre</option>
                                        <option value="Pop" <?= ($song['genre'] ?? '') == 'Pop' ? 'selected' : '' ?>>Pop</option>
                                        <option value="Rock" <?= ($song['genre'] ?? '') == 'Rock' ? 'selected' : '' ?>>Rock</option>
                                        <option value="Country" <?= ($song['genre'] ?? '') == 'Country' ? 'selected' : '' ?>>Country</option>
                                        <option value="R&B/Soul" <?= ($song['genre'] ?? '') == 'R&B/Soul' ? 'selected' : '' ?>>R&B/Soul</option>
                                        <option value="Jazz" <?= ($song['genre'] ?? '') == 'Jazz' ? 'selected' : '' ?>>Jazz</option>
                                        <option value="Musical Theater" <?= ($song['genre'] ?? '') == 'Musical Theater' ? 'selected' : '' ?>>Musical Theater</option>
                                        <option value="Hip-Hop" <?= ($song['genre'] ?? '') == 'Hip-Hop' ? 'selected' : '' ?>>Hip-Hop</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="lyrics">Lyrics</label>
                                <textarea id="lyrics" 
                                          name="lyrics" 
                                          rows="8"
                                          placeholder="Enter song lyrics..."><?= htmlspecialchars($song['lyrics'] ?? '') ?></textarea>
                                <small class="form-help">Optional: Enter the song lyrics</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_active" id="is_active" 
                                           <?= ($song['is_active'] ?? true) ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                    Active (visible to users)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="/admin/songs" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?= isset($song) ? 'Update Song' : 'Create Song' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="info-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Tips</h3>
                </div>
                <div class="card-content">
                    <div class="tip-list">
                        <div class="tip-item">
                            <i class="fas fa-hashtag"></i>
                            <div>
                                <strong>Song Number</strong>
                                <p>Use a consistent numbering system for easy reference</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fab fa-youtube"></i>
                            <div>
                                <strong>YouTube Integration</strong>
                                <p>Add YouTube videos for instant karaoke content</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-upload"></i>
                            <div>
                                <strong>File Uploads</strong>
                                <p>Upload your own video files for custom content</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-music"></i>
                            <div>
                                <strong>Duration</strong>
                                <p>Accurate duration helps with queue management</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--gray-200);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h3 {
    margin-bottom: 1.5rem;
    color: var(--gray-800);
    font-size: 1.125rem;
    font-weight: 600;
}

.media-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.media-tab {
    display: none;
}

.media-tab.active {
    display: block;
}

.preview-container {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    text-align: center;
}

.preview-container iframe {
    border-radius: var(--border-radius);
}

@media (max-width: 768px) {
    .media-tabs {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        min-width: 120px;
    }
}
</style>

<script>
function switchMediaTab(tabName) {
    // Update active tab button
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide content
    document.querySelectorAll('.media-tab').forEach(content => content.classList.remove('active'));
    document.getElementById(tabName + 'Tab').classList.add('active');
}

// Form validation
document.getElementById('songForm').addEventListener('submit', function(e) {
    const songNumber = document.getElementById('song_number').value;
    const title = document.getElementById('title').value;
    const artist = document.getElementById('artist').value;
    
    let isValid = true;
    let errors = [];
    
    if (!songNumber || songNumber < 1) {
        errors.push('Valid song number is required');
        isValid = false;
    }
    
    if (!title.trim()) {
        errors.push('Song title is required');
        isValid = false;
    }
    
    if (!artist.trim()) {
        errors.push('Artist name is required');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fix the following errors:\n' + errors.join('\n'));
    }
});

// Auto-generate song number
document.addEventListener('DOMContentLoaded', function() {
    const songNumberField = document.getElementById('song_number');
    if (songNumberField && !songNumberField.value) {
        // This would typically fetch the next available number from the API
        // For now, just add a placeholder
        songNumberField.placeholder = 'Auto-generated';
    }
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>