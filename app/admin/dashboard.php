<?php
require_once APP_ROOT . '/app/views/admin_layout.php';

function admin_login(): void
{
    require_once APP_ROOT . '/app/pages/account.php';
    if (is_staff()) redirect('admin');
    $errors = handle_login(true);
    ?>
<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Yönetim Girişi · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login-page">
  <div class="login-art" aria-hidden="true"><span></span><span></span></div>
  <form method="post" class="login-box">
    <div class="login-logo"><?= logo_svg('logo') ?></div>
    <h1>Yönetim Paneli</h1>
    <p class="muted">Süper Admin, Admin, Editör ve Satış Temsilcisi girişi</p>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <?= csrf_field() ?>
    <label>E-posta<input type="email" name="email" required autofocus value="<?= old('email') ?>" autocomplete="username"></label>
    <label>Şifre<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block">Giriş yap</button>
    <a class="back" href="<?= url() ?>">← Siteye dön</a>
  </form>
</body></html>
<?php
}

function kpi(string $label, string $value, string $sub = '', string $tone = '', string $href = ''): string
{
    $tag = $href ? 'a href="' . e(url($href)) . '"' : 'div';
    return '<' . $tag . ' class="kpi ' . $tone . '"><span class="kpi-label">' . e($label) . '</span><strong class="kpi-value">' . $value . '</strong>'
        . ($sub ? '<span class="kpi-sub">' . $sub . '</span>' : '') . '</' . ($href ? 'a' : 'div') . '>';
}

