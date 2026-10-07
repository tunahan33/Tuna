<?php
/**
 * Her sayfanın en başında yüklenir.
 * Veritabanı (data/gs.sqlite) yoksa ilk açılışta otomatik oluşturulur ve demo verileri eklenir.
 */

define('ROOT', dirname(__DIR__));
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
