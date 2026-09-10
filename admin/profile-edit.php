<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

$profile = get_profile();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $new = $profile;
    $new['kicker']            = trim($_POST['kicker'] ?? '');
    $new['hero_title']        = trim($_POST['hero_title'] ?? '');
    $new['hero_subtitle']     = trim($_POST['hero_subtitle'] ?? '');
    $new['name']              = trim($_POST['name'] ?? '');
    $new['subtitle']          = trim($_POST['subtitle'] ?? '');
    $new['location']          = trim($_POST['location'] ?? '');
    $new['discord']           = trim($_POST['discord'] ?? '');
    $new['contact_url']       = trim($_POST['contact_url'] ?? '');
    $new['about']             = $_POST['about'] ?? '';
    $new['goals']             = $_POST['goals'] ?? '';
    $new['interests_summary'] = $_POST['interests_summary'] ?? '';
    $new['find_here_title']   = trim($_POST['find_here_title'] ?? '');
    $new['find_here_text']    = $_POST['find_here_text'] ?? '';
    $new['cta_label']         = trim($_POST['cta_label'] ?? '');

    // Tags: comma-separated -> array
    $tags = array_map('trim', explode(',', $_POST['tags'] ?? ''));
    $new['tags'] = array_values(array_filter($tags, fn($t) => $t !== ''));

    // Interests: parallel arrays -> list, dropping empty rows
    $interests = [];
    $icons  = $_POST['interest_icon'] ?? [];
    $titles = $_POST['interest_title'] ?? [];
    $texts  = $_POST['interest_text'] ?? [];
    for ($i = 0; $i < count($titles); $i++) {
        $icon  = trim($icons[$i] ?? '');
        $title = trim($titles[$i] ?? '');
        $text  = trim($texts[$i] ?? '');
        if ($title !== '' || $text !== '') {
            $interests[] = ['icon' => $icon, 'title' => $title, 'text' => $text];
        }
    }
    $new['interests'] = $interests;

    $errors = [];

    // Avatar: optional new upload (single file) + optional removal
    try {
        $uploaded = handle_image_uploads('avatar');
        if (!empty($uploaded)) {
            if (!empty($profile['avatar'])) {
                delete_upload($profile['avatar']);
            }
            $new['avatar'] = $uploaded[0];
        } elseif (!empty($_POST['remove_avatar'])) {
            if (!empty($profile['avatar'])) {
                delete_upload($profile['avatar']);
            }
            $new['avatar'] = '';
        }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if ($new['name'] === '') {
        $errors[] = 'Please enter a name.';
    }

    if (empty($errors)) {
        save_json('profile.json', $new);
        flash('Landing page updated.');
        header('Location: ' . url('admin/profile-edit.php'));
        exit;
    }

    foreach ($errors as $err) {
        flash($err, 'error');
    }
    $profile = $new; // redisplay submitted values
}

$v = fn($k) => e($profile[$k] ?? '');

$adminTitle = 'Landing page';
$active = 'profile';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-head">
    <h1>Landing page</h1>
    <a class="btn btn--ghost btn--sm" href="<?= e(url()) ?>" target="_blank">View site →</a>
</div>

