<?php
/**
 * Shared helpers: JSON storage, auth, CSRF, slugs, flash messages, escaping.
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// Output escaping
// ---------------------------------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------------------
// JSON storage
// ---------------------------------------------------------------------------
function load_json(string $file, $default = [])
{
    $path = DATA_PATH . '/' . $file;

    // First run: seed the live file from the tracked default, if one exists.
    // After this, the live file is owned by the app and never overwritten by
    // a deploy (it's git-ignored), so your edits persist.
    if (!is_file($path)) {
        $seed = DEFAULTS_PATH . '/' . $file;
        if (is_file($seed) && is_dir(DATA_PATH) && is_writable(DATA_PATH)) {
            @copy($seed, $path);
        }
    }

    if (!is_file($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $default;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function save_json(string $file, $data): bool
{
    $path = DATA_PATH . '/' . $file;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    // Atomic write: write to a temp file, then rename.
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $path);
}

function get_categories(): array
{
    $cats = load_json('categories.json', []);
    usort($cats, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $cats;
}

function get_entries(): array
{
    return load_json('entries.json', []);
}

function find_category(string $id): ?array
{
    foreach (get_categories() as $cat) {
        if (($cat['id'] ?? '') === $id) {
            return $cat;
        }
    }
    return null;
}

function find_category_by_slug(string $slug): ?array
{
    foreach (get_categories() as $cat) {
        if (($cat['slug'] ?? '') === $slug) {
            return $cat;
        }
    }
    return null;
}

function find_entry(string $id): ?array
{
    foreach (get_entries() as $entry) {
        if (($entry['id'] ?? '') === $id) {
            return $entry;
        }
    }
    return null;
}

function find_entry_by_slug(string $slug): ?array
{
    foreach (get_entries() as $entry) {
        if (($entry['slug'] ?? '') === $slug) {
            return $entry;
        }
    }
    return null;
}

function entries_for_category(string $categoryId): array
{
    $list = array_filter(get_entries(), fn($en) => ($en['categoryId'] ?? '') === $categoryId);
    usort($list, function ($a, $b) {
        // Newest first by date, falling back to created order.
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    return array_values($list);
}

// ---------------------------------------------------------------------------
// Profile (the landing / "About" page content)
// ---------------------------------------------------------------------------
function profile_defaults(): array
{
    return [
        'kicker'            => 'Background',
        'hero_title'        => 'About Me',
        'hero_subtitle'     => '',
        'name'              => SITE_TITLE,
        'subtitle'          => '',
        'avatar'            => '',
        'location'          => '',
        'discord'           => '',
        'contact_url'       => '',
        'tags'              => [],
        'about'             => '',
        'goals'             => '',
        'interests'         => [],
        'interests_summary' => '',
        'find_here_title'   => "What You'll Find Here",
        'find_here_text'    => '',
        'cta_label'         => 'Get In Touch',
    ];
}

function get_profile(): array
{
    $data = load_json('profile.json', []);
    if (!is_array($data)) {
        $data = [];
    }
    $profile = array_merge(profile_defaults(), $data);
    if (!is_array($profile['tags'])) {
        $profile['tags'] = [];
    }
    if (!is_array($profile['interests'])) {
        $profile['interests'] = [];
    }
    return $profile;
}

// ---------------------------------------------------------------------------
// Pages (free-form tabbed content: About Me, Resources, …)
// ---------------------------------------------------------------------------
function get_pages(bool $publishedOnly = false): array
{
    $pages = load_json('pages.json', []);
    if ($publishedOnly) {
        $pages = array_filter($pages, fn($p) => !empty($p['published']));
    }
    $pages = array_values($pages);
    usort($pages, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $pages;
}

function find_page(string $id): ?array
{
    foreach (get_pages() as $page) {
        if (($page['id'] ?? '') === $id) {
            return $page;
        }
    }
    return null;
}

function find_page_by_slug(string $slug): ?array
{
    foreach (get_pages() as $page) {
        if (($page['slug'] ?? '') === $slug) {
            return $page;
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// Navigation — merges pages + categories into one ordered tab list.
// ---------------------------------------------------------------------------
function nav_items(): array
{
    $items = [];
    foreach (get_pages(true) as $p) {
        $items[] = [
            'key'   => 'page:' . ($p['slug'] ?? ''),
            'label' => ($p['nav_label'] ?? '') !== '' ? $p['nav_label'] : ($p['title'] ?? 'Page'),
            'url'   => url('page.php?p=' . rawurlencode($p['slug'] ?? '')),
            'order' => $p['order'] ?? 0,
        ];
    }
    foreach (get_categories() as $c) {
        $items[] = [
            'key'   => 'category:' . ($c['slug'] ?? ''),
            'label' => $c['name'] ?? 'Category',
            'url'   => url('category.php?c=' . rawurlencode($c['slug'] ?? '')),
            'order' => $c['order'] ?? 0,
        ];
    }
    usort($items, fn($a, $b) => ($a['order'] <=> $b['order']));
    return $items;
}

// ---------------------------------------------------------------------------
// Minimal, safe text formatter for page bodies.
// Escapes everything first, then adds a small markdown-ish subset:
//   # / ## / ### headings, **bold**, *italic*, [text](url), - lists, links.
// Because we escape before transforming, raw HTML can never be injected.
// ---------------------------------------------------------------------------
function md_inline(string $s): string
{
    $s = e($s);
    // [text](url) — only http(s), mailto, or site-relative links.
    $s = preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^\s)]+|mailto:[^\s)]+|\/[^\s)]*)\)/',
        fn($m) => '<a href="' . $m[2] . '" target="_blank" rel="noopener">' . $m[1] . '</a>',
        $s
    );
    $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<!\*)\*([^*\s][^*]*)\*(?!\*)/', '<em>$1</em>', $s);
    // Bare URLs (skip ones already inside an href="" or after >).
    $s = preg_replace(
        '/(?<![">\/])(https?:\/\/[^\s<]+)/',
        '<a href="$1" target="_blank" rel="noopener">$1</a>',
        $s
    );
    return $s;
}

function render_markdown(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') {
        return '';
    }
    $blocks = preg_split('/\n{2,}/', $text);
    $out = [];

    foreach ($blocks as $block) {
        $lines = explode("\n", $block);

        // Bullet list — every line starts with "- " or "* ".
        $isList = true;
        foreach ($lines as $l) {
            if (!preg_match('/^\s*[-*]\s+/', $l)) {
                $isList = false;
                break;
            }
        }
        if ($isList) {
            $items = '';
            foreach ($lines as $l) {
                $items .= '<li>' . md_inline(preg_replace('/^\s*[-*]\s+/', '', $l)) . '</li>';
            }
            $out[] = '<ul>' . $items . '</ul>';
            continue;
        }

        // Heading — a one-line block that starts with #, ##, or ###.
        if (count($lines) === 1 && preg_match('/^(#{1,3})\s+(.*)$/', $lines[0], $m)) {
            $level = strlen($m[1]) + 1; // h2 / h3 / h4
            $out[] = "<h$level>" . md_inline($m[2]) . "</h$level>";
            continue;
        }

        // Paragraph — preserve single line breaks as <br>.
        $out[] = '<p>' . implode('<br>', array_map('md_inline', $lines)) . '</p>';
    }

    return implode("\n", $out);
}

// ---------------------------------------------------------------------------
// IDs & slugs
// ---------------------------------------------------------------------------
function generate_id(): string
{
    return bin2hex(random_bytes(8));
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function unique_slug(string $base, array $existingSlugs): string
{
    $slug = $base;
    $i = 2;
    while (in_array($slug, $existingSlugs, true)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------
function auth_config(): array
{
    return load_json('auth.json', []);
}

function is_setup_complete(): bool
{
    $auth = auth_config();
    return !empty($auth['password_hash']);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['is_admin']);
}

function require_login(): void
{
    if (!is_setup_complete()) {
        header('Location: ' . BASE_URL . 'admin/setup.php');
        exit;
    }
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

function attempt_login(string $username, string $password): bool
{
    $auth = auth_config();
    if (empty($auth['password_hash']) || empty($auth['username'])) {
        return false;
    }
    if (!hash_equals($auth['username'], $username)) {
        return false;
    }
    if (!password_verify($password, $auth['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_user'] = $username;
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------------------------------------------------------------------------
// Image uploads
// ---------------------------------------------------------------------------
/**
 * Handle a multi-file image upload field. Returns array of saved filenames.
 * Throws RuntimeException on invalid files.
 */
function handle_image_uploads(string $field): array
{
    $saved = [];
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $saved;
    }

    $files = $_FILES[$field];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('An image failed to upload (error code ' . $files['error'][$i] . ').');
        }
        if ($files['size'][$i] > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('One image is larger than the ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB limit.');
        }

        $tmp = $files['tmp_name'][$i];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        $allowed = $GLOBALS['ALLOWED_IMAGE_TYPES'];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only JPG, PNG, GIF and WebP images are allowed.');
        }

        $ext = $allowed[$mime];
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOAD_PATH . '/' . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Could not save an uploaded image.');
        }
        $saved[] = $name;
    }

    return $saved;
}

function delete_upload(string $filename): void
{
    // Guard against path traversal — only allow bare filenames.
    if ($filename === '' || strpbrk($filename, '/\\') !== false) {
        return;
    }
    $path = UPLOAD_PATH . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

// ---------------------------------------------------------------------------
// URL helpers
// ---------------------------------------------------------------------------
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function upload_url(string $filename): string
{
    return UPLOAD_URL . rawurlencode($filename);
}
