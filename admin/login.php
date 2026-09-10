<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// No account yet? Send them to setup.
if (!is_setup_complete()) {
    header('Location: ' . url('admin/setup.php'));
    exit;
}

// Already logged in? Go to dashboard.
if (is_logged_in()) {
    header('Location: ' . url('admin/'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (attempt_login($username, $password)) {
        header('Location: ' . url('admin/'));
        exit;
    }
    $error = 'Incorrect username or password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in — <?= e(SITE_TITLE) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-screen">
    <div class="login-box">
        <div class="form-card">
            <h1>🔐 Log in</h1>
            <p class="sub">Sign in to edit <?= e(SITE_TITLE) ?>.</p>

            <?php if ($error): ?>
                <div class="flash flash--error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button class="btn btn--block" type="submit">Log in →</button>
            </form>
            <p style="text-align:center;margin-top:18px"><a href="<?= e(url()) ?>">← Back to site</a></p>
        </div>
    </div>
</div>
</body>
</html>