<form method="post" class="form-card" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <h2 style="font-size:16px;margin:0 0 14px">Hero</h2>
    <div class="field">
        <label>Small label above the title <span class="hint">(e.g. "Background")</span></label>
        <input type="text" name="kicker" value="<?= $v('kicker') ?>" style="max-width:320px">
    </div>
    <div class="field">
        <label>Big title</label>
        <input type="text" name="hero_title" value="<?= $v('hero_title') ?>" placeholder="About Ichigo" required>
    </div>
    <div class="field">
        <label>Subtitle</label>
        <input type="text" name="hero_subtitle" value="<?= $v('hero_subtitle') ?>">
    </div>

    <h2 style="font-size:16px;margin:26px 0 14px">Profile card</h2>
    <div class="field">
        <label>Profile picture</label>
        <?php if (!empty($profile['avatar'])): ?>
            <div class="img-manage">
                <div class="img-manage__item" data-img>
                    <img src="<?= e(upload_url($profile['avatar'])) ?>" alt="">
                    <button type="button" class="img-manage__del" data-toggle-remove title="Remove">×</button>
                    <input type="hidden" name="remove_avatar" value="" data-remove-input data-filename="1">
                </div>
            </div>
            <p class="hint">Upload below to replace it, or click ✕ to remove.</p>
        <?php endif; ?>
        <input type="file" name="avatar[]" accept="image/*">
        <p class="hint">Square images look best. If left empty, your initials are shown.</p>
    </div>
    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="<?= $v('name') ?>" style="max-width:320px" required>
    </div>
    <div class="field">
        <label>Card subtitle <span class="hint">(e.g. "Tech &amp; Peripherals")</span></label>
        <input type="text" name="subtitle" value="<?= $v('subtitle') ?>" style="max-width:320px">
    </div>
    <div class="field">
        <label>Location</label>
        <input type="text" name="location" value="<?= $v('location') ?>" style="max-width:320px">
    </div>
    <div class="field">
        <label>Discord <span class="hint">(handle shown with a click-to-copy button)</span></label>
        <input type="text" name="discord" value="<?= $v('discord') ?>" style="max-width:320px">
    </div>
    <div class="field">
        <label>Contact link <span class="hint">(a URL or mailto: — powers the "Get in Touch" buttons; leave blank to hide them)</span></label>
        <input type="text" name="contact_url" value="<?= $v('contact_url') ?>" placeholder="mailto:you@example.com or https://…">
    </div>
    <div class="field">
        <label>Tags <span class="hint">(comma-separated, e.g. Developer, Creator, Hardware Enthusiast)</span></label>
        <input type="text" name="tags" value="<?= e(implode(', ', $profile['tags'])) ?>">
    </div>

    <h2 style="font-size:16px;margin:26px 0 14px">Text panels</h2>
    <p class="hint" style="margin:-6px 0 12px">These support the same formatting as pages: <code>**bold**</code>, <code>*italic*</code>, <code>[link](https://…)</code>, and <code>-</code> bullet lists.</p>
    <div class="field">
        <label>About Me</label>
        <textarea name="about" style="min-height:150px"><?= e($profile['about']) ?></textarea>
    </div>
    <div class="field">
        <label>My Goals</label>
        <textarea name="goals" style="min-height:100px"><?= e($profile['goals']) ?></textarea>
    </div>

    <h2 style="font-size:16px;margin:26px 0 14px">Core interests</h2>
    <div class="spec-rows" id="interestRows">
        <?php
        $rows = !empty($profile['interests']) ? $profile['interests'] : [['icon' => '', 'title' => '', 'text' => '']];
        foreach ($rows as $it): ?>
            <div class="interest-edit-row">
                <input type="text" name="interest_icon[]" value="<?= e($it['icon'] ?? '') ?>" placeholder="Icon (emoji)" class="interest-edit-row__icon">
                <input type="text" name="interest_title[]" value="<?= e($it['title'] ?? '') ?>" placeholder="Title">
                <input type="text" name="interest_text[]" value="<?= e($it['text'] ?? '') ?>" placeholder="Description">
                <button type="button" class="icon-btn" data-remove-interest title="Remove">×</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn--ghost btn--sm" id="addInterest">+ Add interest</button>

    <div class="field" style="margin-top:20px">
        <label>Interests summary <span class="hint">(the muted paragraph under the interest cards)</span></label>
        <textarea name="interests_summary" style="min-height:100px"><?= e($profile['interests_summary']) ?></textarea>
    </div>

    <h2 style="font-size:16px;margin:26px 0 14px">Closing call-to-action</h2>
    <div class="field">
        <label>Heading</label>
        <input type="text" name="find_here_title" value="<?= $v('find_here_title') ?>">
    </div>
    <div class="field">
        <label>Text</label>
        <textarea name="find_here_text" style="min-height:100px"><?= e($profile['find_here_text']) ?></textarea>
    </div>
    <div class="field">
        <label>Button label <span class="hint">(only shows if a contact link is set above)</span></label>
        <input type="text" name="cta_label" value="<?= $v('cta_label') ?>" style="max-width:260px">
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">Save changes</button>
        <a class="btn btn--ghost" href="<?= e(url()) ?>" target="_blank">Preview</a>
    </div>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
