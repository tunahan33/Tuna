<?php
/** Günlük satış ve sipariş raporu - YALNIZCA SÜPER ADMIN */
require __DIR__ . '/_init.php';
$u = require_perm('reports.view');

$in = "'" . implode("','", SALE_STATUSES) . "'";
$isDate = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d);

/* ---------- Tek gün detay ---------- */
if ($isDate($day = input('day'))) {
    $orders = rows('SELECT o.*, u.name AS rep FROM orders o LEFT JOIN users u ON u.id = o.assigned_to WHERE o.created_at BETWEEN ? AND ? ORDER BY o.created_at', [$day . ' 00:00:00', $day . ' 23:59:59']);
    $paidToday = rows("SELECT * FROM orders WHERE status IN ($in) AND paid_at BETWEEN ? AND ? ORDER BY paid_at", [$day . ' 00:00:00', $day . ' 23:59:59']);
    $acts = rows('SELECT * FROM activity_log WHERE created_at BETWEEN ? AND ? ORDER BY id DESC', [$day . ' 00:00:00', $day . ' 23:59:59']);
    $rev = array_sum(array_column($paidToday, 'amount'));

    if (input('export') === 'csv') {
        log_activity('Rapor dışa aktardı', 'Günlük sipariş listesi: ' . $day, 'report');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="siparisler-' . $day . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Saat', 'Sipariş No', 'Müşteri', 'E-posta', 'Telefon', 'Ürünler', 'Adet', 'İndirim', 'Kargo', 'Tutar', 'Durum', 'Ödeme Zamanı', 'Kargo Takip', 'Sorumlu'], ';');
        foreach ($orders as $o) {
            $names = implode(', ', array_map(fn($i) => $i['qty'] . 'x ' . $i['product_name'] . ' (' . $i['size'] . ')', rows('SELECT * FROM order_items WHERE order_id = ?', [$o['id']])));
            fputcsv($out, [date('H:i', strtotime($o['created_at'])), $o['order_no'], $o['customer_name'], $o['customer_email'], $o['customer_phone'], $names, $o['item_count'], number_format((float) $o['discount'], 2, ',', ''), number_format((float) $o['shipping_fee'], 2, ',', ''), number_format((float) $o['amount'], 2, ',', ''), ORDER_STATUSES[$o['status']][0] ?? $o['status'], $o['paid_at'], trim($o['cargo_company'] . ' ' . $o['tracking_no']), $o['rep']], ';');
        }
        exit;
    }

    admin_header(tr_day_name($day) . ', ' . date('d.m.Y', strtotime($day)), 'Günün tüm siparişleri, satışları ve yapılan işlemler');
    $prev = date('Y-m-d', strtotime($day . ' -1 day'));
    $next = date('Y-m-d', strtotime($day . ' +1 day'));
    ?>
    <div class="toolbar">
        <a class="btn btn-outline btn-sm" href="?day=<?= $prev ?>">← Önceki gün</a>
        <a class="btn btn-outline btn-sm" href="reports.php">Gün gün listeye dön</a>
        <?php if ($next <= date('Y-m-d')): ?><a class="btn btn-outline btn-sm" href="?day=<?= $next ?>">Sonraki gün →</a><?php endif; ?>
        <form method="get" class="inline-form"><input type="date" name="day" value="<?= e($day) ?>" max="<?= date('Y-m-d') ?>"><button class="btn btn-dark btn-sm">Git</button></form>
        <a class="btn btn-yellow btn-sm push-right" href="?day=<?= $day ?>&export=csv">Excel (CSV) indir</a>
    </div>
    <div class="stat-grid">
        <div class="stat"><span>Oluşan Sipariş</span><strong><?= count($orders) ?></strong></div>
        <div class="stat accent-yellow"><span>Tamamlanan Satış</span><strong><?= count($paidToday) ?></strong></div>
        <div class="stat accent-red"><span>Günün Cirosu</span><strong><?= money($rev) ?></strong></div>
        <div class="stat"><span>Başarısız Ödeme</span><strong><?= count(array_filter($orders, fn($o) => $o['status'] === 'failed')) ?></strong></div>
        <div class="stat"><span>Yapılan İşlem</span><strong><?= count($acts) ?></strong></div>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Günün Siparişleri (tek tek)</h2></div>
        <?php if (!$orders): ?><p class="muted">Bu gün sipariş oluşturulmadı.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Saat</th><th>Sipariş No</th><th>Müşteri</th><th>Ürünler</th><th class="num">Tutar</th><th>Durum</th><th>Sorumlu</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr class="row-link" data-href="order.php?id=<?= (int) $o['id'] ?>">
                    <td><?= date('H:i', strtotime($o['created_at'])) ?></td>
                    <td><strong><?= e($o['order_no']) ?></strong></td>
                    <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['customer_email']) ?></small></td>
                    <td><?php foreach (rows('SELECT product_name, size, qty FROM order_items WHERE order_id = ?', [$o['id']]) as $__i): ?><div class="small"><?= (int) $__i['qty'] ?>× <?= e($__i['product_name']) ?> (<?= e($__i['size']) ?>)</div><?php endforeach; ?></td>
                    <td class="num"><?= money($o['amount']) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td><?= e($o['rep'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Bu gün kim ne yaptı?</h2><a class="btn btn-xs btn-outline" href="activity.php?from=<?= $day ?>&to=<?= $day ?>">Akışta filtrele →</a></div>
        <ul class="feed"><?php foreach ($acts as $a): ?><li><span class="dot role-<?= e($a['user_role']) ?>"></span><div><span class="time"><?= date('H:i:s', strtotime($a['created_at'])) ?></span> <strong><?= e($a['user_name']) ?></strong> <span class="role-tag"><?= e(role_label($a['user_role'])) ?></span> — <?= e($a['action']) ?><br><small><?= e($a['details']) ?></small></div></li><?php endforeach; ?>
        <?php if (!$acts): ?><li class="muted">Kayıt yok.</li><?php endif; ?></ul>
    </section>
    <?php
    admin_footer();
    exit;
}

/* ---------- Gün gün liste ---------- */
$from = $isDate(input('from')) ? input('from') : date('Y-m-01');
$to = $isDate(input('to')) ? input('to') : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
if ((strtotime($to) - strtotime($from)) / 86400 > 366) $from = date('Y-m-d', strtotime($to . ' -366 day'));
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];

