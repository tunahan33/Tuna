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
require __DIR__ . '/vouchers.php';
require __DIR__ . '/upgrade.php';

date_default_timezone_set(config('timezone') ?: 'Europe/Istanbul');
mb_internal_encoding('UTF-8');
db_upgrade();

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = str_starts_with(config('base_url'), 'https://');
    session_name('gse_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// SSL ve tek adres zorunluluğu (Yönetim > Site Ayarları > Sunucu & Yedek)
// Açıkken http:// ve farklı yazımlar (örn. www'suz) 301 ile site adresine yönlendirilir.
if (PHP_SAPI !== 'cli' && setting('force_https') === '1' && str_starts_with(config('base_url'), 'https://')) {
    $__canonHost = strtolower((string) parse_url(config('base_url'), PHP_URL_HOST));
    $__reqHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if (!request_is_https() || ($__reqHost !== '' && $__reqHost !== $__canonHost)) {
        header('Location: https://' . $__canonHost . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}
if (request_is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

// Güvenlik başlıkları
// Ödeme sonuç sayfası PayTR çerçevesinden dönüşte açılabildiği için çerçeve kısıtı uygulanmaz
if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'odeme-sonuc.php') {
    header('X-Frame-Options: SAMEORIGIN');
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Bakım modu: personel (panel yetkisi olanlar) siteyi normal görür, ziyaretçilere bakım sayfası gösterilir.
// Yönetim paneli, giriş ve banka dönüş adresi her zaman açıktır.
if (PHP_SAPI !== 'cli' && maintenance_active()) {
    $__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $__open = str_contains($__script, '/admin/') || in_array(basename($__script), ['odeme-sonuc.php', 'paytr-bildirim.php', 'robots.php'], true);
    if (!$__open && !can('panel.access')) {
        require __DIR__ . '/maintenance.php';
        exit;
    }
}