/** Son N günün günlük ciro/sipariş serisi */
function daily_series(int $days): array
{
    $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $data = [];
    for ($i = 0; $i < $days; $i++) {
        $d = date('Y-m-d', strtotime($from . " +$i days"));
        $data[$d] = ['revenue' => 0, 'orders' => 0];
    }
    foreach (rows("SELECT date(created_at) d, COUNT(*) c, SUM(CASE WHEN status IN (" . revenue_in() . ") THEN amount ELSE 0 END) r
                   FROM orders WHERE date(created_at) >= ? GROUP BY date(created_at)", [$from]) as $r) {
        if (isset($data[$r['d']])) $data[$r['d']] = ['revenue' => (int)$r['r'], 'orders' => (int)$r['c']];
    }
    return $data;
}

function revenue_chart(array $series, bool $linkDays = true): string
{
    $max = max(1, max(array_column($series, 'revenue')));
    $h = '<div class="bars" role="img" aria-label="Günlük ciro grafiği">';
    foreach ($series as $d => $v) {
        $pct = $v['revenue'] ? max(2, round($v['revenue'] / $max * 100, 1)) : 0;
        $tip = date('d.m', strtotime($d)) . ' ' . tr_day_name($d) . ' · ' . money_short($v['revenue']) . ' · ' . $v['orders'] . ' sipariş';
        $h .= '<a class="bar' . ($d === date('Y-m-d') ? ' today' : '') . '" ' . ($linkDays ? 'href="' . url('admin/raporlar/gun/' . $d) . '"' : '') . ' data-tip="' . e($tip) . '">'
            . '<span class="bar-fill" style="height:' . $pct . '%"></span><span class="bar-x">' . date('d', strtotime($d)) . '</span></a>';
    }
    return $h . '</div>';
}

function orders_table(array $orders, bool $showDate = true): void
{
    if (!$orders) { echo '<p class="empty-line">Kayıt yok.</p>'; return; } ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Sipariş</th><th>Müşteri</th><th>Hizmet / Paket</th><th><?= $showDate ? 'Tarih' : 'Saat' ?></th><th class="r">Tutar</th><th>Durum</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr class="clickable" data-href="<?= url('admin/siparis/' . $o['id']) ?>">
      <td><a href="<?= url('admin/siparis/' . $o['id']) ?>"><strong><?= e($o['order_no']) ?></strong></a></td>
      <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
      <td><?= e($o['service_title']) ?><br><small class="muted"><?= e($o['package_name']) ?></small></td>
      <td><?= $showDate ? tr_date($o['created_at']) : date('H:i', strtotime($o['created_at'])) ?></td>
      <td class="r"><strong><?= money((int)$o['amount']) ?></strong></td>
      <td><?= status_badge($o['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php
}

function activity_item(array $a): string
{
    [$label, $kind] = action_meta($a['action']);
    return '<li class="act act-' . e($kind) . '" data-id="' . (int)$a['id'] . '"><span class="act-dot"></span><div class="act-body">'
        . '<div><strong>' . e($a['user_name']) . '</strong> ' . ($a['role'] !== 'guest' ? role_badge($a['role']) . ' ' : '')
        . '<span class="act-label">' . e($label) . '</span></div>'
        . ($a['details'] ? '<div class="act-details">' . e($a['details']) . '</div>' : '')
        . '<div class="act-meta">' . date('d.m.Y H:i:s', strtotime($a['created_at'])) . ' · IP ' . e($a['ip']) . '</div></div></li>';
}

function admin_dashboard(): void
{
    $u = require_perm('dashboard');
    $today = date('Y-m-d');
    $rev = revenue_in();

    admin_render('Kontrol Paneli', function () use ($u, $today, $rev) {
        $hour = (int)date('H');
        $greet = $hour < 12 ? 'Günaydın' : ($hour < 18 ? 'İyi günler' : 'İyi akşamlar');
        echo '<div class="welcome"><div><h2>' . $greet . ', ' . e(explode(' ', $u['name'])[0]) . ' 👋</h2><p>' . date('d') . ' ' . tr_month_name((int)date('m')) . ' ' . date('Y') . ', ' . tr_day_name($today) . ' · ' . role_badge($u['role']) . ' olarak giriş yaptınız.</p></div></div>';

        if ($u['role'] === 'super_admin') {
            $t = row("SELECT COUNT(*) c, SUM(CASE WHEN status IN ($rev) THEN 1 ELSE 0 END) pc, COALESCE(SUM(CASE WHEN status IN ($rev) THEN amount END),0) r FROM orders WHERE date(created_at) = ?", [$today]);
            $y = row("SELECT COALESCE(SUM(CASE WHEN status IN ($rev) THEN amount END),0) r FROM orders WHERE date(created_at) = ?", [date('Y-m-d', strtotime('-1 day'))]);
            $m = row("SELECT COUNT(*) c, COALESCE(SUM(amount),0) r FROM orders WHERE status IN ($rev) AND strftime('%Y-%m', created_at) = ?", [date('Y-m')]);
            $diff = $y['r'] ? round(($t['r'] - $y['r']) / $y['r'] * 100) : null;
            $avg = $m['c'] ? (int)round($m['r'] / $m['c']) : 0;
            echo '<div class="kpis">'
                . kpi('Bugünkü ciro', money((int)$t['r']), $diff === null ? 'Dün: ' . money_short((int)$y['r']) : ($diff >= 0 ? '<b class="up">▲ %' . $diff . '</b>' : '<b class="down">▼ %' . abs($diff) . '</b>') . ' düne göre', 'kpi-yellow', 'admin/raporlar/gun/' . $today)
                . kpi('Bugünkü siparişler', (int)$t['c'] . '', (int)$t['pc'] . ' tanesi ödendi', '', 'admin/raporlar/gun/' . $today)
                . kpi(tr_month_name((int)date('m')) . ' cirosu', money_short((int)$m['r']), (int)$m['c'] . ' satış · ort. ' . money_short($avg), 'kpi-dark', 'admin/raporlar')
                . kpi('Bekleyen işler', (string)(int)val("SELECT COUNT(*) FROM orders WHERE status='paid'"), 'Başlatılmayı bekleyen ödenmiş sipariş', 'kpi-red', 'admin/siparisler?durum=paid')
                . '</div>';

            $series = daily_series(30);
            $todayOrders = rows('SELECT * FROM orders WHERE date(created_at) = ? ORDER BY id DESC', [$today]);
            $acts = rows('SELECT * FROM activity_log ORDER BY id DESC LIMIT 12');
            $top = rows("SELECT service_title, package_name, COUNT(*) c, SUM(amount) r FROM orders WHERE status IN ($rev) AND strftime('%Y-%m', created_at) = ? GROUP BY package_id ORDER BY r DESC LIMIT 5", [date('Y-m')]);
            ?>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h3>Son 30 gün — günlük ciro</h3><a class="link" href="<?= url('admin/raporlar') ?>">Tüm raporlar →</a></div>
    <?= revenue_chart($series) ?>
    <p class="small muted">Çubuğa tıklayarak o günün tüm siparişlerini görebilirsiniz. Ciro; ödenmiş, başlamış ve tamamlanmış siparişleri içerir.</p>
  </section>
  <section class="card">
    <div class="card-head"><h3><span class="live-dot"></span> Canlı aktivite</h3><a class="link" href="<?= url('admin/aktivite') ?>">Tümü →</a></div>
    <ul class="activity compact" id="liveFeed" data-feed="<?= url('admin/aktivite/canli') ?>" data-last="<?= (int)($acts[0]['id'] ?? 0) ?>">
      <?php foreach ($acts as $a) echo activity_item($a); ?>
    </ul>
  </section>
</div>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h3>Bugünün siparişleri (<?= count($todayOrders) ?>)</h3><a class="link" href="<?= url('admin/raporlar/gun/' . $today) ?>">Gün detayı →</a></div>
    <?php orders_table($todayOrders, false); ?>
  </section>
  <section class="card">
    <div class="card-head"><h3>Bu ayın en çok satanları</h3></div>
    <?php if (!$top): ?><p class="empty-line">Bu ay henüz satış yok.</p><?php else: ?>
    <ol class="toplist"><?php foreach ($top as $i => $tp): ?>
      <li><span class="rank"><?= $i + 1 ?></span><div><strong><?= e($tp['service_title']) ?></strong><small><?= e($tp['package_name']) ?> · <?= (int)$tp['c'] ?> satış</small></div><b><?= money_short((int)$tp['r']) ?></b></li>
    <?php endforeach; ?></ol>
    <?php endif; ?>
  </section>
</div>
<?php
            return;
        }

        // Diğer roller: kendi işlerine odaklı özet
        $cards = '';
        if (can('orders.view')) {
            $cards .= kpi('Başlatılmayı bekleyen', (string)(int)val("SELECT COUNT(*) FROM orders WHERE status='paid'"), 'Ödenmiş siparişler', 'kpi-yellow', 'admin/siparisler?durum=paid');
            $cards .= kpi('Devam eden hizmetler', (string)(int)val("SELECT COUNT(*) FROM orders WHERE status='processing'"), 'Hizmeti başlamış', '', 'admin/siparisler?durum=processing');
        }
        if (can('messages.view')) $cards .= kpi('Yeni mesajlar', (string)(int)val("SELECT COUNT(*) FROM messages WHERE status='new'"), 'İletişim, İK, KVKK', 'kpi-dark', 'admin/mesajlar');
        if (can('tickets.manage')) $cards .= kpi('Açık destek kayıtları', (string)(int)val("SELECT COUNT(*) FROM tickets WHERE status='open'"), 'Yanıt bekleyen', 'kpi-red', 'admin/destek');
        if (can('catalog.edit')) {
            $cards .= kpi('Danışmanlıklar', (string)(int)val('SELECT COUNT(*) FROM services WHERE is_active=1'), 'Yayında', 'kpi-yellow', 'admin/hizmetler');
            $cards .= kpi('Paketler', (string)(int)val('SELECT COUNT(*) FROM packages WHERE is_active=1'), 'Yayında', '', 'admin/paketler');
        }
        if (can('pages.edit') && !can('orders.view')) $cards .= kpi('Sayfalar', (string)(int)val('SELECT COUNT(*) FROM pages'), 'Kurumsal & yasal', 'kpi-dark', 'admin/sayfalar');
        echo '<div class="kpis">' . $cards . '</div>';

        echo '<div class="grid-main">';
        if (can('orders.view')) {
            echo '<section class="card"><div class="card-head"><h3>İşlem bekleyen siparişler</h3><a class="link" href="' . url('admin/siparisler') . '">Tüm siparişler →</a></div>';
            orders_table(rows("SELECT * FROM orders WHERE status IN ('paid','processing') ORDER BY CASE status WHEN 'paid' THEN 0 ELSE 1 END, id DESC LIMIT 10"));
            echo '</section>';
        } else {
            echo '<section class="card"><div class="card-head"><h3>Son düzenlenen sayfalar</h3><a class="link" href="' . url('admin/sayfalar') . '">Tümü →</a></div><ul class="simple-list">';
            foreach (rows('SELECT * FROM pages ORDER BY updated_at DESC LIMIT 8') as $pg) {
                echo '<li><a href="' . url('admin/sayfa/' . $pg['slug']) . '">' . e($pg['title']) . '</a><small>' . tr_date($pg['updated_at']) . ' · ' . e($pg['updated_by']) . '</small></li>';
            }
            echo '</ul></section>';
        }
        echo '<section class="card"><div class="card-head"><h3>Rolünüz: ' . e(ROLES[$u['role']]) . '</h3></div><p class="small muted">Bu rolle yapabilecekleriniz:</p><ul class="perm-list">';
        foreach (PERMISSION_LABELS as $perm => $label) {
            if (can($perm)) echo '<li>✓ ' . e($label) . '</li>';
        }
        echo '</ul><a class="link" href="' . url('admin/yetkiler') . '">Tüm rollerin yetki tablosu →</a></section></div>';
    });
}
