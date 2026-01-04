<?php
/**
 * Rewards Page
 * Shows badges, avatar customization, and achievements
 */

require_once APP_ROOT . '/templates/header.php';

requireAuth('student');

$gamification = new Gamification();
$userId = $auth->getCurrentUser()['id'];

// Get user data
$summary = $gamification->getUserSummary($userId);
$badges = $gamification->getUserBadges($userId);
$allBadges = $gamification->getAllBadges();

// Get avatar items
$ownedItems = $gamification->getUserAvatarItems($userId);
$availableItems = $gamification->getAvatarItems();

// Group items by type
$itemsByType = [];
foreach ($availableItems as $item) {
    $itemsByType[$item['item_type']][] = $item;
}

// Get avatar settings
$avatarSettings = $gamification->getUserAvatarSettings($userId);
?>

<div class="page-header">
    <div class="container">
        <h1>My Rewards</h1>
        <p>View your achievements and customize your avatar</p>
    </div>
</div>

<!-- User Stats -->
<section class="user-stats-section">
    <div class="container">
        <div class="user-stats-grid">
            <div class="user-stat-card">
                <div class="stat-header">
                    <div class="avatar-display">
                        <?php if ($summary['user']['avatar_image']): ?>
                            <img src="<?= htmlspecialchars($summary['user']['avatar_image']) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($summary['user']['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($summary['user']['username']) ?></h3>
                        <p>Level <?= $summary['user']['current_level'] ?></p>
                    </div>
                </div>
                <div class="progress">
                    <div class="progress-bar" 
                         style="width: <?= $summary['level_progress']['progress'] ?>%">
                    </div>
                </div>
                <p class="progress-text">
                    <?= $summary['level_progress']['xp_in_level'] ?> / <?= $summary['level_progress']['xp_needed'] ?> XP
                </p>
            </div>
            
            <div class="user-stat-card">
                <div class="stat-icon">
                    <i class="fa fa-coins"></i>
                </div>
                <h3><?= $summary['user']['coins'] ?></h3>
                <p>Coins Available</p>
            </div>
            
            <div class="user-stat-card">
                <div class="stat-icon">
                    <i class="fa fa-medal"></i>
                </div>
                <h3><?= $summary['badges_count'] ?></h3>
                <p>Badges Earned</p>
            </div>
            
            <div class="user-stat-card">
                <div class="stat-icon">
                    <i class="fa fa-trophy"></i>
                </div>
                <h3><?= $summary['activities_completed'] ?></h3>
                <p>Activities Completed</p>
            </div>
        </div>
    </div>
</section>

