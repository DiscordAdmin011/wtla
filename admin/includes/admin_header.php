<?php
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_login();

$adminTitle = $adminTitle ?? 'Admin';
$active = $active ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?> — <?= e(SITE_TITLE) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛠️</text></svg>">
</head>
<body class="admin-body">
<div class="admin-wrap">
    <aside class="admin-side">
        <a class="admin-side__brand" href="<?= e(url('admin/')) ?>">🛠️ <?= e(SITE_TITLE) ?></a>
        <nav>
            <a href="<?= e(url('admin/')) ?>" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>">Dashboard</a>
            <a href="<?= e(url('admin/category-edit.php')) ?>" class="<?= $active === 'category' ? 'is-active' : '' ?>">+ New category</a>
            <a href="<?= e(url('admin/entry-edit.php')) ?>" class="<?= $active === 'entry' ? 'is-active' : '' ?>">+ New item</a>
        </nav>
        <div class="admin-side__foot">
            <a href="<?= e(url()) ?>">← View site</a><br><br>
            <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
        </div>
    </aside>
    <main class="admin-main">
        <?php foreach (take_flashes() as $f): ?>
            <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
