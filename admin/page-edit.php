<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$id = $_GET['id'] ?? '';
$page = $id ? find_page((string) $id) : null;
$isNew = !$page;

if ($id && !$page) {
    flash('That page was not found.', 'error');
    header('Location: ' . url('admin/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title     = trim($_POST['title'] ?? '');
    $navLabel  = trim($_POST['nav_label'] ?? '');
    $body      = $_POST['body'] ?? '';
    $order     = (int) ($_POST['order'] ?? 0);
    $published = !empty($_POST['published']);

    if ($title === '') {
        flash('Please give the page a title.', 'error');
    } else {
        $pages = load_json('pages.json', []);

        if ($isNew) {
            $existingSlugs = array_column($pages, 'slug');
            $pages[] = [
                'id'        => generate_id(),
                'title'     => $title,
                'nav_label' => $navLabel,
                'slug'      => unique_slug(slugify($navLabel !== '' ? $navLabel : $title), $existingSlugs),
                'body'      => $body,
                'order'     => $order,
                'published' => $published,
                'created'   => date('c'),
            ];
            save_json('pages.json', $pages);
            flash('Page “' . $title . '” created.');
        } else {
            foreach ($pages as &$p) {
                if ($p['id'] === $page['id']) {
                    $newSlugBase = slugify($navLabel !== '' ? $navLabel : $title);
                    if (($p['slug'] ?? '') !== $newSlugBase
                        && slugify(($p['nav_label'] ?? '') !== '' ? $p['nav_label'] : $p['title']) !== $newSlugBase) {
                        $others = array_values(array_filter($pages, fn($x) => $x['id'] !== $p['id']));
                        $p['slug'] = unique_slug($newSlugBase, array_column($others, 'slug'));
                    }
                    $p['title']     = $title;
                    $p['nav_label'] = $navLabel;
                    $p['body']      = $body;
                    $p['order']     = $order;
                    $p['published'] = $published;
                }
            }
            unset($p);
            save_json('pages.json', $pages);
            flash('Page updated.');
        }
        header('Location: ' . url('admin/'));
        exit;
    }

    // Redisplay with submitted values on validation error.
    $page = array_merge($page ?? [], [
        'title' => $title, 'nav_label' => $navLabel, 'body' => $body,
        'order' => $order, 'published' => $published,
    ]);
}

$val = fn($k, $d = '') => e($page[$k] ?? $d);
$isPublished = $isNew ? true : !empty($page['published']);

$adminTitle = $isNew ? 'New page' : 'Edit page';
$active = 'page';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-head">
    <h1><?= $isNew ? 'New page' : 'Edit page' ?></h1>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/')) ?>">← Back</a>
</div>

<form method="post" class="form-card">
    <?= csrf_field() ?>

    <div class="field">
        <label>Title <span class="hint">(the big heading — you can also repeat it in the body)</span></label>
        <input type="text" name="title" value="<?= $val('title') ?>" placeholder="e.g. About Me" required autofocus>
    </div>

    <div class="field">
        <label>Menu label <span class="hint">(short text shown in the top tab bar; defaults to the title)</span></label>
        <input type="text" name="nav_label" value="<?= $val('nav_label') ?>" placeholder="e.g. About" style="max-width:280px">
    </div>

    <div class="field">
        <label>Content</label>
        <p class="hint" style="margin:-2px 0 8px">
            Formatting: <code># Heading</code>, <code>## Smaller heading</code>,
            <code>**bold**</code>, <code>*italic*</code>, <code>[link text](https://…)</code>,
            and lines starting with <code>-</code> become bullet points. Leave a blank line between paragraphs.
        </p>
        <textarea name="body" style="min-height:320px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:14px"><?= e($page['body'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label>Menu order <span class="hint">(lower numbers appear first in the tab bar)</span></label>
        <input type="number" name="order" value="<?= e((string) ($page['order'] ?? 0)) ?>" style="max-width:120px">
    </div>

    <div class="field">
        <label style="display:flex;align-items:center;gap:9px;cursor:pointer">
            <input type="checkbox" name="published" value="1" <?= $isPublished ? 'checked' : '' ?> style="width:auto">
            Published <span class="hint">(unpublished pages are hidden from visitors)</span>
        </label>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit"><?= $isNew ? 'Create page' : 'Save changes' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('admin/')) ?>">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
