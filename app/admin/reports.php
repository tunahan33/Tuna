<?php
require_once __DIR__ . '/dashboard.php';

function report_range(): array
{
    $to = $_GET['bitis'] ?? date('Y-m-d');
    $from = $_GET['baslangic'] ?? date('Y-m-d', strtotime('-29 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-29 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
    if ($from > $to) [$from, $to] = [$to, $from];
    if ((strtotime($to) - strtotime($from)) / 86400 > 366) $from = date('Y-m-d', strtotime($to . ' -366 days'));
    return [$from, $to];
}

function report_days(string $from, string $to): array
{
    $rev = revenue_in();
    $days = [];
    for ($d = $to; $d >= $from; $d = date('Y-m-d', strtotime($d . ' -1 day'))) {
        $days[$d] = ['total' => 0, 'paid' => 0, 'failed' => 0, 'pending' => 0, 'cancel' => 0, 'revenue' => 0];
    }
    $sql = "SELECT date(created_at) d, COUNT(*) total,
              SUM(CASE WHEN status IN ($rev) THEN 1 ELSE 0 END) paid,
              SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) failed,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) pending,
              SUM(CASE WHEN status IN ('cancelled','refunded') THEN 1 ELSE 0 END) cancel,
              COALESCE(SUM(CASE WHEN status IN ($rev) THEN amount END), 0) revenue
            FROM orders WHERE date(created_at) BETWEEN ? AND ? GROUP BY date(created_at)";
    foreach (rows($sql, [$from, $to]) as $r) {
        $days[$r['d']] = array_map('intval', array_diff_key($r, ['d' => 1]));
    }
    return $days;
}

function admin_reports(): void
{
    require_perm('reports.sales');
    [$from, $to] = report_range();
    $days = report_days($from, $to);
    $sum = ['total' => 0, 'paid' => 0, 'revenue' => 0, 'failed' => 0, 'cancel' => 0];
    foreach ($days as $d) foreach ($sum as $k => $_) $sum[$k] += $d[$k];
    $chart = [];
    foreach (array_reverse($days, true) as $d => $v) $chart[$d] = ['revenue' => $v['revenue'], 'orders' => $v['total']];
    $rev = revenue_in();
    $byService = rows("SELECT service_title, COUNT(*) c, SUM(amount) r FROM orders WHERE status IN ($rev) AND date(created_at) BETWEEN ? AND ? GROUP BY service_title ORDER BY r DESC", [$from, $to]);

    admin_render('Günlük Satış & Siparişler', function () use ($from, $to, $days, $sum, $chart, $byService) {
        $presets = ['Bugün' => [date('Y-m-d'), date('Y-m-d')], 'Son 7 gün' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
                    'Son 30 gün' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')], 'Bu ay' => [date('Y-m-01'), date('Y-m-d')],
                    'Geçen ay' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))]]; ?>
<section class="card">
  <form class="toolbar" method="get">
    <div class="chips inline"><?php foreach ($presets as $label => [$f, $t]): ?>
      <a class="chip <?= $f === $from && $t === $to ? 'on' : '' ?>" href="?baslangic=<?= $f ?>&amp;bitis=<?= $t ?>"><?= $label ?></a>
    <?php endforeach; ?></div>
    <label class="inline-label">Başlangıç <input type="date" name="baslangic" value="<?= e($from) ?>"></label>
    <label class="inline-label">Bitiş <input type="date" name="bitis" value="<?= e($to) ?>"></label>
    <button class="btn btn-dark">Listele</button>
    <a class="btn btn-ghost" href="<?= url('admin/raporlar/csv') ?>?baslangic=<?= e($from) ?>&amp;bitis=<?= e($to) ?>">⬇ Excel (CSV)</a>
  </form>
  <label class="inline-label">Tek bir güne git:
    <input type="date" value="<?= date('Y-m-d') ?>" onchange="if(this.value)location.href='<?= url('admin/raporlar/gun/') ?>'+this.value">
  </label>
