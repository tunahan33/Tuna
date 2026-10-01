<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

if (($u = current_user()) && can('panel.access', $u)) {
    redirect('admin/');
}
if (is_post()) {
    verify_csrf();
    if ($u = attempt_login(input('email'), (string) ($_POST['password'] ?? ''))) {
        if (!can('panel.access', $u)) {
            redirect('hesabim.php');
        }
        $to = $_SESSION['intended'] ?? '';
        unset($_SESSION['intended']);
        if ($to && str_contains($to, '/admin/') && str_starts_with($to, '/') && !str_starts_with($to, '//')) {
            header('Location: ' . $to, true, 303);
            exit;
        }
        redirect('admin/');
    }
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Yönetim Girişi · GS Projeler</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo"><img src="<?= asset('img/logo.svg') ?>" alt="GS Projeler" height="50"></div>
    <h1>Yönetim Paneli</h1>
    <?= flashes() ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>E-posta<input type="email" name="email" value="<?= e(input('email')) ?>" required autofocus></label>
        <label>Şifre<input type="password" name="password" required></label>
        <button class="btn btn-primary btn-block btn-lg">Giriş Yap</button>
    </form>
    <p class="center small mt-1"><a href="<?= url() ?>">← Siteye dön</a> · <a href="<?= url('sifremi-unuttum.php') ?>">Şifremi unuttum</a></p>
</div>
</body>
</html>
