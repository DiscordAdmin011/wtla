<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/'));
    exit;
}

verify_csrf();

$type = $_POST['type'] ?? '';
$id   = $_POST['id'] ?? '';

if ($type === 'category') {
    $categories = load_json('categories.json', []);
    $before = count($categories);
    $categories = array_values(array_filter($categories, fn($c) => ($c['id'] ?? '') !== $id));

    if (count($categories) < $before) {
        save_json('categories.json', $categories);
        // Orphan the entries that pointed here (don't delete them).
        $entries = load_json('entries.json', []);
        $changed = false;
        foreach ($entries as &$en) {
            if (($en['categoryId'] ?? '') === $id) {
                $en['categoryId'] = '';
                $changed = true;
            }
        }
        unset($en);
        if ($changed) {
            save_json('entries.json', $entries);
        }
        flash('Category deleted.');
    } else {
        flash('Category not found.', 'error');
    }
} elseif ($type === 'entry') {
    $entries = load_json('entries.json', []);
    $target = null;
    foreach ($entries as $en) {
        if (($en['id'] ?? '') === $id) {
            $target = $en;
            break;
        }
    }
    if ($target) {
        // Remove image files.
        foreach ($target['images'] ?? [] as $img) {
            delete_upload($img);
        }
        $entries = array_values(array_filter($entries, fn($e) => ($e['id'] ?? '') !== $id));
        save_json('entries.json', $entries);
        flash('Item deleted.');
    } else {
        flash('Item not found.', 'error');
    }
} elseif ($type === 'page') {
    $pages = load_json('pages.json', []);
    $before = count($pages);
    $pages = array_values(array_filter($pages, fn($p) => ($p['id'] ?? '') !== $id));
    if (count($pages) < $before) {
        save_json('pages.json', $pages);
        flash('Page deleted.');
    } else {
        flash('Page not found.', 'error');
    }
} else {
    flash('Unknown delete request.', 'error');
}

header('Location: ' . url('admin/'));
exit;
