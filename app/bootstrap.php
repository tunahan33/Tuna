<?php
/**
 * Her sayfanın en başında yüklenir.
 * Veritabanı (data/gs.sqlite) yoksa ilk açılışta otomatik oluşturulur ve demo verileri eklenir.
 */

define('ROOT', dirname(__DIR__));

// Gerekli PHP eklentileri yoksa anlaşılır bir uyarı göster
$__missing = array_filter(['pdo_sqlite', 'mbstring'], fn($x) => !extension_loaded($x));
if ($__missing) {
    $msg = 'Sitenin çalışması için PHP eklentileri eksik: ' . implode(', ', $__missing) . '. '
        . 'Windows\'ta siteyi baslat.bat dosyasına çift tıklayarak açın (eksik eklentileri kendisi açar). '
        . 'Hostingde ise PHP ayarlarından bu eklentileri etkinleştirin.';
    if (PHP_SAPI === 'cli') {
        exit($msg . PHP_EOL);
    }
    http_response_code(500);
    exit('<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 16px"><h1>Kurulum eksik</h1><p>' . htmlspecialchars($msg) . '</p></body>');
}
define('DB_FILE', ROOT . '/data/gs.sqlite');

date_default_timezone_set('Europe/Istanbul');
mb_internal_encoding('UTF-8');
ini_set('display_errors', PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli' ? '1' : '0');
error_reporting(E_ALL);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/shop.php';

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('gs_oturum');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
