<?php
/**
 * Avatar Customization Page
 */

require_once APP_ROOT . '/templates/header.php';

requireAuth('student');

$gamification = new Gamification();
$userId = $auth->getCurrentUser()['id'];

// Get user data
$summary = $gamification->getUserSummary($userId);

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
        <h1>Avatar</h1>
        <p>Customize your character!</p>
    </div>
</div>

<!-- User Stats -->
<section class="avatar-stats">
    <div class="container">
        <div class="stats-row">
            <div class="avatar-stat-card">
                <div class="avatar-preview-mini">
                    <?php if ($summary['user']['avatar_image']): ?>
                        <img src="<?= htmlspecialchars($summary['user']['avatar_image']) ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($summary['user']['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="stat-info">
                    <h3><?= htmlspecialchars($summary['user']['username']) ?></h3>
                    <p>Level <?= $summary['user']['current_level'] ?></p>
                    <div class="progress">
                        <div class="progress-bar" 
                             style="width: <?= $summary['level_progress']['progress'] ?>%">
                        </div>
                    </div>
                    <p class="progress-text">
                        <?= $summary['level_progress']['xp_in_level'] ?> / <?= $summary['level_progress']['xp_needed'] ?> XP
                    </p>
                </div>
            </div>
            
            <div class="coins-display">
                <i class="fa fa-coins"></i>
                <span><?= $summary['user']['coins'] ?></span>
                <p class="coins-label">Coins Available</p>
            </div>
        </div>
    </div>
</section>

<!-- Avatar Builder -->
<section class="avatar-builder-section">
    <div class="container">
        <div class="builder-grid">
            <!-- Avatar Preview -->
            <div class="avatar-preview-section">
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
                
                <div class="avatar-actions">
                    <a href="<?= SITE_URL ?>/rewards" class="btn btn-primary">
                        <i class="fa fa-shopping-cart"></i> Shop for Items
                    </a>
                    <a href="<?= SITE_URL ?>/profile" class="btn btn-outline">
                        <i class="fa fa-cog"></i> Settings
                    </a>
                </div>
            </div>
            
            <!-- Items Shop -->
            <div class="avatar-shop-section">
                <h3>My Items</h3>
                
                <div class="shop-tabs">
                    <button class="shop-tab active" data-type="hair">Hair</button>
                    <button class="shop-tab" data-type="face">Face</button>
                    <button class="shop-tab" data-type="clothing">Clothing</button>
                    <button class="shop-tab" data-type="accessory">Accessories</button>
                    <button class="shop-tab" data-type="background">Backgrounds</button>
                </div>
                
                <div class="shop-content">
                    <div class="coins-display-mini">
                        <i class="fa fa-coins"></i>
                        <span><?= $summary['user']['coins'] ?> Coins</span>
                    </div>
                    
                    <?php foreach ($itemsByType as $type => $items): ?>
                        <div class="shop-panel" data-panel="<?= $type ?>">
                            <div class="shop-items-grid">
                                <?php foreach ($items as $item): ?>
                                    <?php 
                                    $owned = in_array($item['id'], array_column($ownedItems, 'item_id'));
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
                                            <?php if ($item['cost'] > 0): ?>
                                                <p class="item-cost">
                                                    <i class="fa fa-coins"></i> <?= $item['cost'] ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="item-cost">Free</p>
                                            <?php endif; ?>
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
                                        <?php else: ?>
                                            <button class="btn btn-outline btn-sm shop-btn">
                                                <i class="fa fa-shopping-cart"></i>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Equip item
    document.querySelectorAll('.equip-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemEl = this.closest('.shop-item');
            const itemId = parseInt(itemEl.dataset.itemId);
            const type = itemEl.dataset.type;
            
            fetch('/api/equip-avatar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
            })
            .catch(err => {
                App.showToast('An error occurred', 'error');
            });
        });
    });
    
    // Shop button (for purchasing)
    document.querySelectorAll('.shop-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            window.location.href = '<?= SITE_URL ?>/rewards';
        });
    });
});
</script>

<style>
.avatar-builder-section {
    padding: var(--spacing-xl) 0;
}

.builder-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: var(--spacing-xxl);
}

.avatar-preview-section,
.avatar-shop-section {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-xl);
}

.avatar-stats {
    padding: var(--spacing-xl) 0;
}

.stats-row {
    display: flex;
    gap: var(--spacing-xl);
    align-items: stretch;
}

.avatar-stat-card {
    flex: 1;
    display: flex;
    gap: var(--spacing-lg);
    align-items: center;
}

.avatar-preview-mini {
    width: 100px;
    height: 100px;
    border-radius: var(--radius-full);
    background: var(--bg-dark);
    display: flex;
    align-items: center;
    justify-content: center;
}

.coins-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-xl);
    background: linear-gradient(135deg, #ffd700, #ffb700);
    border-radius: var(--radius-lg);
    min-width: 200px;
}

.coins-display i {
    font-size: 3rem;
    color: white;
    margin-bottom: var(--spacing-sm);
}

.coins-display span {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
}

.coins-display .coins-label {
    color: white;
    opacity: 0.9;
    margin: 0;
    font-size: var(--font-size-sm);
}

.avatar-canvas {
    width: 250px;
    height: 250px;
    margin: 0 auto var(--spacing-xl);
    background: #f0f0f0;
    border-radius: var(--radius-lg);
    position: relative;
    overflow: hidden;
}

.avatar-layer {
    position: absolute;
    width: 100%;
    height: 100%;
}

.avatar-layer img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-layer-hair { z-index: 10; }
.avatar-layer-face { z-index: 20; }
.avatar-layer-clothing { z-index: 15; }
.avatar-layer-accessory { z-index: 30; }
.avatar-layer-background { z-index: 1; }

.avatar-actions {
    display: flex;
    gap: var(--spacing-md);
}

.avatar-actions a {
    flex: 1;
    text-align: center;
}

.shop-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
    flex-wrap: wrap;
}

.shop-tab {
    padding: var(--spacing-sm) var(--spacing-md);
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-weight: 600;
}

.shop-tab:hover,
.shop-tab.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.shop-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--spacing-md);
}

.shop-item {
    background: #f8f9fa;
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    text-align: center;
    transition: all var(--transition-fast);
    border: 2px solid transparent;
}

.shop-item.owned {
    background: #d4edda;
    border-color: var(--success);
}

.shop-item.equipped {
    background: #fff3cd;
    border-color: var(--warning);
}

.shop-item .item-image {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-sm);
    border-radius: var(--radius-md);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.shop-item .item-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.shop-item .item-info h4 {
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-xs);
}

.shop-item .item-cost {
    font-weight: 600;
    color: var(--warning);
    margin-bottom: var(--spacing-xs);
}

.shop-item .item-requirement {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
}

.coins-display-mini {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    background: #f8f9fa;
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.coins-display-mini i {
    color: var(--warning);
    font-size: 1.5rem;
}

.coins-display-mini span {
    font-weight: 600;
    font-size: var(--font-size-lg);
}

@media (max-width: 768px) {
    .builder-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-row {
        flex-direction: column;
    }
    
    .shop-tabs {
        justify-content: center;
    }
    
    .shop-items-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
