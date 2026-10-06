<?php
/** Site üst bölümü. Kullanım: $pageTitle, $pageDesc değişkenleri tanımlanıp dahil edilir. */
$__user = current_user();
$__services = rows('SELECT slug, title, icon FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$__title = isset($pageTitle) ? $pageTitle . ' | ' . setting('site_name', 'GS Sportif Faaliyetler') : setting('site_name', 'GS Sportif Faaliyetler') . ' | E-Spor Koçluk';
$__desc = $pageDesc ?? setting('site_description');
$__current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__title) ?></title>
<meta name="description" content="<?= e($__desc) ?>">
<link rel="canonical" href="<?= e(rtrim(config('base_url'), '/') . preg_replace('#^' . preg_quote(rtrim((string) parse_url(config('base_url'), PHP_URL_PATH), '/'), '#') . '#', '', strtok($_SERVER['REQUEST_URI'] ?? '/', '#'))) ?>">
<meta property="og:url" content="<?= e(config('base_url')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= e($__title) ?>">
<meta property="og:description" content="<?= e($__desc) ?>">
<meta name="theme-color" content="#0D0F12">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
</head>
<body class="site">
<div class="topbar">
    <div class="container topbar-inner">
        <span><?= e(setting('working_hours')) ?></span>
        <span class="topbar-right">
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a>
            <a href="mailto:<?= e(setting('company_email')) ?>"><?= e(setting('company_email')) ?></a>
        </span>
    </div>
</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url() ?>" aria-label="Ana sayfa"><img src="<?= asset('img/logo-light.svg') ?>" alt="GS Sportif Faaliyetler" width="330" height="60"></a>
        <button class="nav-toggle" aria-label="Menü" aria-expanded="false" data-nav-toggle><span></span><span></span><span></span></button>
        <nav class="main-nav" data-nav>
            <a href="<?= url() ?>" class="<?= $__current === 'index.php' ? 'active' : '' ?>">Ana Sayfa</a>
            <div class="dropdown">
                <a href="<?= url('hizmetler.php') ?>" class="<?= in_array($__current, ['hizmetler.php', 'hizmet.php', 'paket.php']) ? 'active' : '' ?>">Koçluk Türleri <span class="caret">▾</span></a>
                <div class="dropdown-menu">
                    <?php foreach ($__services as $__s): ?>
                        <a href="<?= url('hizmet.php?slug=' . urlencode($__s['slug'])) ?>"><span class="dd-icon"><?= service_icon($__s['icon']) ?></span><?= e($__s['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?= url('paketler.php') ?>" class="<?= $__current === 'paketler.php' ? 'active' : '' ?>">Paketler &amp; Fiyatlar</a>
            <a href="<?= url('sayfa.php?s=hakkimizda') ?>" class="<?= ($__current === 'sayfa.php' && ($_GET['s'] ?? '') === 'hakkimizda') ? 'active' : '' ?>">Hakkımızda</a>
            <a href="<?= url('sayfa.php?s=sikca-sorulan-sorular') ?>" class="<?= ($__current === 'sayfa.php' && ($_GET['s'] ?? '') === 'sikca-sorulan-sorular') ? 'active' : '' ?>">SSS</a>
            <a href="<?= url('iletisim.php') ?>" class="<?= $__current === 'iletisim.php' ? 'active' : '' ?>">İletişim</a>
            <?php if ($__user): ?>
                <?php if (can('panel.access', $__user)): ?>
                    <a class="btn btn-dark btn-sm" href="<?= url('admin/') ?>">Yönetim Paneli</a>
                <?php else: ?>
                    <a class="btn btn-dark btn-sm" href="<?= url('hesabim.php') ?>">Hesabım</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn btn-outline btn-sm" href="<?= url('giris.php') ?>">Giriş</a>
                <a class="btn btn-primary btn-sm" href="<?= url('kayit.php') ?>">Üye Ol</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php if ($__f = flashes()): ?><div class="container flash-wrap"><?= $__f ?></div><?php endif; ?>