</section>
<div class="kpis">
  <?= kpi('Toplam ciro', money((int)$sum['revenue']), tr_date($from, false) . ' – ' . tr_date($to, false), 'kpi-yellow') ?>
  <?= kpi('Başarılı satış', (string)$sum['paid'], 'Ortalama sepet ' . ($sum['paid'] ? money_short((int)round($sum['revenue'] / $sum['paid'])) : '—')) ?>
  <?= kpi('Toplam sipariş', (string)$sum['total'], 'Dönüşüm %' . ($sum['total'] ? round($sum['paid'] / $sum['total'] * 100) : 0), 'kpi-dark') ?>
  <?= kpi('Başarısız / iptal', $sum['failed'] . ' / ' . $sum['cancel'], 'Ödeme hatası / iptal-iade', 'kpi-red') ?>
</div>
<?php if (count($chart) > 1 && count($chart) <= 62): ?>
<section class="card"><div class="card-head"><h3>Günlük ciro</h3></div><?= revenue_chart($chart) ?></section>
<?php endif; ?>
<div class="grid-main">
<section class="card">
  <div class="card-head"><h3>Gün gün satışlar</h3><span class="small muted">Satıra tıklayarak o günün siparişlerini tek tek görün</span></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Tarih</th><th class="r">Sipariş</th><th class="r">Ödenen</th><th class="r">Bekleyen</th><th class="r">Başarısız</th><th class="r">İptal/İade</th><th class="r">Ciro</th></tr></thead>
    <tbody>
    <?php foreach ($days as $d => $v): ?>
      <tr class="clickable<?= $v['total'] ? '' : ' dim' ?>" data-href="<?= url('admin/raporlar/gun/' . $d) ?>">
        <td><a href="<?= url('admin/raporlar/gun/' . $d) ?>"><strong><?= date('d.m.Y', strtotime($d)) ?></strong></a> <small class="muted"><?= tr_day_name($d) ?></small></td>
        <td class="r"><?= $v['total'] ?></td><td class="r"><?= $v['paid'] ?></td><td class="r"><?= $v['pending'] ?></td><td class="r"><?= $v['failed'] ?></td><td class="r"><?= $v['cancel'] ?></td>
        <td class="r"><strong><?= money((int)$v['revenue']) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Toplam</td><td class="r"><?= $sum['total'] ?></td><td class="r"><?= $sum['paid'] ?></td><td></td><td class="r"><?= $sum['failed'] ?></td><td class="r"><?= $sum['cancel'] ?></td><td class="r"><?= money((int)$sum['revenue']) ?></td></tr></tfoot>
  </table></div>
</section>
<section class="card">
  <div class="card-head"><h3>Danışmanlık bazında</h3></div>
  <?php if (!$byService): ?><p class="empty-line">Bu aralıkta satış yok.</p><?php else:
      $max = max(array_column($byService, 'r')); ?>
    <ul class="hbars"><?php foreach ($byService as $s): ?>
      <li><div class="hb-top"><span><?= e($s['service_title']) ?></span><b><?= money_short((int)$s['r']) ?></b></div>
      <div class="hb-track"><span style="width:<?= round($s['r'] / $max * 100) ?>%"></span></div><small class="muted"><?= (int)$s['c'] ?> satış</small></li>
    <?php endforeach; ?></ul>
  <?php endif; ?>
</section>
</div>
<?php
    }, ['subtitle' => 'Yalnızca Süper Admin görebilir']);
}

