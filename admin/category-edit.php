<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$id = $_GET['id'] ?? '';
$category = $id ? find_category((string) $id) : null;
$isNew = !$category;

if ($id && !$category) {
    flash('That category was not found.', 'error');
    header('Location: ' . url('admin/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $hasOrder = isset($_POST['order']) && $_POST['order'] !== '';
    $order = (int) ($_POST['order'] ?? 0);

    if ($name === '') {
        flash('Please give the category a name.', 'error');
    } else {
        $categories = load_json('categories.json', []);

        if ($isNew) {
            $existingSlugs = array_column($categories, 'slug');
            $newCat = [
                'id'          => generate_id(),
                'name'        => $name,
                'slug'        => unique_slug(slugify($name), $existingSlugs),
                'icon'        => $icon !== '' ? $icon : '📦',
                'description' => $description,
                'order'       => $hasOrder ? $order : count($categories) * 10,
                'created'     => date('c'),
            ];
            $categories[] = $newCat;
            save_json('categories.json', $categories);
            flash('Category “' . $name . '” created.');
        } else {
            foreach ($categories as &$c) {
                if ($c['id'] === $category['id']) {
                    // Keep slug stable if name unchanged; regenerate if changed.
                    if (($c['name'] ?? '') !== $name) {
                        $others = array_values(array_filter($categories, fn($x) => $x['id'] !== $c['id']));
                        $c['slug'] = unique_slug(slugify($name), array_column($others, 'slug'));
                    }
                    $c['name'] = $name;
                    $c['icon'] = $icon !== '' ? $icon : '📦';
                    $c['description'] = $description;
                    if ($hasOrder) {
                        $c['order'] = $order;
                    }
                }
            }
            unset($c);
            save_json('categories.json', $categories);
            flash('Category updated.');
        }
        header('Location: ' . url('admin/'));
        exit;
    }
}

$val = fn($k, $d = '') => e($_POST[$k] ?? ($category[$k] ?? $d));

$adminTitle = $isNew ? 'New category' : 'Edit category';
$active = 'category';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-head">
    <h1><?= $isNew ? 'New category' : 'Edit category' ?></h1>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/')) ?>">← Back</a>
</div>

<form method="post" class="form-card">
    <?= csrf_field() ?>
    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="<?= $val('name') ?>" placeholder="e.g. Phones" required autofocus>
    </div>
    <div class="field">
        <label>Icon <span class="hint">(an emoji shown on the card, e.g. 📱 💻 🎧 ⌚)</span></label>
        <input type="text" name="icon" value="<?= $val('icon') ?>" placeholder="📦" maxlength="8" style="max-width:120px;font-size:20px">
    </div>
    <div class="field">
        <label>Description <span class="hint">(optional)</span></label>
        <textarea name="description" placeholder="A short line about this category"><?= $val('description') ?></textarea>
    </div>
    <div class="field">
        <label>Menu order <span class="hint">(lower numbers appear first in the tab bar)</span></label>
        <input type="number" name="order" value="<?= e((string) ($category['order'] ?? '')) ?>" placeholder="auto" style="max-width:120px">
    </div>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isNew ? 'Create category' : 'Save changes' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('admin/')) ?>">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
