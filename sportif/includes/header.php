<?php
/** Mağaza üst bölümü. $pageTitle, $pageDesc değişkenleri tanımlanıp dahil edilir. */
$__user = current_user();
$__cats = rows('SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY sort_order, id');
$__title = isset($pageTitle) ? $pageTitle . ' | ' . setting('site_name', 'GS Sportif') : setting('site_name', 'GS Sportif') . ' | ' . setting('site_slogan');
$__desc = $pageDesc ?? setting('site_description');
$__current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__cartCount = cart_count();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__title) ?></title>
<meta name="description" content="<?= e($__desc) ?>">
<link rel="canonical" href="<?= e(rtrim(config('base_url'), '/') . preg_replace('#^' . preg_quote(rtrim((string) parse_url(config('base_url'), PHP_URL_PATH), '/'), '#') . '#', '', strtok($_SERVER['REQUEST_URI'] ?? '/', '#'))) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= e($__title) ?>">
<meta property="og:description" content="<?= e($__desc) ?>">
<meta name="theme-color" content="#2B2F33">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/shop.css') ?>">
</head>
<body>
<div class="topbar">
    <div class="container topbar-inner">
        <span><?php if ((float) setting('free_shipping_limit') > 0): ?>🚚 <?= money(setting('free_shipping_limit')) ?> ve üzeri siparişlerde <strong>kargo bedava</strong><?php endif; ?></span>
        <span class="topbar-right">
            <a href="<?= url('takim-siparisi.php') ?>">Takım Siparişi</a>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('company_phone'))) ?>"><?= e(setting('company_phone')) ?></a>
        </span>
    </div>
</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url() ?>" aria-label="Ana sayfa"><img src="<?= asset('img/logo.svg') ?>" alt="<?= e(setting('site_name', 'GS Sportif')) ?>" width="210" height="48"></a>
        <form class="header-search" action="<?= url('urunler.php') ?>" method="get" role="search">
            <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Ürün ara… (forma, eşofman, tayt)" aria-label="Ürün ara">
            <button aria-label="Ara"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
        </form>
        <div class="header-icons">
            <a href="<?= $__user ? url(can('panel.access', $__user) ? 'admin/' : 'hesabim.php') : url('giris.php') ?>" class="icon-link" aria-label="Hesabım">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span><?= $__user ? e(explode(' ', $__user['name'])[0]) : 'Giriş' ?></span>
            </a>
            <a href="<?= url('sepet.php') ?>" class="icon-link cart-link" aria-label="Sepet">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M6 7h12l1 14H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span>Sepet</span><?php if ($__cartCount): ?><em class="cart-count"><?= $__cartCount ?></em><?php endif; ?>
            </a>
            <button class="nav-toggle" aria-label="Menü" aria-expanded="false" data-nav-toggle><span></span><span></span><span></span></button>
        </div>
    </div>
    <nav class="cat-nav" data-nav>
        <div class="container cat-nav-inner">
            <a href="<?= url('urunler.php') ?>" class="<?= $__current === 'urunler.php' && !input('kategori') ? 'active' : '' ?>">Tüm Ürünler</a>
            <?php foreach ($__cats as $__c): ?>
                <a href="<?= url('urunler.php?kategori=' . urlencode($__c['slug'])) ?>" class="<?= input('kategori') === $__c['slug'] ? 'active' : '' ?>"><?= e($__c['name']) ?></a>
            <?php endforeach; ?>
            <a href="<?= url('urunler.php?indirim=1') ?>" class="hot">İndirimdekiler</a>
            <a href="<?= url('takim-siparisi.php') ?>" class="team <?= $__current === 'takim-siparisi.php' ? 'active' : '' ?>">Takım Siparişi</a>
        </div>
    </nav>
</header>
<main>
<?php if ($__f = flashes()): ?><div class="container flash-wrap"><?= $__f ?></div><?php endif; ?>
