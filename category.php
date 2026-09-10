<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['c'] ?? '';
$category = find_category_by_slug((string) $slug);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><div class="empty__icon">🔍</div><p>That category doesn\'t exist.</p><p><a class="btn btn--ghost" href="' . e(url()) . '">← Back home</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$entries = entries_for_category($category['id']);
$pageTitle = $category['name'] . ' — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb"><a href="<?= e(url()) ?>">Home</a> / <?= e($category['name']) ?></div>

<div class="page-head">
    <h1><?= e($category['icon'] ?? '📦') ?> <?= e($category['name']) ?></h1>
    <?php if (!empty($category['description'])): ?>
        <p><?= e($category['description']) ?></p>
    <?php endif; ?>
</div>

<?php if (empty($entries)): ?>
    <div class="empty">
        <div class="empty__icon">📭</div>
        <p>Nothing here yet.</p>
        <?php if (is_logged_in()): ?>
            <p><a class="btn" href="<?= e(url('admin/entry-edit.php?category=' . rawurlencode($category['id']))) ?>">+ Add an item</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($entries as $entry):
            $cover = !empty($entry['images']) ? $entry['images'][0] : null;
        ?>
            <a class="card" href="<?= e(url('entry.php?e=' . rawurlencode($entry['slug']))) ?>">
                <div class="card__media <?= $cover ? '' : 'card__media--emoji' ?>">
                    <?php if ($cover): ?>
                        <img src="<?= e(upload_url($cover)) ?>" alt="<?= e($entry['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <?= e($category['icon'] ?? '📦') ?>
                    <?php endif; ?>
                </div>
                <div class="card__body">
                    <h2 class="card__title"><?= e($entry['title']) ?></h2>
                    <?php if (!empty($entry['description'])): ?>
                        <p class="card__desc"><?= e($entry['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($entry['date'])): ?>
                        <p class="card__meta"><?= e(date('M Y', strtotime($entry['date']))) ?></p>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
