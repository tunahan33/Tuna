<?php
/** Web kurulum sihirbazı - hostingde tarayıcıdan /install/ adresine girerek çalıştırılır */

require __DIR__ . '/installer.php';

$root = dirname(__DIR__);
// config.php varsa site kurulu kabul edilir; kurulum sihirbazı bir daha çalışmaz (yeniden yüklense bile)
$locked = file_exists($root . '/config.php');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$path = rtrim(preg_replace('#/install(/.*)?$#', '', dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php')), '/');
$detectedUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;

$checks = [
    'PHP 8.1 veya üzeri' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL eklentisi' => extension_loaded('pdo_mysql'),
    'mbstring eklentisi' => extension_loaded('mbstring'),
    'OpenSSL eklentisi' => extension_loaded('openssl'),
    'Ana klasör yazılabilir (config.php için)' => is_writable($root),
];

$error = null;
$done = false;
$v = fn($k, $d = '') => htmlspecialchars($_POST[$k] ?? $d, ENT_QUOTES, 'UTF-8');

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = [
        'db' => [
            'driver' => 'mysql',
            'host' => trim($_POST['db_host'] ?? 'localhost'),
            'port' => (int) ($_POST['db_port'] ?? 3306),
            'name' => trim($_POST['db_name'] ?? ''),
            'user' => trim($_POST['db_user'] ?? ''),
            'pass' => $_POST['db_pass'] ?? '',
            'sqlite' => $root . '/storage/database.sqlite',
        ],
        'base_url' => rtrim(trim($_POST['base_url'] ?? $detectedUrl), '/'),
        'app_key' => bin2hex(random_bytes(32)),
        'timezone' => 'Europe/Istanbul',
        'debug' => false,
    ];
    $admin = [
        'name' => trim($_POST['admin_name'] ?? ''),
        'email' => trim($_POST['admin_email'] ?? ''),
        'phone' => trim($_POST['admin_phone'] ?? ''),
        'password' => $_POST['admin_password'] ?? '',
    ];

    if (!$admin['name'] || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Süper admin adı ve geçerli bir e-posta girin.';
    } elseif (strlen($admin['password']) < 8) {
        $error = 'Süper admin şifresi en az 8 karakter olmalıdır.';
    } elseif ($admin['password'] !== ($_POST['admin_password2'] ?? '')) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        try {
            install_run($config, $admin);
            install_write_config($config);
            file_put_contents(__DIR__ . '/install.lock', date('c'));
            $done = true;
        } catch (Throwable $e) {
            $error = 'Kurulum hatası: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>GS Sportif Faaliyetler Kurulum</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-card wide">
    <div class="auth-logo"><img src="../assets/img/logo.svg" alt="GS Sportif Faaliyetler" height="48"></div>
    <h1>Kurulum Sihirbazı</h1>
    <?php if ($locked): ?>
        <div class="alert alert-info">Kurulum zaten tamamlanmış. Güvenliğiniz için <code>install</code> klasörünü sunucudan silebilirsiniz.</div>
        <a class="btn btn-primary" href="../admin/login.php">Yönetim Paneline Git</a>
    <?php elseif ($done): ?>
        <div class="alert alert-success">Kurulum başarıyla tamamlandı!</div>
        <ol class="steps">
            <li>Güvenlik için FTP / Dosya Yöneticisi ile <code>install</code> klasörünü silin.</li>
            <li>Yönetim panelinde <strong>Site Ayarları</strong>'ndan firma ünvanı, adres, vergi ve MERSİS bilgilerinizi girin (Garanti başvurusu için zorunludur).</li>
            <li>Garanti BBVA'dan gelen Üye İşyeri No, Terminal No, Provizyon Şifresi ve 3D Store Key bilgilerini <strong>Ödeme (Sanal POS)</strong> sekmesine girin.</li>
        </ol>
        <a class="btn btn-primary" href="../admin/login.php">Yönetim Paneline Giriş Yap</a>
    <?php else: ?>
        <h3>Sunucu Kontrolleri</h3>
        <ul class="checklist">
            <?php foreach ($checks as $label => $ok): ?>
                <li class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✔' : '✖' ?> <?= $label ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" class="form">
            <h3>1. Veritabanı (cPanel &gt; MySQL Veritabanları)</h3>
            <div class="grid-2">
                <label>Sunucu<input name="db_host" value="<?= $v('db_host', 'localhost') ?>" required></label>
                <label>Port<input name="db_port" value="<?= $v('db_port', '3306') ?>" required></label>
                <label>Veritabanı Adı<input name="db_name" value="<?= $v('db_name') ?>" required></label>
                <label>Kullanıcı Adı<input name="db_user" value="<?= $v('db_user') ?>" required></label>
            </div>
            <label>Veritabanı Şifresi<input type="password" name="db_pass" value="<?= $v('db_pass') ?>"></label>
            <h3>2. Site Adresi</h3>
            <label>Sitenin tam adresi<input name="base_url" value="<?= $v('base_url', $detectedUrl) ?>" required></label>
            <h3>3. Süper Admin Hesabı</h3>
            <div class="grid-2">
                <label>Ad Soyad<input name="admin_name" value="<?= $v('admin_name') ?>" required></label>
                <label>E-posta<input type="email" name="admin_email" value="<?= $v('admin_email') ?>" required></label>
                <label>Şifre (en az 8 karakter)<input type="password" name="admin_password" required minlength="8"></label>
                <label>Şifre Tekrar<input type="password" name="admin_password2" required minlength="8"></label>
            </div>
            <button class="btn btn-primary btn-block" <?= in_array(false, $checks, true) ? 'disabled' : '' ?>>Kurulumu Başlat</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
