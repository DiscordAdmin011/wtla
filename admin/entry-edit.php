<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$categories = get_categories();

$id = $_GET['id'] ?? '';
$entry = $id ? find_entry((string) $id) : null;
$isNew = !$entry;

if ($id && !$entry) {
    flash('That item was not found.', 'error');
    header('Location: ' . url('admin/'));
    exit;
}

if (empty($categories)) {
    flash('Create a category before adding items.', 'error');
    header('Location: ' . url('admin/category-edit.php'));
    exit;
}

// Preselect a category from ?category= when adding a new item.
$preselectCategory = $_GET['category'] ?? ($entry['categoryId'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = trim($_POST['title'] ?? '');
    $categoryId  = trim($_POST['categoryId'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date        = trim($_POST['date'] ?? '');

    // Build specs from parallel arrays, dropping fully-empty rows.
    $specs = [];
    $labels = $_POST['spec_label'] ?? [];
    $values = $_POST['spec_value'] ?? [];
    for ($i = 0; $i < count($labels); $i++) {
        $l = trim($labels[$i] ?? '');
        $v = trim($values[$i] ?? '');
        if ($l !== '' || $v !== '') {
            $specs[] = ['label' => $l, 'value' => $v];
        }
    }

    $errors = [];
    if ($title === '') {
        $errors[] = 'Please give the item a title.';
    }
    if (!find_category($categoryId)) {
        $errors[] = 'Please choose a valid category.';
    }

    // Handle image uploads.
    $newImages = [];
    if (empty($errors)) {
        try {
            $newImages = handle_image_uploads('images');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (empty($errors)) {
        $entries = load_json('entries.json', []);

        // Existing images to keep (for edits): those not marked for removal.
        $existingImages = $entry['images'] ?? [];
        $removeImages = $_POST['remove_image'] ?? [];
        $keptImages = array_values(array_filter($existingImages, fn($img) => !in_array($img, $removeImages, true)));

        // Actually delete removed files from disk.
        foreach ($removeImages as $img) {
            if (in_array($img, $existingImages, true)) {
                delete_upload($img);
            }
        }

        $images = array_merge($keptImages, $newImages);

        if ($isNew) {
            $existingSlugs = array_column($entries, 'slug');
            $entries[] = [
                'id'          => generate_id(),
                'categoryId'  => $categoryId,
                'title'       => $title,
                'slug'        => unique_slug(slugify($title), $existingSlugs),
                'description' => $description,
                'date'        => $date,
                'specs'       => $specs,
                'images'      => $images,
                'created'     => date('c'),
            ];
            save_json('entries.json', $entries);
            flash('“' . $title . '” added.');
        } else {
            foreach ($entries as &$en) {
                if ($en['id'] === $entry['id']) {
                    if (($en['title'] ?? '') !== $title) {
                        $others = array_values(array_filter($entries, fn($x) => $x['id'] !== $en['id']));
                        $en['slug'] = unique_slug(slugify($title), array_column($others, 'slug'));
                    }
                    $en['categoryId']  = $categoryId;
                    $en['title']       = $title;
                    $en['description'] = $description;
                    $en['date']        = $date;
                    $en['specs']       = $specs;
                    $en['images']      = $images;
                }
            }
            unset($en);
            save_json('entries.json', $entries);
            flash('“' . $title . '” updated.');
        }
        header('Location: ' . url('admin/'));
        exit;
    }

    foreach ($errors as $err) {
        flash($err, 'error');
    }
    // Fall through to redisplay the form with submitted values.
    $entry = array_merge($entry ?? [], [
        'title' => $title, 'categoryId' => $categoryId,
        'description' => $description, 'date' => $date, 'specs' => $specs,
    ]);
    $preselectCategory = $categoryId;
}

$val = fn($k, $d = '') => e($entry[$k] ?? $d);
$currentSpecs = $entry['specs'] ?? [];
$currentImages = $entry['images'] ?? [];

$adminTitle = $isNew ? 'New item' : 'Edit item';
$active = 'entry';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-head">
    <h1><?= $isNew ? 'New item' : 'Edit item' ?></h1>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/')) ?>">← Back</a>
</div>

<form method="post" class="form-card" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="field">
        <label>Title</label>
        <input type="text" name="title" value="<?= $val('title') ?>" placeholder="e.g. iPhone 15 Pro" required autofocus>
    </div>

    <div class="field">
        <label>Category</label>
        <select name="categoryId" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['id']) ?>" <?= $preselectCategory === $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['icon'] ?? '') ?> <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Date <span class="hint">(when you got it / relevant date — optional)</span></label>
        <input type="date" name="date" value="<?= $val('date') ?>" style="max-width:220px">
    </div>

    <div class="field">
        <label>Description <span class="hint">(optional)</span></label>
        <textarea name="description" placeholder="Notes, story, why you like it…"><?= $val('description') ?></textarea>
    </div>

    <div class="field">
        <label>Specifications <span class="hint">(key/value pairs, e.g. RAM → 16GB)</span></label>
        <div class="spec-rows" id="specRows">
            <?php
            $rows = $currentSpecs ?: [['label' => '', 'value' => '']];
            foreach ($rows as $spec): ?>
                <div class="spec-row">
                    <input type="text" name="spec_label[]" placeholder="Label (e.g. CPU)" value="<?= e($spec['label'] ?? '') ?>">
                    <input type="text" name="spec_value[]" placeholder="Value (e.g. M3 Pro)" value="<?= e($spec['value'] ?? '') ?>">
                    <button type="button" class="icon-btn" data-remove-spec title="Remove row">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn--ghost btn--sm" id="addSpec">+ Add spec row</button>
    </div>

    <?php if (!empty($currentImages)): ?>
        <div class="field">
            <label>Current images <span class="hint">(click the ✕ to remove on save)</span></label>
            <div class="img-manage">
                <?php foreach ($currentImages as $img): ?>
                    <div class="img-manage__item" data-img>
                        <img src="<?= e(upload_url($img)) ?>" alt="">
                        <button type="button" class="img-manage__del" data-toggle-remove title="Mark for removal">×</button>
                        <input type="hidden" name="remove_image[]" value="" data-remove-input data-filename="<?= e($img) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="field">
        <label><?= empty($currentImages) ? 'Images' : 'Add more images' ?>
            <span class="hint">(JPG/PNG/GIF/WebP, up to <?= (int)(MAX_UPLOAD_BYTES / 1024 / 1024) ?>MB each)</span></label>
        <input type="file" name="images[]" accept="image/*" multiple>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit"><?= $isNew ? 'Add item' : 'Save changes' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('admin/')) ?>">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