<!-- Badges -->
<section class="badges-section">
    <div class="container">
        <div class="section-header">
            <h2>My Badges</h2>
            <span class="badge-count"><?= count($badges) ?>/<?= count($allBadges) ?></span>
        </div>
        
        <?php if (empty($badges)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-medal" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No badges yet</h3>
                <p>Complete activities to earn your first badge!</p>
                <a href="<?= SITE_URL ?>/activities" class="btn btn-primary">Start Learning</a>
            </div>
        <?php else: ?>
            <div class="badges-grid">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge-card earned">
                        <div class="badge-icon">
                            <?php if ($badge['icon']): ?>
                                <img src="<?= htmlspecialchars($badge['icon']) ?>" alt="">
                            <?php else: ?>
                                <i class="fa fa-trophy"></i>
                            <?php endif; ?>
                        </div>
                        <h4><?= htmlspecialchars($badge['name']) ?></h4>
                        <p><?= htmlspecialchars($badge['description']) ?></p>
                        <div class="badge-earned">
                            <i class="fa fa-check-circle"></i>
                            Earned: <?= formatDate($badge['earned_at'], 'M j, Y') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Avatar Customization -->
<section class="avatar-section">
    <div class="container">
        <h2 class="section-title">Avatar Customization</h2>
        
        <div class="avatar-builder">
            <!-- Avatar Preview -->
            <div class="avatar-preview">
                <h3>Preview</h3>
                <div class="avatar-canvas">
                    <div class="avatar-layer avatar-layer-background">
                        <?php if ($avatarSettings && $avatarSettings['background_image']): ?>
                            <img src="<?= htmlspecialchars($avatarSettings['background_image']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="avatar-layer avatar-layer-clothing">
                        <?php if ($avatarSettings && $avatarSettings['clothing_image']): ?>
                            <img src="<?= htmlspecialchars($avatarSettings['clothing_image']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="avatar-layer avatar-layer-hair">
                        <?php if ($avatarSettings && $avatarSettings['hair_image']): ?>
                            <img src="<?= htmlspecialchars($avatarSettings['hair_image']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="avatar-layer avatar-layer-face">
                        <?php if ($avatarSettings && $avatarSettings['face_image']): ?>
                            <img src="<?= htmlspecialchars($avatarSettings['face_image']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="avatar-layer avatar-layer-accessory">
                        <?php if ($avatarSettings && $avatarSettings['accessory_image']): ?>
                            <img src="<?= htmlspecialchars($avatarSettings['accessory_image']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Shop Tabs -->
            <div class="avatar-shop">
                <div class="shop-tabs">
                    <button class="shop-tab active" data-type="hair">Hair</button>
                    <button class="shop-tab" data-type="face">Face</button>
                    <button class="shop-tab" data-type="clothing">Clothing</button>
                    <button class="shop-tab" data-type="accessory">Accessories</button>
                    <button class="shop-tab" data-type="background">Backgrounds</button>
                </div>
                
                <div class="shop-content">
                    <div class="coins-display">
                        <i class="fa fa-coins"></i>
                        <span><?= $summary['user']['coins'] ?> Coins</span>
                    </div>
                    
                    <?php foreach ($itemsByType as $type => $items): ?>
                        <div class="shop-panel" data-panel="<?= $type ?>">
                            <div class="shop-items-grid">
                                <?php foreach ($items as $item): ?>
                                    <?php 
                                    $owned = in_array($item['id'], array_column($ownedItems, 'item_id'));
                                    $canAfford = $summary['user']['coins'] >= $item['cost'];
                                    $meetsLevel = $summary['user']['current_level'] >= $item['min_level_required'];
                                    $equipped = false;
                                    
                                    if ($type === 'hair' && $avatarSettings['hair_id'] == $item['id']) $equipped = true;
                                    if ($type === 'face' && $avatarSettings['face_id'] == $item['id']) $equipped = true;
                                    if ($type === 'clothing' && $avatarSettings['clothing_id'] == $item['id']) $equipped = true;
                                    if ($type === 'accessory' && $avatarSettings['accessory_id'] == $item['id']) $equipped = true;
                                    if ($type === 'background' && $avatarSettings['background_id'] == $item['id']) $equipped = true;
                                    ?>
                                    
                                    <div class="shop-item 
                                         <?= $owned ? 'owned' : '' ?> 
                                         <?= $equipped ? 'equipped' : '' ?>"
                                         data-item-id="<?= $item['id'] ?>"
                                         data-type="<?= $type ?>">
                                        <div class="item-image">
                                            <?php if ($item['image']): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="">
                                            <?php else: ?>
                                                <i class="fa fa-question-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-info">
                                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                                            <p class="item-cost">
                                                <?php if ($item['cost'] > 0): ?>
                                                    <i class="fa fa-coins"></i> <?= $item['cost'] ?>
                                                <?php else: ?>
                                                    Free
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($item['min_level_required'] > 1): ?>
                                                <p class="item-requirement">
                                                    Level <?= $item['min_level_required'] ?>+
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($owned): ?>
                                            <?php if ($equipped): ?>
                                                <button class="btn btn-success btn-sm equipped">
                                                    <i class="fa fa-check"></i> Equipped
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm equip-item">
                                                    Equip
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($canAfford && $meetsLevel): ?>
                                            <button class="btn btn-primary btn-sm buy-item">
                                                Buy
                                            </button>
                                        <?php elseif (!$meetsLevel): ?>
                                            <button class="btn btn-sm" disabled>
                                                Level <?= $item['min_level_required'] ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm" disabled>
                                                Not Enough Coins
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leaderboard Preview -->
<section class="leaderboard-preview">
    <div class="container text-center">
        <h2 class="section-title">Leaderboard</h2>
        <p>See how you rank against other learners!</p>
        <a href="<?= SITE_URL ?>/leaderboard" class="btn btn-primary">
            View Leaderboard <i class="fa fa-arrow-right"></i>
        </a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shop tabs
    const tabs = document.querySelectorAll('.shop-tab');
    const panels = document.querySelectorAll('.shop-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const type = this.dataset.type;
            
            // Update tabs
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update panels
            panels.forEach(panel => {
                panel.style.display = panel.dataset.panel === type ? 'block' : 'none';
            });
        });
    });
    
    // Show first panel
    if (panels.length > 0) {
        panels[0].style.display = 'block';
    }
    
    // Buy item
    document.querySelectorAll('.buy-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemEl = this.closest('.shop-item');
            const itemId = parseInt(itemEl.dataset.itemId);
            
            if (confirm('Purchase this item?')) {
                fetch('/api/purchase-avatar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ item_id: itemId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        App.showToast('Item purchased!', 'success');
                        location.reload();
                    } else {
                        App.showToast(data.error || 'Purchase failed', 'error');
                    }
                });
            }
        });
    });
    
    // Equip item
    document.querySelectorAll('.equip-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemEl = this.closest('.shop-item');
            const itemId = parseInt(itemEl.dataset.itemId);
            const type = itemEl.dataset.type;
            
            fetch('/api/equip-avatar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    item_id: itemId,
                    item_type: type
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    App.showToast('Item equipped!', 'success');
                    location.reload();
                } else {
                    App.showToast(data.error || 'Equip failed', 'error');
                }
            });
        });
    });
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
