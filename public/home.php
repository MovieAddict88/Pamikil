<?php
/**
 * Home Page
 * 
 * Landing page for the learning platform
 */

require_once APP_ROOT . '/templates/header.php';

// Get featured activities
$activity = new Activity();
$featuredActivities = $activity->getFeatured(6);

// Get subjects
$db = Database::getInstance();
$subjects = $db->query("SELECT * FROM subjects WHERE is_active = 1 ORDER BY display_order");

// Get welcome message
$db = Database::getInstance();
$welcomeMsg = $db->queryOne("SELECT setting_value FROM system_settings WHERE setting_key = 'welcome_message'");
$welcomeMessage = $welcomeMsg ? $welcomeMsg['setting_value'] : 'Welcome to Pamikil Learning!';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Learn, Play, Grow!</h1>
        <p class="hero-subtitle"><?= htmlspecialchars($welcomeMessage) ?></p>
        <?php if ($auth->isLoggedIn() && $auth->isStudent()): ?>
            <a href="<?= SITE_URL ?>/activities" class="btn btn-primary btn-lg">Start Learning</a>
        <?php elseif ($auth->isLoggedIn() && $auth->isParent()): ?>
            <a href="<?= SITE_URL ?>/dashboard" class="btn btn-primary btn-lg">Parent Dashboard</a>
        <?php elseif ($auth->isLoggedIn() && $auth->isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin" class="btn btn-primary btn-lg">Admin Panel</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/register" class="btn btn-primary btn-lg">Get Started Free</a>
        <?php endif; ?>
    </div>
    <div class="hero-image">
        <div class="hero-illustration">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title">Why Choose Pamikil?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎮</div>
                <h3>Fun Activities</h3>
                <p>7 different activity types including quizzes, stories, puzzles, and more!</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Earn Rewards</h3>
                <p>Collect coins, earn badges, and unlock cool items for your avatar!</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Track Progress</h3>
                <p>Parents can monitor learning progress and set time limits.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3>Customize</h3>
                <p>Create your unique avatar with hundreds of items to choose from!</p>
            </div>
        </div>
    </div>
</section>

<!-- Subjects Section -->
<section class="subjects-section">
    <div class="container">
        <h2 class="section-title">Explore Subjects</h2>
        <div class="subjects-grid">
            <?php foreach ($subjects as $subject): ?>
                <a href="<?= SITE_URL ?>/activities?subject=<?= $subject['id'] ?>" class="subject-card" style="border-color: <?= $subject['color'] ?>">
                    <div class="subject-icon" style="background-color: <?= $subject['color'] ?>">
                        <i class="fa <?= $subject['icon'] ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($subject['name']) ?></h3>
                    <p><?= htmlspecialchars($subject['description']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Activities Section -->
<section class="activities-section">
    <div class="container">
        <h2 class="section-title">Featured Activities</h2>
        <div class="activities-grid">
            <?php foreach ($featuredActivities as $activity): ?>
                <a href="<?= SITE_URL ?>/activity/<?= $activity['slug'] ?>" class="activity-card">
                    <div class="activity-thumbnail">
                        <?php if ($activity['thumbnail']): ?>
                            <img src="<?= htmlspecialchars($activity['thumbnail']) ?>" alt="<?= htmlspecialchars($activity['title']) ?>">
                        <?php else: ?>
                            <div class="activity-placeholder" style="background-color: <?= $activity['subject_color'] ?>">
                                <span><?= substr($activity['title'], 0, 2) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="activity-info">
                        <span class="activity-type"><?= ucfirst($activity['activity_type']) ?></span>
                        <h3><?= htmlspecialchars($activity['title']) ?></h3>
                        <p class="activity-meta">
                            <span><i class="fa fa-star"></i> <?= $activity['difficulty_name'] ?></span>
                            <span><i class="fa fa-coins"></i> +<?= $activity['coin_reward'] ?></span>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/activities" class="btn btn-secondary">View All Activities</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Start Learning?</h2>
            <p>Join thousands of kids learning and having fun!</p>
            <?php if (!$auth->isLoggedIn()): ?>
                <div class="cta-buttons">
                    <a href="<?= SITE_URL ?>/register" class="btn btn-primary btn-lg">Sign Up Free</a>
                    <a href="<?= SITE_URL ?>/login" class="btn btn-outline btn-lg">Log In</a>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/activities" class="btn btn-primary btn-lg">Start Learning Now</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
