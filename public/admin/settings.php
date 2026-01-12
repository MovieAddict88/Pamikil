<?php
/**
 * Admin Settings
 * Manage platform settings
 */

require_once APP_ROOT . '/templates/header.php';

requireAdmin();

$db = Database::getInstance();
$settings = $db->query("SELECT * FROM system_settings ORDER BY setting_key");

$errors = [];
$success = false;

if (isPost()) {
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $admin = new Admin();
        
        // Update each setting
        foreach (post('settings', []) as $key => $value) {
            $result = $admin->updateSetting($key, $value);
            if (!$result) {
                $errors[] = "Failed to update setting: {$key}";
            }
        }
        
        if (empty($errors)) {
            $success = 'Settings updated successfully!';
        }
    }
}
?>

<div class="admin-header">
    <div class="container">
        <div class="admin-nav">
            <h1>System Settings</h1>
            <div class="admin-menu">
                <a href="<?= SITE_URL ?>/admin" class=""><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                <a href="<?= SITE_URL ?>/admin/users"><i class="fa fa-users"></i> Users</a>
                <a href="<?= SITE_URL ?>/admin/activities"><i class="fa fa-tasks"></i> Activities</a>
                <a href="<?= SITE_URL ?>/admin/reports"><i class="fa fa-chart-bar"></i> Reports</a>
                <a href="<?= SITE_URL ?>/admin/settings" class="active"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back to Site</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="container">
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="container">
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Settings Form -->
<section class="admin-settings">
    <div class="container">
        <form method="POST" class="settings-form">
            <?= Security::getCSRFTokenField() ?>
            
            <div class="settings-grid">
                <!-- General Settings -->
                <div class="settings-group">
                    <h3><i class="fa fa-info-circle"></i> General Settings</h3>
                    <div class="settings-items">
                        <?php foreach ($settings as $setting): ?>
                            <?php if (!in_array($setting['setting_key'], ['site_name', 'site_description'])) continue; ?>
                            <?php if (strpos($setting['setting_key'], 'maintenance') !== false): continue; ?>
                            
                            <div class="setting-item">
                                <label for="<?= $setting['setting_key'] ?>">
                                    <?= htmlspecialchars($setting['description']) ?>
                                </label>
                                
                                <?php if ($setting['setting_type'] == 'boolean'): ?>
                                    <div class="toggle-switch">
                                        <input type="checkbox" 
                                               id="<?= $setting['setting_key'] ?>"
                                               name="settings[<?= $setting['setting_key'] ?>]"
                                               value="1"
                                               <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                <?php elseif ($setting['setting_type'] == 'number'): ?>
                                    <input type="number" 
                                           id="<?= $setting['setting_key'] ?>"
                                           name="settings[<?= $setting['setting_key'] ?>]"
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                           class="form-control">
                                <?php else: ?>
                                    <input type="text" 
                                           id="<?= $setting['setting_key'] ?>"
                                           name="settings[<?= $setting['setting_key'] ?>]"
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                           class="form-control">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Gamification Settings -->
                <div class="settings-group">
                    <h3><i class="fa fa-gamepad"></i> Gamification</h3>
                    <div class="settings-items">
                        <?php foreach ($settings as $setting): ?>
                            <?php if (in_array($setting['setting_key'], ['coins_per_level', 'xp_per_level', 'max_daily_activities'])): ?>
                                <div class="setting-item">
                                    <label for="<?= $setting['setting_key'] ?>">
                                        <?= htmlspecialchars($setting['description']) ?>
                                    </label>
                                    <input type="number" 
                                           id="<?= $setting['setting_key'] ?>"
                                           name="settings[<?= $setting['setting_key'] ?>]"
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                           class="form-control">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-group">
                    <h3><i class="fa fa-shield-alt"></i> Security</h3>
                    <div class="settings-items">
                        <?php foreach ($settings as $setting): ?>
                            <?php if (strpos($setting['setting_key'], 'session_timeout') !== false): ?>
                                <div class="setting-item">
                                    <label for="<?= $setting['setting_key'] ?>">
                                        <?= htmlspecialchars($setting['description']) ?>
                                        <small>(in seconds)</small>
                                    </label>
                                    <input type="number" 
                                           id="<?= $setting['setting_key'] ?>"
                                           name="settings[<?= $setting['setting_key'] ?>]"
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                           class="form-control"
                                           min="300" max="7200">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</section>

<style>
.admin-settings {
    padding: var(--spacing-xl) 0;
}

.settings-form {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-xxl);
    padding: var(--spacing-xxl);
}

.settings-group h3 {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
    color: var(--primary);
}

.settings-items {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.setting-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.setting-item label {
    font-weight: 600;
    color: var(--text-primary);
}

.setting-item small {
    color: var(--text-secondary);
    font-size: var(--font-size-xs);
}

.toggle-switch {
    position: relative;
    width: 60px;
    height: 30px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--success);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(30px);
}

.form-actions {
    padding: var(--spacing-xl);
    border-top: 1px solid #e0e0e0;
    text-align: right;
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
