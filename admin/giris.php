<?php
require dirname(__DIR__) . '/app/bootstrap.php';

if (can('panel')) {
    redirect('admin/');
}
if (is_post()) {
    verify_csrf();
    if ($u = attempt_login((string) input('email'), (string) input('password'))) {
        if (!can('panel', $u)) {
            flash('error', 'Bu hesabın yönetim paneline erişim yetkisi yok.');
            redirect('hesabim.php');
        }
        redirect('admin/');
    }
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Yönetim Paneli Girişi</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="login-page">
<form method="post" class="login-card">
    <?= csrf_field() ?>
    <img src="<?= asset('img/logo.svg') ?>" alt="GS Sportif Ürünler" width="200">
    <h1>Yönetim Paneli</h1>
    <?= flashes() ?>
    <label>E-posta<input type="email" name="email" value="<?= e(input('email')) ?>" required autofocus autocomplete="username"></label>
    <label>Şifre<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block">Giriş Yap</button>
    <a href="<?= url() ?>" class="muted small">← Siteye dön</a>
</form>
</body>
</html>
