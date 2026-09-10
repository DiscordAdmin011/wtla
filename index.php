<?php
require_once __DIR__ . '/includes/functions.php';

$categories = get_categories();
$allEntries = get_entries();

$pageTitle = SITE_TITLE;
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head reveal">
    <h1>Welcome 👋</h1>
    <?php if (SITE_TAGLINE !== ''): ?>
        <p><?= e(SITE_TAGLINE) ?></p>
    <?php endif; ?>
</div>

<?php foreach (take_flashes() as $f): ?>
    <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<?php if (empty($categories)): ?>
    <div class="empty">
        <div class="empty__icon">🗂️</div>
        <p>No categories yet.</p>
        <?php if (is_logged_in()): ?>
            <p><a class="btn" href="<?= e(url('admin/category-edit.php')) ?>">+ Create your first category</a></p>
        <?php else: ?>
            <p>Log in to start adding your stuff.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($categories as $i => $cat):
            $count = count(array_filter($allEntries, fn($en) => ($en['categoryId'] ?? '') === $cat['id']));
        ?>
            <a class="card cat-card reveal" style="--i:<?= (int)$i ?>" href="<?= e(url('category.php?c=' . rawurlencode($cat['slug']))) ?>">
                <div class="card__media card__media--emoji"><?= e($cat['icon'] ?? '📦') ?></div>
                <div class="card__body">
                    <h2 class="card__title"><?= e($cat['name']) ?></h2>
                    <?php if (!empty($cat['description'])): ?>
                        <p class="card__desc"><?= e($cat['description']) ?></p>
                    <?php endif; ?>
                    <p class="card__meta"><?= $count ?> item<?= $count === 1 ? '' : 's' ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
