<?php
/** Her sayfanın başında yüklenen başlangıç dosyası */

define('ROOT', dirname(__DIR__));

if (!file_exists(ROOT . '/config.php')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('#/(admin|install)(/.*)?$#', '', dirname($script));
    header('Location: ' . rtrim($base, '/') . '/install/');
    exit;
}

$GLOBALS['__config'] = require ROOT . '/config.php';

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';

date_default_timezone_set(config('timezone') ?: 'Europe/Istanbul');
mb_internal_encoding('UTF-8');

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = str_starts_with(config('base_url'), 'https://');
    session_name('gsp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// SSL zorunluluğu (Yönetim > Site Ayarları > Sunucu & Yedek)
if (PHP_SAPI !== 'cli' && setting('force_https') === '1' && str_starts_with(config('base_url'), 'https://') && !request_is_https()) {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? parse_url(config('base_url'), PHP_URL_HOST)) . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
if (request_is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

// Güvenlik başlıkları
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
