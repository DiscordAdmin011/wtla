<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// If setup is already done, don't let anyone reset the password here.
if (is_setup_complete()) {
    header('Location: ' . url('admin/login.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $ok = save_json('auth.json', [
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created'       => date('c'),
        ]);
        if ($ok) {
            attempt_login($username, $password);
            flash('Welcome! Your admin account is ready.');
            header('Location: ' . url('admin/'));
            exit;
        }
        $error = 'Could not save credentials. Check that the data/ folder is writable.';
    }
}

$pageTitle = 'Set up admin';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up — <?= e(SITE_TITLE) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-screen">
    <div class="login-box">
        <div class="form-card">
            <h1>Create your admin account</h1>
            <p class="sub">This is the first-time setup. Choose a username and password you'll use to edit your site.</p>

            <?php if ($error): ?>
                <div class="flash flash--error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="field">
                    <label>Password <span class="hint">(min 8 characters)</span></label>
                    <input type="password" name="password" required>
                </div>
                <div class="field">
                    <label>Confirm password</label>
                    <input type="password" name="confirm" required>
                </div>
                <button class="btn btn--block" type="submit">Create account →</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
