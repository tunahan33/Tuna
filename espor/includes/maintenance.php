<?php
/** Bakım modu sayfası (503) */
$__until = setting('maintenance_until');
http_response_code(503);
if ($__until !== '' && ($__ts = strtotime($__until)) > time()) {
    header('Retry-After: ' . max(60, $__ts - time()));
}
header('Cache-Control: no-store');
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Bakımdayız | <?= e(setting('site_name', 'GS Sportif Faaliyetler')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="auth-body">
<div class="auth-card wide center">
    <div class="auth-logo"><img src="<?= asset('img/logo.svg') ?>" alt="GS Sportif Faaliyetler" height="50"></div>
    <h1>Kısa bir bakımdayız</h1>
    <p class="muted"><?= nl2br(e(setting('maintenance_message') ?: 'Size daha iyi hizmet verebilmek için sitemizde çalışma yapıyoruz. Kısa süre içinde tekrar yayında olacağız.')) ?></p>
    <?php if ($__until !== '' && strtotime($__until) > time()): ?>
        <div class="info-box">Tahmini açılış: <strong><?= tr_day_name(date('Y-m-d', strtotime($__until))) ?>, <?= date('d.m.Y H:i', strtotime($__until)) ?></strong></div>
    <?php endif; ?>
    <p class="small">Acil durumlar için:
        <?php if (setting('company_phone')): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a> · <?php endif; ?>
        <a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a></p>
</div>
</body>
</html>
