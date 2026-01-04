<?php 
$title = "Create Room";
$bodyClass = "create-room-page";
ob_start();
?>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-door-open"></i> Create New Room</h1>
        <p>Set up your karaoke room and invite friends to join</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Room Details</h2>
                </div>
                <div class="card-content">
                    <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="room-form">
                        <?= csrf_field() ?>
                        
                        <div class="form-group">
                            <label for="name">Room Name *</label>
                            <div class="input-container">
                                <i class="fas fa-door-open input-icon"></i>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars(old('name')) ?>"
                                       required 
                                       minlength="3"
                                       maxlength="100"
                                       placeholder="Enter room name">
                            </div>
                            <small class="form-help">A descriptive name for your karaoke room</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3"
                                      maxlength="500"
                                      placeholder="Tell people what to expect in your room..."><?= htmlspecialchars(old('description')) ?></textarea>
                            <small class="form-help">Optional: Describe your room's theme, music style, or special rules</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_participants">Maximum Participants</label>
                                <div class="input-container">
                                    <i class="fas fa-users input-icon"></i>
                                    <select id="max_participants" name="max_participants">
                                        <option value="10" <?= (old('max_participants') ?? '20') == '10' ? 'selected' : '' ?>>10 people</option>
                                        <option value="20" <?= (old('max_participants') ?? '20') == '20' ? 'selected' : '' ?>>20 people</option>
                                        <option value="30" <?= (old('max_participants') ?? '20') == '30' ? 'selected' : '' ?>>30 people</option>
                                        <option value="50" <?= (old('max_participants') ?? '20') == '50' ? 'selected' : '' ?>>50 people</option>
                                        <option value="100" <?= (old('max_participants') ?? '20') == '100' ? 'selected' : '' ?>>100 people</option>
                                    </select>
                                </div>
                                <small class="form-help">Maximum number of people who can join</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_songs_per_user">Max Songs Per User</label>
                                <div class="input-container">
                                    <i class="fas fa-music input-icon"></i>
                                    <select id="max_songs_per_user" name="max_songs_per_user">
                                        <option value="1" <?= (old('max_songs_per_user') ?? '3') == '1' ? 'selected' : '' ?>>1 song</option>
                                        <option value="2" <?= (old('max_songs_per_user') ?? '3') == '2' ? 'selected' : '' ?>>2 songs</option>
                                        <option value="3" <?= (old('max_songs_per_user') ?? '3') == '3' ? 'selected' : '' ?>>3 songs</option>
                                        <option value="5" <?= (old('max_songs_per_user') ?? '3') == '5' ? 'selected' : '' ?>>5 songs</option>
                                        <option value="10" <?= (old('max_songs_per_user') ?? '3') == '10' ? 'selected' : '' ?>>10 songs</option>
                                    </select>
                                </div>
                                <small class="form-help">How many songs each person can have in queue</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_public" id="is_public" <?= old('is_public') ? 'checked' : 'checked' ?>>
                                <span class="checkmark"></span>
                                Public Room
                            </label>
                            <small class="form-help">Public rooms appear in the room list for everyone to see</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="autoplay" id="autoplay" <?= old('autoplay') ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                Auto-play Next Song
                            </label>
                            <small class="form-help">Automatically start the next song when the current one ends</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_duets" id="allow_duets" <?= old('allow_duets') ? 'checked' : 'checked' ?>>
                                <span class="checkmark"></span>
                                Allow Duets
                            </label>
                            <small class="form-help">Let multiple people sing together</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_approval" id="require_approval" <?= old('require_approval') ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                Require Approval for Songs
                            </label>
                            <small class="form-help">Host must approve songs before they go to the queue</small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="/dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Room
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
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>Room Name</strong>
                                <p>Choose a memorable name that reflects your room's vibe</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>Size Matters</strong>
                                <p>Smaller rooms feel more intimate, larger ones are more energetic</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-crown"></i>
                            <div>
                                <strong>You're the Host</strong>
                                <p>As the creator, you can manage the room and moderate the queue</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-share"></i>
                            <div>
                                <strong>Invite Friends</strong>
                                <p>Share your room code so others can join the fun</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time validation
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const help = this.parentElement.parentElement.querySelector('.form-help');
    
    if (name.length < 3) {
        help.textContent = 'Room name must be at least 3 characters';
        help.style.color = '#dc3545';
    } else if (name.length > 100) {
        help.textContent = 'Room name must be less than 100 characters';
        help.style.color = '#dc3545';
    } else {
        help.textContent = 'A descriptive name for your karaoke room';
        help.style.color = '#6c757d';
    }
});

// Character counter for description
document.getElementById('description').addEventListener('input', function() {
    const maxLength = 500;
    const currentLength = this.value.length;
    const counter = this.parentElement.querySelector('.char-counter');
    
    if (!counter) {
        const counter = document.createElement('small');
        counter.className = 'form-help char-counter';
        this.parentElement.appendChild(counter);
    }
    
    this.parentElement.querySelector('.char-counter').textContent = `${currentLength}/${maxLength} characters`;
    this.parentElement.querySelector('.char-counter').style.color = currentLength > maxLength * 0.9 ? '#dc3545' : '#6c757d';
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>