$days = [];
for ($d = $to; $d >= $from; $d = date('Y-m-d', strtotime($d . ' -1 day'))) {
    $days[$d] = ['orders' => 0, 'sales' => 0, 'revenue' => 0.0, 'failed' => 0, 'refunded' => 0.0];
}
foreach (rows('SELECT DATE(created_at) AS d, COUNT(*) AS c, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS f FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)', ['failed', ...$range]) as $r) {
    if (isset($days[$r['d']])) { $days[$r['d']]['orders'] = (int) $r['c']; $days[$r['d']]['failed'] = (int) $r['f']; }
}
foreach (rows("SELECT DATE(paid_at) AS d, COUNT(*) AS c, SUM(amount) AS t FROM orders WHERE status IN ($in) AND paid_at BETWEEN ? AND ? GROUP BY DATE(paid_at)", $range) as $r) {
    if (isset($days[$r['d']])) { $days[$r['d']]['sales'] = (int) $r['c']; $days[$r['d']]['revenue'] = (float) $r['t']; }
}
foreach (rows("SELECT DATE(paid_at) AS d, SUM(amount) AS t FROM orders WHERE status = 'refunded' AND paid_at BETWEEN ? AND ? GROUP BY DATE(paid_at)", $range) as $r) {
    if (isset($days[$r['d']])) $days[$r['d']]['refunded'] = (float) $r['t'];
}
$tot = ['orders' => array_sum(array_column($days, 'orders')), 'sales' => array_sum(array_column($days, 'sales')), 'revenue' => array_sum(array_column($days, 'revenue')), 'failed' => array_sum(array_column($days, 'failed'))];
$byService = rows("SELECT COALESCE(c.name, 'Silinmiş ürün') AS service_title, SUM(i.qty) AS c, SUM(i.line_total) AS t FROM order_items i JOIN orders o ON o.id = i.order_id LEFT JOIN products p ON p.id = i.product_id LEFT JOIN categories c ON c.id = p.category_id WHERE o.status IN ($in) AND o.paid_at BETWEEN ? AND ? GROUP BY c.name ORDER BY t DESC", $range);
$byPackage = rows("SELECT i.product_name AS package_name, '' AS service_title, SUM(i.qty) AS c, SUM(i.line_total) AS t FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.status IN ($in) AND o.paid_at BETWEEN ? AND ? GROUP BY i.product_name ORDER BY c DESC LIMIT 10", $range);
$extra = row("SELECT COALESCE(SUM(discount),0) AS disc, COALESCE(SUM(shipping_fee),0) AS ship, COALESCE(SUM(item_count),0) AS items FROM orders WHERE status IN ($in) AND paid_at BETWEEN ? AND ?", $range);
$byRep = rows("SELECT COALESCE(u.name, 'Atanmamış') AS n, COUNT(*) AS c, SUM(o.amount) AS t FROM orders o LEFT JOIN users u ON u.id = o.assigned_to WHERE o.status IN ($in) AND o.paid_at BETWEEN ? AND ? GROUP BY u.name ORDER BY t DESC", $range);

