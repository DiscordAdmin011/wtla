<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$adminTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/admin_header.php';

$categories = get_categories();
$entries = get_entries();
?>

<div class="admin-head">
    <h1>Dashboard</h1>
    <div style="display:flex;gap:10px">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/category-edit.php')) ?>">+ Category</a>
        <a class="btn btn--sm" href="<?= e(url('admin/entry-edit.php')) ?>">+ Item</a>
    </div>
</div>

<h2 style="font-size:18px;margin:0 0 12px">Categories</h2>
<?php if (empty($categories)): ?>
    <div class="empty" style="padding:34px">
        <p>No categories yet. Create one to group your items (e.g. Phones, Computers).</p>
        <a class="btn" href="<?= e(url('admin/category-edit.php')) ?>">+ New category</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
        <tr><th style="width:44px"></th><th>Name</th><th>Items</th><th>Slug</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat):
            $count = count(array_filter($entries, fn($en) => ($en['categoryId'] ?? '') === $cat['id'])); ?>
            <tr>
                <td style="font-size:24px"><?= e($cat['icon'] ?? '📦') ?></td>
                <td><strong><?= e($cat['name']) ?></strong></td>
                <td><span class="badge"><?= $count ?></span></td>
                <td><code style="font-size:13px;color:var(--text-muted)"><?= e($cat['slug']) ?></code></td>
                <td class="actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/category-edit.php?id=' . rawurlencode($cat['id']))) ?>">Edit</a>
                    <form method="post" action="<?= e(url('admin/delete.php')) ?>" style="display:inline"
                          onsubmit="return confirm('Delete category &quot;<?= e($cat['name']) ?>&quot;? Items inside it will NOT be deleted but will become uncategorised.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="category">
                        <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                        <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2 style="font-size:18px;margin:34px 0 12px">Items</h2>
<?php if (empty($entries)): ?>
    <div class="empty" style="padding:34px">
        <p>No items yet.</p>
        <?php if (!empty($categories)): ?>
            <a class="btn" href="<?= e(url('admin/entry-edit.php')) ?>">+ New item</a>
        <?php else: ?>
            <p style="color:var(--text-muted)">Create a category first.</p>
        <?php endif; ?>
    </div>
<?php else:
    // Sort newest first
    usort($entries, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? '')); ?>
    <table class="table">
        <thead>
        <tr><th style="width:56px"></th><th>Title</th><th>Category</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry):
            $cat = find_category($entry['categoryId'] ?? '');
            $cover = !empty($entry['images']) ? $entry['images'][0] : null; ?>
            <tr>
                <td>
                    <?php if ($cover): ?>
                        <img class="thumb-mini" src="<?= e(upload_url($cover)) ?>" alt="">
                    <?php else: ?>
                        <span style="font-size:22px"><?= e($cat['icon'] ?? '📦') ?></span>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($entry['title']) ?></strong></td>
                <td><?= $cat ? e($cat['name']) : '<span class="badge">Uncategorised</span>' ?></td>
                <td style="color:var(--text-muted);font-size:14px"><?= !empty($entry['date']) ? e(date('M j, Y', strtotime($entry['date']))) : '—' ?></td>
                <td class="actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('entry.php?e=' . rawurlencode($entry['slug']))) ?>" target="_blank">View</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/entry-edit.php?id=' . rawurlencode($entry['id']))) ?>">Edit</a>
                    <form method="post" action="<?= e(url('admin/delete.php')) ?>" style="display:inline"
                          onsubmit="return confirm('Delete &quot;<?= e($entry['title']) ?>&quot;? This also removes its images.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="entry">
                        <input type="hidden" name="id" value="<?= e($entry['id']) ?>">
                        <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