function admin_report_day(string $date): void
{
    require_perm('reports.sales');
    if (!strtotime($date)) redirect('admin/raporlar');
    $orders = rows('SELECT * FROM orders WHERE date(created_at) = ? ORDER BY created_at DESC', [$date]);
    $acts = rows('SELECT * FROM activity_log WHERE date(created_at) = ? ORDER BY id DESC LIMIT 200', [$date]);
    $rev = 0; $paid = 0;
    foreach ($orders as $o) if (in_array($o['status'], REVENUE_STATUSES, true)) { $rev += (int)$o['amount']; $paid++; }
    $prev = date('Y-m-d', strtotime($date . ' -1 day'));
    $next = date('Y-m-d', strtotime($date . ' +1 day'));

    admin_render(date('d.m.Y', strtotime($date)) . ' ' . tr_day_name($date), function () use ($date, $orders, $acts, $rev, $paid, $prev, $next) { ?>
<div class="page-actions">
  <a class="btn btn-ghost" href="<?= url('admin/raporlar') ?>">← Raporlar</a>
  <a class="btn btn-ghost" href="<?= url('admin/raporlar/gun/' . $prev) ?>">‹ Önceki gün</a>
  <?php if ($next <= date('Y-m-d')): ?><a class="btn btn-ghost" href="<?= url('admin/raporlar/gun/' . $next) ?>">Sonraki gün ›</a><?php endif; ?>
  <input type="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>" onchange="if(this.value)location.href='<?= url('admin/raporlar/gun/') ?>'+this.value">
  <a class="btn btn-dark" href="<?= url('admin/raporlar/csv') ?>?baslangic=<?= e($date) ?>&amp;bitis=<?= e($date) ?>">⬇ CSV</a>
</div>
<div class="kpis">
  <?= kpi('Günün cirosu', money($rev), '', 'kpi-yellow') ?>
  <?= kpi('Sipariş', (string)count($orders), $paid . ' tanesi ödendi') ?>
  <?= kpi('Aktivite', (string)count($acts), 'kayıtlı işlem', 'kpi-dark') ?>
</div>
<div class="grid-main">
  <section class="card"><div class="card-head"><h3>Günün siparişleri — tek tek</h3></div><?php orders_table($orders, false); ?></section>
  <section class="card"><div class="card-head"><h3>Günün aktivitesi — kim ne yaptı</h3><a class="link" href="<?= url('admin/aktivite') ?>?tarih=<?= e($date) ?>">Filtrele →</a></div>
    <ul class="activity compact scroll"><?php foreach ($acts as $a) echo activity_item($a); ?><?php if (!$acts): ?><li class="empty-line">Kayıt yok.</li><?php endif; ?></ul>
  </section>
</div>
<?php
    }, ['subtitle' => 'Günlük sipariş ve aktivite dökümü']);
}

function admin_report_csv(): void
{
    require_perm('reports.sales');
    [$from, $to] = report_range();
    $orders = rows('SELECT * FROM orders WHERE date(created_at) BETWEEN ? AND ? ORDER BY created_at', [$from, $to]);
    log_activity('export', "Sipariş raporu $from – $to (" . count($orders) . ' kayıt)');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="gsprojeler-siparisler-' . $from . '_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tarih', 'Sipariş No', 'Müşteri', 'E-posta', 'Telefon', 'Hizmet', 'Paket', 'Tutar (TL)', 'Durum', 'Ödeme Tarihi', 'Fatura Tipi', 'Firma', 'VKN/TCKN', 'Şehir'], ';');
    foreach ($orders as $o) {
        fputcsv($out, [tr_date($o['created_at']), $o['order_no'], $o['customer_name'], $o['email'], $o['phone'], $o['service_title'], $o['package_name'],
            number_format($o['amount'] / 100, 2, ',', ''), ORDER_STATUSES[$o['status']][0] ?? $o['status'], tr_date($o['paid_at']), $o['invoice_type'],
            $o['company_name'], $o['invoice_type'] === 'kurumsal' ? $o['tax_no'] : $o['identity_no'], $o['city']], ';');
    }
    exit;
}

