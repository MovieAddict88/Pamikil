<?php $currentPage = 'home'; ?>

<div class="section-header">
    <h1 class="section-title">Karaoke Rooms</h1>
    <div class="section-actions">
        <a href="/solo" class="btn btn-primary">
            <i class="fas fa-microphone"></i> Start Solo Session
        </a>
        <button class="btn btn-secondary create-room-btn">
            <i class="fas fa-plus"></i> Create Room
        </a>
    </div>
</div>

<?php if ($flash = $this->getFlash()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        <span><?php echo htmlspecialchars($flash['message']); ?></span>
    </div>
<?php endif; ?>

<div class="rooms-container">
    <?php if (!empty($rooms)): ?>
        <div class="rooms-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <div class="room-header">
                        <h3 class="room-name"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <div class="room-privacy">
                            <?php if ($room['is_private']): ?>
                                <i class="fas fa-lock" title="Private Room"></i>
                            <?php else: ?>
                                <i class="fas fa-globe" title="Public Room"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="room-info">
                        <div class="info-item">
                            <i class="fas fa-code"></i>
                            <span>Code: <strong><?php echo htmlspecialchars($room['code']); ?></strong></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span>Host: <?php echo htmlspecialchars($room['creator_username']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $room['participant_count']; ?> / <?php echo $room['max_participants']; ?> players</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>Created: <?php echo date('M j, g:i A', strtotime($room['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-signal"></i>
                            <span>Status: <span class="badge badge-<?php echo $room['status']; ?>"><?php echo ucfirst($room['status']); ?></span></span>
                        </div>
                    </div>
                    
                    <div class="room-actions">
                        <button class="btn btn-primary join-room-btn" data-room-code="<?php echo $room['code']; ?>">
                            <i class="fas fa-sign-in-alt"></i> Join Room
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-data">
            <p>No active rooms found. Create your first room!</p>
            <a href="/solo" class="btn btn-primary">
                <i class="fas fa-microphone"></i> Start Solo Session
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($recent_activity)): ?>
    <div class="mt-4">
        <h2 class="section-title">Your Recent Activity</h2>
        <div class="rooms-grid">
            <?php foreach ($recent_activity as $room): ?>
                <div class="room-card">
                    <div class="room-header">
                        <h3 class="room-name"><?php echo htmlspecialchars($room['name']); ?></h3>
                    </div>
                    <div class="room-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span>Host: <?php echo htmlspecialchars($room['creator_username']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>Last active: <?php echo date('M j, g:i A', strtotime($room['updated_at'])); ?></span>
                        </div>
                    </div>
                    <div class="room-actions">
                        <a href="/room/<?php echo $room['code']; ?>" class="btn btn-secondary">
                            <i class="fas fa-history"></i> Rejoin
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Create Room Modal -->
<div id="createRoomModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Room</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form class="ajax-form" action="/room/create" method="POST" id="createRoomForm">
            <div class="form-group">
                <label class="form-label" for="roomName">Room Name</label>
                <input type="text" class="form-control" id="roomName" name="name" required 
                       placeholder="Enter room name" maxlength="255">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="maxParticipants">Max Participants</label>
                <select class="form-control" id="maxParticipants" name="max_participants">
                    <option value="5">5 singers</option>
                    <option value="10" selected>10 singers</option>
                    <option value="15">15 singers</option>
                    <option value="20">20 singers</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_private" value="1">
                    Private Room (requires code to join)
                </label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> Create Room
                </button>
            </div>
        </form>
    </div>
</div>