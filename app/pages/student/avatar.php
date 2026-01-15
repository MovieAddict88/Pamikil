<?php
declare(strict_types=1);
require_student();

$user = current_user();
$__pageTitle = 'Avatar · ' . (string)config_get('app.name', 'Pamikil Learning');

if (is_post()) {
    Csrf::requireValidToken();

    $action = (string)($_POST['action'] ?? '');
    $itemId = (int)($_POST['item_id'] ?? 0);

    $item = db()->fetch('SELECT * FROM avatar_items WHERE id = :id LIMIT 1', ['id' => $itemId]);
    if (!$item) {
        flash('error', 'That item does not exist.');
        redirect('/student/avatar');
    }

    $slot = (string)$item['slot'];
    $allowedSlots = ['base', 'hat', 'shirt', 'pet', 'bg'];
    if (!in_array($slot, $allowedSlots, true)) {
        flash('error', 'Invalid item slot.');
        redirect('/student/avatar');
    }

    $owned = db()->fetch('SELECT 1 FROM user_avatar_items WHERE user_id = :u AND item_id = :i LIMIT 1', ['u' => (int)$user['id'], 'i' => $itemId]);
    $isDefault = (int)($item['is_default'] ?? 0) === 1;

    if ($action === 'buy') {
        if ($owned || $isDefault) {
            flash('info', 'You already own that item.');
            redirect('/student/avatar');
        }

        $cost = (int)($item['cost_coins'] ?? 0);
        $balance = (int)($user['coins'] ?? 0);

        if ($cost < 0 || $balance < $cost) {
            flash('error', 'Not enough coins.');
            redirect('/student/avatar');
        }

        db()->beginTransaction();
        try {
            db()->execute(
                'INSERT INTO user_avatar_items (user_id, item_id, owned_at) VALUES (:u, :i, NOW())',
                ['u' => (int)$user['id'], 'i' => $itemId]
            );
            db()->execute('UPDATE users SET coins = coins - :c WHERE id = :u', ['c' => $cost, 'u' => (int)$user['id']]);
            db()->execute(
                'INSERT INTO coins_ledger (user_id, delta, reason, ref_type, ref_id, created_at)
                 VALUES (:u, :d, :r, "avatar_item", :id, NOW())',
                ['u' => (int)$user['id'], 'd' => -$cost, 'r' => 'Bought avatar item', 'id' => $itemId]
            );
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        flash('success', 'Item purchased!');
        redirect('/student/avatar');
    }

    if ($action === 'select') {
        if (!$owned && !$isDefault) {
            flash('error', 'You do not own that item yet.');
            redirect('/student/avatar');
        }

        $fieldMap = [
            'base' => 'base_item_id',
            'hat' => 'hat_item_id',
            'shirt' => 'shirt_item_id',
            'pet' => 'pet_item_id',
            'bg' => 'bg_item_id',
        ];

        $field = $fieldMap[$slot];
        db()->execute("UPDATE user_avatar SET $field = :i WHERE user_id = :u", ['i' => $itemId, 'u' => (int)$user['id']]);
        flash('success', 'Avatar updated!');
        redirect('/student/avatar');
    }

    flash('error', 'Unknown action.');
    redirect('/student/avatar');
}

// reload fresh user coins
$user = db()->fetch('SELECT * FROM users WHERE id = :u', ['u' => (int)$user['id']]) ?: $user;

$items = db()->fetchAll('SELECT * FROM avatar_items ORDER BY slot, cost_coins, name');
$ownedRows = db()->fetchAll('SELECT item_id FROM user_avatar_items WHERE user_id = :u', ['u' => (int)$user['id']]);
$ownedIds = array_map(static fn($r) => (int)$r['item_id'], $ownedRows);

$current = db()->fetch('SELECT * FROM user_avatar WHERE user_id = :u', ['u' => (int)$user['id']]);

$avatar = db()->fetch(
    'SELECT
        b.asset_path AS base_asset,
        h.asset_path AS hat_asset,
        s.asset_path AS shirt_asset,
        p.asset_path AS pet_asset,
        bg.asset_path AS bg_asset
     FROM user_avatar ua
     LEFT JOIN avatar_items b ON b.id = ua.base_item_id
     LEFT JOIN avatar_items h ON h.id = ua.hat_item_id
     LEFT JOIN avatar_items s ON s.id = ua.shirt_item_id
     LEFT JOIN avatar_items p ON p.id = ua.pet_item_id
     LEFT JOIN avatar_items bg ON bg.id = ua.bg_item_id
     WHERE ua.user_id = :u',
    ['u' => (int)$user['id']]
);

$slotLabels = [
    'base' => 'Character',
    'hat' => 'Hat',
    'shirt' => 'Outfit',
    'pet' => 'Pet',
    'bg' => 'Background',
];

?>
<section class="page-header">
    <h1>Avatar customization</h1>
    <p class="muted">Coins: <strong><?= (int)$user['coins'] ?></strong> · Buy items to unlock more looks.</p>
</section>

<div class="grid avatar-page">
    <section class="card">
        <h2 class="card__title">Preview</h2>
        <div class="avatar avatar--large" aria-label="Avatar preview">
            <?php if ($avatar && !empty($avatar['bg_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['bg_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['base_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['base_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['shirt_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['shirt_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['hat_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['hat_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['pet_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['pet_asset'], '/'))) ?>"><?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2 class="card__title">Items</h2>

        <?php foreach (['base','bg','shirt','hat','pet'] as $slot): ?>
            <h3><?= e($slotLabels[$slot]) ?></h3>
            <div class="items">
                <?php foreach ($items as $it): ?>
                    <?php if (($it['slot'] ?? '') !== $slot) continue; ?>
                    <?php
                        $itId = (int)$it['id'];
                        $isOwned = in_array($itId, $ownedIds, true) || ((int)($it['is_default'] ?? 0) === 1);
                        $isSelected = $current ? ((int)($current[$slot . '_item_id'] ?? 0) === $itId) : false;
                        $cost = (int)($it['cost_coins'] ?? 0);
                    ?>
                    <div class="item <?= $isSelected ? 'item--selected' : '' ?>">
                        <div class="item__preview" aria-hidden="true">
                            <?php if (!empty($it['asset_path'])): ?>
                                <img alt="" src="<?= e(url('/' . ltrim((string)$it['asset_path'], '/'))) ?>">
                            <?php else: ?>
                                <span class="muted">No preview</span>
                            <?php endif; ?>
                        </div>
                        <div class="item__info">
                            <div class="item__name"><?= e($it['name']) ?></div>
                            <div class="item__meta">
                                <?php if ((int)($it['is_default'] ?? 0) === 1): ?>
                                    <span class="pill pill--success">Free</span>
                                <?php else: ?>
                                    <span class="pill"><?= (int)$cost ?> coins</span>
                                <?php endif; ?>
                                <?php if ($isSelected): ?>
                                    <span class="pill pill--soft">Selected</span>
                                <?php elseif ($isOwned): ?>
                                    <span class="pill pill--success">Owned</span>
                                <?php endif; ?>
                            </div>

                            <form method="post" class="item__actions">
                                <?= Csrf::inputField() ?>
                                <input type="hidden" name="item_id" value="<?= $itId ?>">

                                <?php if ($isOwned): ?>
                                    <button class="btn" name="action" value="select" type="submit">Use</button>
                                <?php else: ?>
                                    <button class="btn btn--primary" name="action" value="buy" type="submit">Buy</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
