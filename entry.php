<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['e'] ?? '';
$entry = find_entry_by_slug((string) $slug);

if (!$entry) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><div class="empty__icon">🔍</div><p>That item doesn\'t exist.</p><p><a class="btn btn--ghost" href="' . e(url()) . '">← Back home</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$category = find_category($entry['categoryId'] ?? '');
$images = $entry['images'] ?? [];
$specs = $entry['specs'] ?? [];

$activeNav = $category ? 'category:' . $category['slug'] : '';
$pageTitle = $entry['title'] . ' — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="<?= e(url()) ?>">Home</a> /
    <?php if ($category): ?>
        <a href="<?= e(url('category.php?c=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a> /
    <?php endif; ?>
    <?= e($entry['title']) ?>
</div>

<article class="entry-detail reveal">
    <div class="gallery">
        <div class="gallery__main">
            <?php if (!empty($images)): ?>
                <img id="galleryMain" src="<?= e(upload_url($images[0])) ?>" alt="<?= e($entry['title']) ?>">
            <?php else: ?>
                <span class="gallery__placeholder"><?= e($category['icon'] ?? '📦') ?></span>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="gallery__thumbs">
                <?php foreach ($images as $i => $img): ?>
                    <button type="button" class="gallery__thumb <?= $i === 0 ? 'is-active' : '' ?>"
                            data-full="<?= e(upload_url($img)) ?>">
                        <img src="<?= e(upload_url($img)) ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="entry-info">
        <h1><?= e($entry['title']) ?></h1>
        <?php if ($category): ?>
            <div class="entry-cat"><?= e($category['icon'] ?? '') ?> <?= e($category['name']) ?>
                <?php if (!empty($entry['date'])): ?>
                    · <?= e(date('F j, Y', strtotime($entry['date']))) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($entry['description'])): ?>
            <p class="entry-desc"><?= e($entry['description']) ?></p>
        <?php endif; ?>

        <?php if (!empty($specs)): ?>
            <table class="specs">
                <tbody>
                <?php foreach ($specs as $spec):
                    if (($spec['label'] ?? '') === '' && ($spec['value'] ?? '') === '') continue; ?>
                    <tr>
                        <th><?= e($spec['label'] ?? '') ?></th>
                        <td><?= e($spec['value'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <p style="margin-top:24px">
                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/entry-edit.php?id=' . rawurlencode($entry['id']))) ?>">✏️ Edit this item</a>
            </p>
        <?php endif; ?>
    </div>
</article>

<script src="<?= e(url('assets/js/main.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
