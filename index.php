<?php
require_once __DIR__ . '/includes/functions.php';

$profile = get_profile();

$pageTitle = SITE_TITLE;
$activeNav = 'home';
require __DIR__ . '/includes/header.php';

// Small helper: initials for the avatar fallback.
$initials = '';
foreach (preg_split('/\s+/', trim($profile['name'])) as $word) {
    if ($word !== '') { $initials .= mb_substr($word, 0, 1); }
}
$initials = mb_strtoupper(mb_substr($initials, 0, 2));
?>

<?php foreach (take_flashes() as $f): ?>
    <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>

<div class="landing">
    <div class="blueprint" aria-hidden="true">
        <span style="top:8%;left:6%">+3950</span>
        <span style="top:14%;right:10%">+ mATX</span>
        <span style="top:26%;right:18%">+ &lt;45g</span>
        <span style="top:20%;left:14%">+ 300Ω</span>
        <span style="top:40%;left:4%">+ 3950</span>
        <span style="top:32%;right:6%">+ 0x0A</span>
        <span style="bottom:16%;left:8%">+ &lt;45g</span>
        <span style="bottom:10%;right:14%">+ mATX</span>
    </div>

    <?php if (is_logged_in()): ?>
        <div class="landing-editbar">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/profile-edit.php')) ?>">✏️ Edit this page</a>
        </div>
    <?php endif; ?>

    <section class="hero reveal">
        <?php if ($profile['kicker'] !== ''): ?>
            <p class="hero__kicker"><?= e($profile['kicker']) ?></p>
        <?php endif; ?>
        <h1 class="hero__title"><?= e($profile['hero_title']) ?></h1>
        <?php if ($profile['hero_subtitle'] !== ''): ?>
            <p class="hero__subtitle"><?= e($profile['hero_subtitle']) ?></p>
        <?php endif; ?>
    </section>

    <div class="landing-grid">
        <aside class="profile-card reveal">
            <div class="profile-card__avatar">
                <?php if (!empty($profile['avatar'])): ?>
                    <img src="<?= e(upload_url($profile['avatar'])) ?>" alt="<?= e($profile['name']) ?>">
                <?php else: ?>
                    <span><?= e($initials !== '' ? $initials : '🙂') ?></span>
                <?php endif; ?>
            </div>
            <h2 class="profile-card__name"><?= e($profile['name']) ?></h2>
            <?php if ($profile['subtitle'] !== ''): ?>
                <p class="profile-card__subtitle"><?= e($profile['subtitle']) ?></p>
            <?php endif; ?>

            <div class="profile-rows">
                <?php if ($profile['location'] !== ''): ?>
                    <div class="profile-row">
                        <span class="profile-row__label">📍 Location</span>
                        <span class="profile-row__value"><?= e($profile['location']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($profile['discord'] !== ''): ?>
                    <div class="profile-row">
                        <span class="profile-row__label">🎮 Discord</span>
                        <button type="button" class="profile-row__value copy-btn" data-copy="<?= e($profile['discord']) ?>" title="Click to copy">
                            <?= e($profile['discord']) ?> <span class="copy-icon">⧉</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if ($profile['contact_url'] !== ''): ?>
                    <div class="profile-row">
                        <span class="profile-row__label">✉️ Contact</span>
                        <a class="profile-row__value profile-row__link" href="<?= e($profile['contact_url']) ?>">Get in Touch →</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($profile['tags'])): ?>
                <div class="profile-tags">
                    <?php foreach ($profile['tags'] as $tag): ?>
                        <span class="profile-tag"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <div class="panels">
            <?php if ($profile['about'] !== ''): ?>
                <section class="panel reveal">
                    <h3 class="panel__title panel__title--rule">About Me</h3>
                    <div class="panel__body"><?= render_markdown($profile['about']) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($profile['goals'] !== ''): ?>
                <section class="panel panel--accent reveal">
                    <h3 class="panel__title">My Goals</h3>
                    <div class="panel__body"><?= render_markdown($profile['goals']) ?></div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($profile['interests']) || $profile['interests_summary'] !== ''): ?>
        <section class="interests">
            <h2 class="section-title reveal">Core Interests</h2>
            <?php if (!empty($profile['interests'])): ?>
                <div class="interest-grid">
                    <?php foreach ($profile['interests'] as $i => $interest): ?>
                        <article class="interest-card reveal" style="--i:<?= (int) $i ?>">
                            <div class="interest-card__icon"><?= e($interest['icon'] ?? '✦') ?></div>
                            <div class="interest-card__text">
                                <h3><?= e($interest['title'] ?? '') ?></h3>
                                <p><?= nl2br(e($interest['text'] ?? '')) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($profile['interests_summary'] !== ''): ?>
                <div class="panel panel--muted reveal">
                    <div class="panel__body"><?= render_markdown($profile['interests_summary']) ?></div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($profile['find_here_text'] !== '' || $profile['find_here_title'] !== ''): ?>
        <section class="cta-panel reveal">
            <?php if ($profile['find_here_title'] !== ''): ?>
                <h2><?= e($profile['find_here_title']) ?></h2>
            <?php endif; ?>
            <?php if ($profile['find_here_text'] !== ''): ?>
                <div class="cta-panel__body"><?= render_markdown($profile['find_here_text']) ?></div>
            <?php endif; ?>
            <?php if ($profile['contact_url'] !== '' && $profile['cta_label'] !== ''): ?>
                <a class="btn btn--lg" href="<?= e($profile['contact_url']) ?>"><?= e($profile['cta_label']) ?></a>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
