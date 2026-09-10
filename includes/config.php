<?php
/**
 * Global configuration.
 *
 * Feel free to edit SITE_TITLE / SITE_TAGLINE. Everything else is derived
 * automatically so the site works whether it lives at the domain root or in
 * a subfolder.
 */

// ---- Site identity -------------------------------------------------------
define('SITE_TITLE',   "Ichigo's Hub");
define('SITE_TAGLINE', ''); // Leave empty to hide it; add a line here anytime.

// ---- Paths (filesystem) --------------------------------------------------
define('ROOT_PATH',    dirname(__DIR__));
define('DATA_PATH',    ROOT_PATH . '/data');
define('UPLOAD_PATH',  ROOT_PATH . '/uploads');

// ---- Paths (URLs) --------------------------------------------------------
// Works from any subdirectory: figures out the base URL automatically.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
// If we're inside /admin, step back up one level to the site root.
if (substr($scriptDir, -6) === '/admin') {
    $scriptDir = substr($scriptDir, 0, -6);
}
$scriptDir = rtrim($scriptDir, '/');
define('BASE_URL',   $scriptDir === '' ? '/' : $scriptDir . '/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// ---- Uploads -------------------------------------------------------------
define('MAX_UPLOAD_BYTES', 8 * 1024 * 1024); // 8 MB per image
$GLOBALS['ALLOWED_IMAGE_TYPES'] = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

// ---- Sessions ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure the data & uploads folders exist and are writable.
foreach ([DATA_PATH, UPLOAD_PATH] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