function activity_filters(): array
{
    $where = ['1=1']; $params = [];
    if (!empty($_GET['kullanici'])) { $where[] = 'user_id = ?'; $params[] = (int)$_GET['kullanici']; }
    if (!empty($_GET['rol']) && (isset(ROLES[$_GET['rol']]) || $_GET['rol'] === 'guest')) { $where[] = 'role = ?'; $params[] = $_GET['rol']; }
    if (!empty($_GET['islem']) && isset(ACTIONS[$_GET['islem']])) { $where[] = 'action = ?'; $params[] = $_GET['islem']; }
    if (!empty($_GET['tarih']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tarih'])) { $where[] = 'date(created_at) = ?'; $params[] = $_GET['tarih']; }
    if (!empty($_GET['q'])) { $where[] = '(details LIKE ? OR user_name LIKE ? OR ip LIKE ?)'; $q = '%' . $_GET['q'] . '%'; array_push($params, $q, $q, $q); }
    return [implode(' AND ', $where), $params];
}

function admin_activity(): void
{
    require_perm('activity.view');
    [$w, $params] = activity_filters();
    $total = (int)val("SELECT COUNT(*) FROM activity_log WHERE $w", $params);
    [$page, $pages, $offset, $per] = paginate($total, 50);
    $acts = rows("SELECT * FROM activity_log WHERE $w ORDER BY id DESC LIMIT $per OFFSET $offset", $params);
    $staff = rows("SELECT id, name, role FROM users WHERE role != 'member' ORDER BY name");
    $filtered = $w !== '1=1';
    $byUser = rows("SELECT user_name, role, COUNT(*) c FROM activity_log WHERE date(created_at) = ? AND role != 'guest' AND role != 'member' GROUP BY user_id ORDER BY c DESC", [date('Y-m-d')]);

    admin_render('Aktivite Akışı', function () use ($acts, $staff, $page, $pages, $total, $filtered, $byUser) { ?>
<section class="card">
  <form class="toolbar" method="get">
    <select name="kullanici"><option value="">Tüm personel</option><?php foreach ($staff as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['kullanici'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e(ROLES[$s['role']]) ?>)</option><?php endforeach; ?></select>
    <select name="rol"><option value="">Tüm roller</option><?php foreach (ROLES + ['guest' => 'Ziyaretçi'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['rol'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <select name="islem"><option value="">Tüm işlemler</option><?php foreach (ACTIONS as $k => [$l]): ?><option value="<?= $k ?>" <?= ($_GET['islem'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <input type="date" name="tarih" value="<?= e($_GET['tarih'] ?? '') ?>">
    <input type="search" name="q" placeholder="Ara (sipariş no, isim, IP)" value="<?= e($_GET['q'] ?? '') ?>">
    <button class="btn btn-dark">Filtrele</button>
    <?php if ($filtered): ?><a class="btn btn-ghost" href="?">Temizle</a><?php endif; ?>
  </form>
</section>
<div class="grid-main">
  <section class="card">
    <div class="card-head"><h3><?php if (!$filtered): ?><span class="live-dot"></span> Canlı akış<?php else: ?>Filtrelenmiş kayıtlar<?php endif; ?></h3><span class="small muted"><?= $total ?> kayıt</span></div>
    <ul class="activity" <?= !$filtered && $page === 1 ? 'id="liveFeed" data-feed="' . url('admin/aktivite/canli') . '" data-last="' . (int)($acts[0]['id'] ?? 0) . '"' : '' ?>>
      <?php foreach ($acts as $a) echo activity_item($a); ?>
      <?php if (!$acts): ?><li class="empty-line">Kayıt bulunamadı.</li><?php endif; ?>
    </ul>
    <?= pager($page, $pages) ?>
  </section>
  <section class="card">
    <div class="card-head"><h3>Bugün personel hareketleri</h3></div>
    <?php if (!$byUser): ?><p class="empty-line">Bugün personel işlemi yok.</p><?php else: ?>
    <ul class="simple-list"><?php foreach ($byUser as $b): ?><li><span><?= e($b['user_name']) ?> <?= role_badge($b['role']) ?></span><b><?= (int)$b['c'] ?> işlem</b></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <p class="small muted">Tüm girişler, sipariş ve ödeme olayları, fiyat/içerik değişiklikleri, rol değişiklikleri ve form gönderimleri IP adresiyle birlikte kaydedilir. Kayıtlar silinemez.</p>
  </section>
</div>
<?php
    }, ['subtitle' => 'Kim, ne zaman, ne yaptı — yalnızca Süper Admin']);
}

/** Canlı akış için JSON: son görülen id'den sonraki kayıtlar */
function admin_activity_feed(): void
{
    require_perm('activity.view');
    $after = (int)($_GET['after'] ?? 0);
    $acts = rows('SELECT * FROM activity_log WHERE id > ? ORDER BY id DESC LIMIT 30', [$after]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['last' => (int)($acts[0]['id'] ?? $after), 'html' => implode('', array_map('activity_item', $acts))], JSON_UNESCAPED_UNICODE);
    exit;
}
