<?php
/** Yönetim paneli ortak üst/alt bölüm */
require_once dirname(__DIR__) . '/app/bootstrap.php';

function admin_menu(): array
{
    return [
        ['index.php', 'Gösterge Paneli', 'panel', 'M3 13h8V3H3zm0 8h8v-6H3zm10 0h8V11h-8zm0-18v6h8V3z'],
        ['siparisler.php', 'Siparişler', 'orders.view', 'M6 7h12l1 14H5L6 7zM9 7a3 3 0 0 1 6 0'],
        ['raporlar.php', 'Günlük Satış Raporu', 'reports', 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
        ['aktivite.php', 'Aktivite Akışı', 'activity', 'M22 12h-4l-3 9L9 3l-3 9H2'],
        ['urunler.php', 'Ürünler', 'products.text', 'M20 7 12 3 4 7v10l8 4 8-4zM4 7l8 4 8-4M12 11v10'],
        ['kategoriler.php', 'Kategoriler', 'categories', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
        ['musteriler.php', 'Müşteriler', 'customers.view', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8'],
        ['talepler.php', 'Talepler', 'requests.view', 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
        ['sayfalar.php', 'Sayfalar & Sözleşmeler', 'pages', 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h8'],
        ['kullanicilar.php', 'Kullanıcılar & Roller', 'users', 'M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM3 21a9 9 0 0 1 18 0'],
        ['ayarlar.php', 'Mağaza Ayarları', 'settings', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z'],
    ];
}

function admin_header(string $title, string $subtitle = '', string $actions = ''): void
{
    $u = current_user();
    $current = basename($_SERVER['SCRIPT_NAME']);
    $aliases = ['siparis.php' => 'siparisler.php', 'urun-duzenle.php' => 'urunler.php', 'sayfa-duzenle.php' => 'sayfalar.php', 'kullanici-duzenle.php' => 'kullanicilar.php'];
    $current = $aliases[$current] ?? $current;
    $newOrders = can('orders.view') ? (int) val("SELECT COUNT(*) FROM orders WHERE status = 'yeni'") : 0;
    $newReq = can('requests.view') ? (int) val("SELECT COUNT(*) FROM requests WHERE status = 'yeni'") : 0;
    ?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Yönetim Paneli</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<aside class="side" data-side>
    <a class="side-logo" href="<?= url('admin/') ?>"><img src="<?= asset('img/logo-light.svg') ?>" alt="GS Sportif Ürünler" width="170"></a>
    <div class="side-user">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
        <div><strong><?= e($u['name']) ?></strong><span class="role role-<?= e($u['role']) ?>"><?= e(role_label($u['role'])) ?></span></div>
    </div>
    <nav class="side-nav">
        <?php foreach (admin_menu() as [$file, $label, $perm, $icon]): if (!can($perm)) continue; ?>
            <a href="<?= url('admin/' . $file) ?>" class="<?= $current === $file ? 'active' : '' ?><?= in_array($perm, ['reports', 'activity', 'settings'], true) ? ' only-super' : '' ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></svg>
                <span><?= $label ?></span>
                <?php if ($file === 'siparisler.php' && $newOrders): ?><em><?= $newOrders ?></em><?php endif; ?>
                <?php if ($file === 'talepler.php' && $newReq): ?><em><?= $newReq ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="side-foot">
        <a href="<?= url('admin/profil.php') ?>">Profilim</a>
        <a href="<?= url() ?>" target="_blank">Siteyi Gör ↗</a>
        <a href="<?= url('admin/cikis.php') ?>">Çıkış</a>
    </div>
</aside>
<div class="main">
    <header class="top">
        <button class="side-toggle" data-side-toggle aria-label="Menü">☰</button>
        <div>
            <h1><?= e($title) ?></h1>
            <?php if ($subtitle): ?><p><?= $subtitle ?></p><?php endif; ?>
        </div>
        <div class="top-actions"><?= $actions ?></div>
    </header>
    <div class="content">
        <?= flashes() ?>
<?php
}

function admin_footer(): void
{
    ?>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
<?php
}

/** Panel içi küçük yardımcı: KPI kutusu */
function kpi(string $label, string $value, string $hint = '', string $tone = ''): string
{
    return '<div class="kpi ' . e($tone) . '"><span>' . e($label) . '</span><strong>' . $value . '</strong>' . ($hint ? '<small>' . $hint . '</small>' : '') . '</div>';
}

/**
 * Basit sütun grafik (tek seri). $data: [['label' => 'Pzt', 'value' => 123.4, 'tip' => '...', 'href' => '...'], ...]
 * Sütunun üzerine gelince ayrıntı görünür, tıklayınca o günün siparişleri açılır.
 */
function bar_chart(array $data, string $caption): string
{
    $max = max(1, max(array_column($data, 'value') ?: [0]));
    $step = pow(10, floor(log10($max)));
    $top = ceil($max / $step) * $step;
    $html = '<figure class="chart" aria-label="' . e($caption) . '"><div class="chart-grid">';
    foreach ([1, .5, 0] as $g) {
        $html .= '<span style="bottom:' . ($g * 100) . '%"><i>' . number_format($top * $g, 0, ',', '.') . ' ₺</i></span>';
    }
    $html .= '</div><div class="chart-bars">';
    foreach ($data as $d) {
        $h = $top > 0 ? max($d['value'] > 0 ? 1.5 : 0, $d['value'] / $top * 100) : 0;
        $html .= '<a class="bar" href="' . e($d['href'] ?? '#') . '" data-tip="' . e($d['tip'] ?? '') . '"><b style="height:' . round($h, 2) . '%"></b><small>' . e($d['label']) . '</small></a>';
    }
    return $html . '</div><figcaption>' . e($caption) . '</figcaption></figure>';
}
