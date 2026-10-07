<?php
/** Site üst bölümü. Sayfada $title (ve isteğe bağlı $description) tanımlanıp dahil edilir. */
$__u = current_user();
$__cats = rows('SELECT slug, name FROM categories ORDER BY sort, id');
$__site = setting('site_name', 'GS Sportif Ürünler');
$__title = isset($title) ? $title . ' | ' . $__site : $__site . ' | ' . setting('site_slogan');
$__count = cart_count();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__title) ?></title>
<meta name="description" content="<?= e($description ?? setting('site_slogan')) ?>">
<meta name="theme-color" content="#2B2F33">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
</head>
<body>
<?php if ($__a = setting('announcement')): ?><div class="announce"><?= e($__a) ?></div><?php endif; ?>
<header class="header">
    <div class="container header-row">
        <a class="logo" href="<?= url() ?>" aria-label="<?= e($__site) ?> ana sayfa">
            <img src="<?= asset('img/logo.svg') ?>" alt="<?= e($__site) ?>" width="190" height="44">
        </a>
        <form class="search" action="<?= url('urunler.php') ?>" role="search">
            <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Forma, krampon, eşofman ara…" aria-label="Ürün ara">
            <button aria-label="Ara"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
        </form>
        <nav class="header-icons">
            <?php if ($__u && can('panel', $__u)): ?>
                <a class="icon-btn panel-btn" href="<?= url('admin/') ?>">Panel</a>
            <?php endif; ?>
            <a class="icon-btn" href="<?= url($__u ? 'hesabim.php' : 'giris.php') ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span><?= $__u ? e(explode(' ', $__u['name'])[0]) : 'Giriş Yap' ?></span>
            </a>
            <a class="icon-btn cart-btn" href="<?= url('sepet.php') ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M6 7h12l1 14H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span>Sepetim</span><?php if ($__count): ?><em><?= $__count ?></em><?php endif; ?>
            </a>
            <button class="menu-toggle" data-menu aria-label="Menü"><span></span><span></span><span></span></button>
        </nav>
    </div>
    <nav class="catnav" data-catnav>
        <div class="container catnav-row">
            <a href="<?= url('urunler.php') ?>">Tüm Ürünler</a>
            <?php foreach ($__cats as $__c): ?><a href="<?= url('urunler.php?kategori=' . $__c['slug']) ?>" class="<?= input('kategori') === $__c['slug'] ? 'active' : '' ?>"><?= e($__c['name']) ?></a><?php endforeach; ?>
            <a href="<?= url('urunler.php?indirim=1') ?>" class="hot">İndirimler</a>
        </div>
    </nav>
</header>
<main>
<?php if ($__f = flashes()): ?><div class="container flash"><?= $__f ?></div><?php endif; ?>
