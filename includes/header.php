<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? SITE_TITLE;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(SITE_TAGLINE) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🗂️</text></svg>">
</head>
<body>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url()) ?>">
            <span class="brand__mark">🗂️</span>
            <span class="brand__text">
                <span class="brand__title"><?= e(SITE_TITLE) ?></span>
                <span class="brand__tagline"><?= e(SITE_TAGLINE) ?></span>
            </span>
        </a>
        <nav class="site-nav">
            <a href="<?= e(url()) ?>">Home</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= e(url('admin/')) ?>">Admin</a>
                <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
            <?php else: ?>
                <a href="<?= e(url('admin/login.php')) ?>">Log in</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