if (input('export') === 'csv') {
    log_activity('Rapor dışa aktardı', "Gün gün satış raporu: $from / $to", 'report');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="gunluk-satis-' . $from . '_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tarih', 'Gün', 'Oluşan Sipariş', 'Satış Adedi', 'Ciro (TL)', 'Başarısız Ödeme'], ';');
    foreach ($days as $d => $x) fputcsv($out, [date('d.m.Y', strtotime($d)), tr_day_name($d), $x['orders'], $x['sales'], number_format($x['revenue'], 2, ',', ''), $x['failed']], ';');
    fputcsv($out, ['TOPLAM', '', $tot['orders'], $tot['sales'], number_format($tot['revenue'], 2, ',', ''), $tot['failed']], ';');
    exit;
}
$maxRev = max(array_column($days, 'revenue') ?: [0]) ?: 1;

admin_header('Günlük Satış Raporu', 'Satışlar ve siparişler gün gün · <span class="lock-tag">★ Yalnızca Süper Admin</span>');
?>
<form class="panel filters" method="get">
    <label class="inline">Başlangıç<input type="date" name="from" value="<?= e($from) ?>"></label>
    <label class="inline">Bitiş<input type="date" name="to" value="<?= e($to) ?>" max="<?= date('Y-m-d') ?>"></label>
    <button class="btn btn-dark btn-sm">Göster</button>
    <span class="quick">
        <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">Bugün</a>
        <a href="?from=<?= date('Y-m-d', strtotime('-6 day')) ?>&to=<?= date('Y-m-d') ?>">Son 7 gün</a>
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">Bu ay</a>
        <a href="?from=<?= date('Y-m-01', strtotime('first day of last month')) ?>&to=<?= date('Y-m-t', strtotime('last day of last month')) ?>">Geçen ay</a>
        <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>">Bu yıl</a>
    </span>
    <a class="btn btn-yellow btn-sm push-right" href="?from=<?= $from ?>&to=<?= $to ?>&export=csv">Excel (CSV) indir</a>
