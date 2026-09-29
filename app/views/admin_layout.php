<?php
require_once APP_ROOT . '/app/views/layout.php'; // icon(), payment_logos()

function admin_icon(string $name): string
{
    $p = [
        'home'     => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'cart'     => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/>',
        'pulse'    => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'box'      => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'inbox'    => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
        'life'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.9 4.9 4.3 4.3M14.8 14.8l4.3 4.3M14.8 9.2l4.3-4.3M4.9 19.1l4.3-4.3"/>',
        'users'    => '<circle cx="9" cy="8" r="4"/><path d="M1 21a8 8 0 0 1 16 0M17 4a4 4 0 0 1 0 8M23 21a8 8 0 0 0-5-7.4"/>',
        'contacts' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M7 18a5 5 0 0 1 10 0"/>',
        'key'      => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'globe'    => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>',
        'crown'    => '<path d="m2 18 2-12 5 5 3-7 3 7 5-5 2 12z"/>',
    ][$name] ?? '<circle cx="12" cy="12" r="9"/>';
    return '<svg class="ai" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function role_badge(string $role): string
{
    return '<span class="role role-' . e($role) . '">' . e(ROLES[$role] ?? $role) . '</span>';
}

function admin_header(string $title, array $opts = []): void
{
    $u = current_user();
    $path = trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path())), '/');
    $counts = [
        'orders'   => can('orders.view') ? (int)val("SELECT COUNT(*) FROM orders WHERE status = 'paid'") : 0,
        'messages' => can('messages.view') ? (int)val("SELECT COUNT(*) FROM messages WHERE status = 'new'") : 0,
        'tickets'  => can('tickets.manage') ? (int)val("SELECT COUNT(*) FROM tickets WHERE status = 'open'") : 0,
    ];
    // [yol, etiket, ikon, yetki, rozet, alt sayfa öneki]
    $menu = [
        'Genel' => [
            ['admin', 'Kontrol Paneli', 'home', 'dashboard', 0, null],
        ],
        'Satış' => [
            ['admin/siparisler', 'Siparişler', 'cart', 'orders.view', $counts['orders'], 'admin/siparis/'],
            ['admin/musteriler', 'Müşteriler', 'contacts', 'customers.view', 0, null],
        ],
        'Süper Admin' => [
            ['admin/raporlar', 'Günlük Satış & Siparişler', 'chart', 'reports.sales', 0, 'admin/raporlar/'],
            ['admin/aktivite', 'Aktivite Akışı', 'pulse', 'activity.view', 0, null],
        ],
        'İçerik' => [
            ['admin/hizmetler', 'Danışmanlıklar', 'grid', 'catalog.edit', 0, 'admin/hizmet/'],
            ['admin/paketler', 'Paketler & Fiyatlar', 'box', 'catalog.edit', 0, 'admin/paket/'],
            ['admin/sayfalar', 'Sayfalar & Sözleşmeler', 'file', 'pages.edit', 0, 'admin/sayfa/'],
        ],
        'Destek' => [
            ['admin/mesajlar', 'Mesajlar & Başvurular', 'inbox', 'messages.view', $counts['messages'], 'admin/mesaj/'],
            ['admin/destek', 'Arıza / Destek Kayıtları', 'life', 'tickets.manage', $counts['tickets'], 'admin/destek/'],
        ],
        'Yönetim' => [
            ['admin/kullanicilar', 'Kullanıcılar & Roller', 'users', 'users.manage', 0, 'admin/kullanici/'],
            ['admin/yetkiler', 'Yetki Tablosu', 'key', 'dashboard', 0, null],
            ['admin/ayarlar', 'Site & Sanal POS Ayarları', 'cog', 'settings.manage', 0, null],
        ],
    ];
    ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Yönetim · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<aside class="sidebar" id="sidebar">
  <a class="sb-brand" href="<?= url('admin') ?>"><?= logo_svg('logo') ?></a>
  <nav class="sb-nav">
    <?php foreach ($menu as $group => $items):
        $visible = array_filter($items, fn($i) => can($i[3]));
        if (!$visible) continue; ?>
      <div class="sb-group<?= $group === 'Süper Admin' ? ' sb-super' : '' ?>"><?= $group === 'Süper Admin' ? admin_icon('crown') . ' ' : '' ?><?= e($group) ?></div>
      <?php foreach ($visible as $i):
          $on = $path === $i[0] || ($i[5] && str_starts_with($path, $i[5])); ?>
        <a href="<?= url($i[0]) ?>" class="<?= $on ? 'on' : '' ?>"><?= admin_icon($i[2]) ?><span><?= e($i[1]) ?></span><?php if (!empty($i[4])): ?><em><?= (int)$i[4] ?></em><?php endif; ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot">
    <a href="<?= url() ?>" target="_blank"><?= admin_icon('globe') ?><span>Siteyi görüntüle</span></a>
  </div>
</aside>
<div class="sb-overlay" id="sbOverlay"></div>
<div class="main">
  <header class="topbar">
    <button class="burger" id="burger" aria-label="Menü"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <div class="tb-title">
      <h1><?= e($title) ?></h1>
      <?php if (!empty($opts['subtitle'])): ?><p><?= e($opts['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="tb-user">
      <?= role_badge($u['role']) ?>
      <div class="tb-menu">
        <button class="avatar" aria-label="Hesap menüsü"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></button>
        <div class="tb-drop">
          <div class="tb-drop-head"><strong><?= e($u['name']) ?></strong><span><?= e($u['email']) ?></span></div>
          <a href="<?= url('admin/profil') ?>"><?= admin_icon('user') ?> Profilim</a>
          <a href="<?= url('cikis') ?>"><?= admin_icon('out') ?> Çıkış yap</a>
        </div>
      </div>
    </div>
  </header>
  <div class="content">
  <?php foreach (flashes() as [$type, $msg]): ?>
    <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach;
}

function admin_footer(): void
{
    ?>
  </div>
</div>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
<?php
}

/** Basit sayfalama */
function paginate(int $total, int $per = 25): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page = min($pages, max(1, (int)($_GET['sayfa'] ?? 1)));
    return [$page, $pages, ($page - 1) * $per, $per];
}

function pager(int $page, int $pages): string
{
    if ($pages <= 1) return '';
    $qs = $_GET;
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 10 && abs($i - $page) > 2 && $i !== 1 && $i !== $pages) { if (abs($i - $page) === 3) $h .= '<span>…</span>'; continue; }
        $qs['sayfa'] = $i;
        $h .= '<a href="?' . e(http_build_query($qs)) . '" class="' . ($i === $page ? 'on' : '') . '">' . $i . '</a>';
    }
    return $h . '</nav>';
}

function action_meta(string $action): array
{
    return ACTIONS[$action] ?? [$action, 'content'];
}
