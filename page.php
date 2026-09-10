<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['p'] ?? '';
$page = find_page_by_slug((string) $slug);

// Only show published pages to the public; the owner can preview drafts.
if (!$page || (empty($page['published']) && !is_logged_in())) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><div class="empty__icon">🔍</div><p>That page doesn\'t exist.</p><p><a class="btn btn--ghost" href="' . e(url()) . '">← Back home</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$activeNav = 'page:' . $page['slug'];
$pageTitle = $page['title'] . ' — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<article class="prose reveal">
    <?php if (empty($page['published']) && is_logged_in()): ?>
        <div class="flash flash--error" style="margin-bottom:20px">This page is a <strong>draft</strong> — only you can see it.</div>
    <?php endif; ?>

    <?= render_markdown($page['body'] ?? '') ?>

    <?php if (is_logged_in()): ?>
        <p style="margin-top:32px">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/page-edit.php?id=' . rawurlencode($page['id']))) ?>">✏️ Edit this page</a>
        </p>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