</form>
<div class="stat-grid">
    <div class="stat"><span>Oluşan Sipariş</span><strong><?= $tot['orders'] ?></strong></div>
    <div class="stat accent-yellow"><span>Satış Adedi</span><strong><?= $tot['sales'] ?></strong></div>
    <div class="stat accent-red"><span>Toplam Ciro</span><strong><?= money($tot['revenue']) ?></strong></div>
    <div class="stat"><span>Satılan Ürün</span><strong><?= (int) $extra['items'] ?> adet</strong></div>
    <div class="stat"><span>Kupon İndirimi / Kargo</span><strong><?= money($extra['disc']) ?></strong><small>Kargo geliri: <?= money($extra['ship']) ?></small></div>
    <div class="stat"><span>Ortalama Sepet</span><strong><?= money($tot['sales'] ? $tot['revenue'] / $tot['sales'] : 0) ?></strong></div>
    <div class="stat"><span>Başarısız Ödeme</span><strong><?= $tot['failed'] ?></strong></div>
</div>
<section class="panel">
    <div class="panel-head"><h2>Gün Gün Satışlar</h2><span class="small muted">Satırlara tıklayarak o günün siparişlerini tek tek görün</span></div>
    <div class="table-wrap"><table class="table day-table">
        <thead><tr><th>Tarih</th><th>Gün</th><th class="num">Sipariş</th><th class="num">Satış</th><th class="num">Ciro</th><th class="bar-cell"></th><th class="num">Başarısız</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($days as $d => $x): ?>
            <tr class="row-link <?= $x['orders'] || $x['sales'] ? '' : 'dim' ?>" data-href="reports.php?day=<?= $d ?>">
                <td><strong><?= date('d.m.Y', strtotime($d)) ?></strong></td>
                <td><?= tr_day_name($d) ?></td>
                <td class="num"><?= $x['orders'] ?></td>
                <td class="num"><?= $x['sales'] ?></td>
                <td class="num"><strong><?= money($x['revenue']) ?></strong></td>
                <td class="bar-cell"><span class="hbar" style="width:<?= round($x['revenue'] / $maxRev * 100) ?>%"></span></td>
                <td class="num"><?= $x['failed'] ?: '-' ?></td>
                <td><a class="btn btn-xs btn-outline" href="reports.php?day=<?= $d ?>">Detay</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="2">TOPLAM</th><th class="num"><?= $tot['orders'] ?></th><th class="num"><?= $tot['sales'] ?></th><th class="num"><?= money($tot['revenue']) ?></th><th></th><th class="num"><?= $tot['failed'] ?></th><th></th></tr></tfoot>
    </table></div>
</section>
<div class="grid-3-panels">
    <section class="panel"><h3>Kategoriye Göre <small class="muted">(adet · ciro)</small></h3>
        <table class="table compact"><tbody><?php foreach ($byService as $r): ?><tr><td><?= e($r['service_title']) ?></td><td class="num"><?= (int) $r['c'] ?></td><td class="num"><strong><?= money($r['t']) ?></strong></td></tr><?php endforeach; ?><?php if (!$byService): ?><tr><td class="muted">Satış yok</td></tr><?php endif; ?></tbody></table>
    </section>
    <section class="panel"><h3>En Çok Satan Ürünler <small class="muted">(adet)</small></h3>
        <table class="table compact"><tbody><?php foreach ($byPackage as $r): ?><tr><td><?= e($r['package_name']) ?><br><small class="muted"><?= e($r['service_title']) ?></small></td><td class="num"><?= (int) $r['c'] ?></td><td class="num"><strong><?= money($r['t']) ?></strong></td></tr><?php endforeach; ?><?php if (!$byPackage): ?><tr><td class="muted">Satış yok</td></tr><?php endif; ?></tbody></table>
    </section>
    <section class="panel"><h3>Satış Temsilcisine Göre</h3>
        <table class="table compact"><tbody><?php foreach ($byRep as $r): ?><tr><td><?= e($r['n']) ?></td><td class="num"><?= (int) $r['c'] ?></td><td class="num"><strong><?= money($r['t']) ?></strong></td></tr><?php endforeach; ?><?php if (!$byRep): ?><tr><td class="muted">Satış yok</td></tr><?php endif; ?></tbody></table>
    </section>
</div>
<?php admin_footer();
