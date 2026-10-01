<?php
/** Yönetim paneli şablonu */

function admin_menu(): array
{
    $newMsgs = can('messages.view') ? (int) val("SELECT COUNT(*) FROM messages WHERE status = 'new'") : 0;
    $toShip = can('orders.view') ? (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('paid','preparing')") : 0;
    $newTeam = can('team.view') ? (int) val("SELECT COUNT(*) FROM team_requests WHERE status = 'new'") : 0;
    $lowStock = can('content.edit') ? (int) val('SELECT COUNT(*) FROM product_variants WHERE stock <= ?', [(int) setting('low_stock_limit', '3')]) : 0;
    return [
        ['Genel', [
            ['index.php', 'Kontrol Paneli', 'grid', 'panel.access'],
            ['reports.php', 'Günlük Satış Raporu', 'chart', 'reports.view'],
            ['activity.php', 'Aktivite Akışı', 'pulse', 'activity.view'],
        ]],
        ['Satış', [
            ['orders.php', 'Siparişler', 'bag', 'orders.view', $toShip],
            ['team.php', 'Takım Talepleri', 'users', 'team.view', $newTeam],
            ['customers.php', 'Müşteriler', 'user', 'customers.view'],
            ['coupons.php', 'İndirim Kuponları', 'tag', 'coupons.manage'],
            ['messages.php', 'Mesajlar', 'mail', 'messages.view', $newMsgs],
        ]],
        ['Mağaza', [
            ['products.php', 'Ürünler', 'shirt', 'content.edit'],
            ['stock.php', 'Stok Takibi', 'box', 'content.edit', $lowStock],
            ['categories.php', 'Kategoriler', 'layers', 'content.edit'],
            ['pages.php', 'Sayfalar & Sözleşmeler', 'file', 'pages.edit'],
        ]],
        ['Sistem', [
            ['users.php', 'Kullanıcılar & Yetkiler', 'shield', 'users.view'],
            ['settings.php', 'Site & Ödeme Ayarları', 'cog', 'settings.edit'],
            ['profile.php', 'Profilim', 'user', 'panel.access'],
        ]],
    ];
}

function admin_icon(string $n): string
{
    $i = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15v2M11 11v6M15 7v10M19 12v5"/>',
        'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'bag' => '<path d="M6 7h12l1 14H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 13 9 5 9-5"/>',
        'tag' => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9z"/><circle cx="8" cy="8" r="1.5"/>',
        'file' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'shield' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3z"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'shirt' => '<path d="M8 3 4 6l2 4 2-1v12h8V9l2 1 2-4-4-3a4 4 0 0 1-8 0z"/>',
        'box' => '<path d="m3 7 9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7M12 11v10"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($i[$n] ?? '') . '</svg>';
}

function admin_header(string $title, string $subtitle = ''): void
{
    $u = current_user();
    $cur = basename($_SERVER['SCRIPT_NAME']);
    $aliases = ['order.php' => 'orders.php', 'product_edit.php' => 'products.php', 'page_edit.php' => 'pages.php', 'user_edit.php' => 'users.php', 'message.php' => 'messages.php'];
    $cur = $aliases[$cur] ?? $cur;
    ?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · GS Sportif Yönetim</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<aside class="sidebar" data-sidebar>
    <a class="sb-brand" href="<?= url('admin/') ?>"><img src="<?= asset('img/logo-light.svg') ?>" alt="GS Sportif" height="40"></a>
    <div class="sb-user">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
        <div><strong><?= e($u['name']) ?></strong><span class="role role-<?= e($u['role']) ?>"><?= e(role_label($u['role'])) ?></span></div>
    </div>
    <nav class="sb-nav">
        <?php foreach (admin_menu() as [$group, $items]):
            $items = array_filter($items, fn($i) => can($i[3]));
            if (!$items) continue; ?>
            <div class="sb-group"><?= e($group) ?></div>
            <?php foreach ($items as $it): ?>
                <a href="<?= url('admin/' . $it[0]) ?>" class="<?= $cur === $it[0] ? 'active' : '' ?>">
                    <?= admin_icon($it[2]) ?><span><?= e($it[1]) ?></span>
                    <?php if (!empty($it[4])): ?><em class="count"><?= (int) $it[4] ?></em><?php endif; ?>
                    <?php if (in_array($it[3], ['reports.view', 'activity.view', 'settings.edit'], true)): ?><i class="lock" title="Yalnızca Süper Admin">★</i><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="sb-foot">
        <a href="<?= url() ?>" target="_blank"><?= admin_icon('globe') ?><span>Siteyi Görüntüle</span></a>
        <a href="<?= url('admin/logout.php') ?>"><?= admin_icon('logout') ?><span>Çıkış Yap</span></a>
    </div>
</aside>
<div class="sb-overlay" data-sidebar-close></div>
<div class="admin-main">
    <header class="admin-top">
        <button class="sb-toggle" data-sidebar-toggle aria-label="Menü"><span></span><span></span><span></span></button>
        <div>
            <h1><?= e($title) ?></h1>
            <?php if ($subtitle): ?><p><?= $subtitle ?></p><?php endif; ?>
        </div>
        <div class="top-date"><?= tr_day_name(date('Y-m-d')) ?>, <?= date('d.m.Y') ?></div>
    </header>
    <div class="admin-content">
        <?php if (maintenance_active()): ?>
            <div class="alert alert-error">🔧 <strong>Bakım modu açık</strong> — site ziyaretçilere kapalı<?= setting('maintenance_until') ? ', ' . e(tr_date(setting('maintenance_until'))) . ' itibarıyla otomatik açılacak' : '' ?>. <?= can('settings.edit') ? '<a href="' . url('admin/settings.php?tab=server') . '">Yönet →</a>' : '' ?></div>
        <?php endif; ?>
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

/** Sayfalama yardımcısı */
function paginate(int $total, int $perPage = 25): array
{
    $page = max(1, (int) ($_GET['p'] ?? 1));
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    return [$page, $pages, ($page - 1) * $perPage, $perPage];
}

function pager(int $page, int $pages): string
{
    if ($pages <= 1) return '';
    $qs = $_GET;
    $out = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i > 2 && $i < $pages - 1 && abs($i - $page) > 2) {
            if (!str_ends_with($out, '…')) $out .= '<span>…</span>';
            continue;
        }
        $qs['p'] = $i;
        $out .= '<a class="' . ($i === $page ? 'active' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
