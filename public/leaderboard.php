<?php
/**
 * Leaderboard Page
 * Shows top performers and user rankings
 */

require_once APP_ROOT . '/templates/header.php';

$gamification = new Gamification();

// Get leaderboard data
$xpLeaderboard = $gamification->getLeaderboard('weekly_xp', 20);
$coinsLeaderboard = $gamification->getLeaderboard('weekly_coins', 20);

// Get current user's position
$userPosition = null;
if ($auth->isLoggedIn() && $auth->isStudent()) {
    $userPosition = $gamification->getUserLeaderboardPosition($auth->getCurrentUser()['id'], 'weekly_xp');
}
?>

<div class="page-header">
    <div class="container">
        <h1>Leaderboard</h1>
        <p>See the top learners this week!</p>
    </div>
</div>

<!-- Leaderboard Type Tabs -->
<section class="leaderboard-tabs">
    <div class="container">
        <div class="lb-tabs">
            <button class="lb-tab active" data-type="xp">XP Leaders</button>
            <button class="lb-tab" data-type="coins">Coin Collectors</button>
        </div>
    </div>
</section>

<!-- User Position -->
<?php if ($userPosition): ?>
<section class="user-position">
    <div class="container">
        <div class="position-card">
            <div class="position-rank #<?= $userPosition['rank'] ?>">
                <span class="rank-number">#<?= $userPosition['rank'] ?></span>
            </div>
            <div class="position-info">
                <h3>Your Position</h3>
                <p><?= $userPosition['score'] ?> XP this week</p>
            </div>
            <a href="<?= SITE_URL ?>/activities" class="btn btn-primary">
                Earn More XP
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- XP Leaderboard -->
<section class="leaderboard-section" id="xp-leaderboard">
    <div class="container">
        <div class="leaderboard-container">
            <div class="leaderboard-header">
                <i class="fa fa-bolt"></i>
                <h2>Weekly XP Leaders</h2>
            </div>
            
            <?php if (empty($xpLeaderboard)): ?>
                <p class="text-center">No entries this week yet. Be the first!</p>
            <?php else: ?>
                <div class="leaderboard-table">
                    <div class="lb-header">
                        <div class="lb-cell lb-cell-rank">Rank</div>
                        <div class="lb-cell lb-cell-user">Learner</div>
                        <div class="lb-cell lb-cell-score">XP Earned</div>
                    </div>
                    
                    <?php foreach ($xpLeaderboard as $i => $entry): ?>
                        <div class="lb-row <?= $auth->isLoggedIn() && $entry['user_id'] == $auth->getCurrentUser()['id'] ? 'highlight' : '' ?>">
                            <div class="lb-cell lb-cell-rank">
                                <?php if ($i < 3): ?>
                                    <span class="rank-badge rank-<?= $i + 1 ?>">
                                        <?= $i + 1 ?>
                                    </span>
                                <?php else: ?>
                                    <span class="rank-number">#<?= $entry['rank'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="lb-cell lb-cell-user">
                                <div class="user-display">
                                    <?php if ($entry['avatar_image']): ?>
                                        <img src="<?= htmlspecialchars($entry['avatar_image']) ?>" 
                                             alt="" class="user-avatar-mini">
                                    <?php else: ?>
                                        <div class="avatar-placeholder-mini">
                                            <?= strtoupper(substr($entry['username'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($entry['username']) ?></span>
                                </div>
                            </div>
                            <div class="lb-cell lb-cell-score">
                                <i class="fa fa-bolt"></i>
                                <?= number_format($entry['score']) ?> XP
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Coins Leaderboard -->
<section class="leaderboard-section" id="coins-leaderboard" style="display: none;">
    <div class="container">
        <div class="leaderboard-container">
            <div class="leaderboard-header">
                <i class="fa fa-coins"></i>
                <h2>Weekly Coin Collectors</h2>
            </div>
            
            <?php if (empty($coinsLeaderboard)): ?>
                <p class="text-center">No entries this week yet. Start earning!</p>
            <?php else: ?>
                <div class="leaderboard-table">
                    <div class="lb-header">
                        <div class="lb-cell lb-cell-rank">Rank</div>
                        <div class="lb-cell lb-cell-user">Learner</div>
                        <div class="lb-cell lb-cell-score">Coins</div>
                    </div>
                    
                    <?php foreach ($coinsLeaderboard as $i => $entry): ?>
                        <div class="lb-row <?= $auth->isLoggedIn() && $entry['user_id'] == $auth->getCurrentUser()['id'] ? 'highlight' : '' ?>">
                            <div class="lb-cell lb-cell-rank">
                                <?php if ($i < 3): ?>
                                    <span class="rank-badge rank-<?= $i + 1 ?>">
                                        <?= $i + 1 ?>
                                    </span>
                                <?php else: ?>
                                    <span class="rank-number">#<?= $entry['rank'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="lb-cell lb-cell-user">
                                <div class="user-display">
                                    <?php if ($entry['avatar_image']): ?>
                                        <img src="<?= htmlspecialchars($entry['avatar_image']) ?>" 
                                             alt="" class="user-avatar-mini">
                                    <?php else: ?>
                                        <div class="avatar-placeholder-mini">
                                            <?= strtoupper(substr($entry['username'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($entry['username']) ?></span>
                                </div>
                            </div>
                            <div class="lb-cell lb-cell-score">
                                <i class="fa fa-coins"></i>
                                <?= number_format($entry['score']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Info Section -->
<section class="leaderboard-info">
    <div class="container">
        <div class="info-cards">
            <div class="info-card">
                <i class="fa fa-sync-alt"></i>
                <h3>Weekly Reset</h3>
                <p>Leaderboard resets every Monday morning. Keep learning to climb the ranks!</p>
            </div>
            <div class="info-card">
                <i class="fa fa-shield-alt"></i>
                <h3>Privacy First</h3>
                <p>Only usernames are shown. Your personal information is always protected.</p>
            </div>
            <div class="info-card">
                <i class="fa fa-gamepad"></i>
                <h3>Fair Play</h3>
                <p>All rankings are based on legitimate activity completion.</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.lb-tab');
    const xpSection = document.getElementById('xp-leaderboard');
    const coinsSection = document.getElementById('coins-leaderboard');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const type = this.dataset.type;
            
            // Update tabs
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update sections
            if (type === 'xp') {
                xpSection.style.display = 'block';
                coinsSection.style.display = 'none';
            } else {
                xpSection.style.display = 'none';
                coinsSection.style.display = 'block';
            }
        });
    });
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
