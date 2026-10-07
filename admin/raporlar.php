<?php
/**
 * SÜPER ADMİN'E ÖZEL: Gün gün satış ve sipariş raporu.
 * - Tarih aralığındaki her gün için sipariş sayısı, satılan ürün adedi, ciro, iptal/iade
 * - Bir güne tıklayınca o günün tüm siparişleri tek tek, ürünleriyle birlikte listelenir
 * - Excel (CSV) olarak indirilebilir
 */
require __DIR__ . '/_layout.php';
$u = require_perm('reports');

$isDate = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d);
$day = $isDate(input('gun')) ? input('gun') : null;
$from = $isDate(input('bas')) ? input('bas') : date('Y-m-d', strtotime('-29 days'));
$to = $isDate(input('bit')) ? input('bit') : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$sale = "'" . implode("','", SALE_STATUSES) . "'";

/* ---------- Tek gün detayı ---------- */
if ($day) {
    $orders = rows('SELECT o.*, u.name rep FROM orders o LEFT JOIN users u ON u.id = o.assigned_to WHERE date(o.created_at) = ? ORDER BY o.created_at', [$day]);
    $items = [];
    foreach (rows('SELECT oi.* FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE date(o.created_at) = ?', [$day]) as $i) $items[$i['order_id']][] = $i;

    if (input('indir')) {
        log_activity('Gün raporunu indirdi', tr_date($day, false));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="siparisler-' . $day . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Saat', 'Sipariş No', 'Müşteri', 'E-posta', 'Telefon', 'İl', 'Ürünler', 'Adet', 'Ara Toplam', 'Kargo', 'Toplam', 'Durum', 'Sorumlu'], ';');
        foreach ($orders as $o) {
            $its = $items[$o['id']] ?? [];
            fputcsv($out, [date('H:i', strtotime($o['created_at'])), $o['order_no'], $o['customer_name'], $o['email'], $o['phone'], $o['city'],
                implode(' | ', array_map(fn($i) => $i['qty'] . 'x ' . $i['name'] . ($i['size'] ? ' (' . $i['size'] . ')' : ''), $its)),
                array_sum(array_column($its, 'qty')), number_format($o['subtotal'], 2, ',', ''), number_format($o['shipping'], 2, ',', ''),
                number_format($o['total'], 2, ',', ''), ORDER_STATUSES[$o['status']][0], $o['rep'] ?? ''], ';');
        }
        exit;
    }

    $valid = array_filter($orders, fn($o) => in_array($o['status'], SALE_STATUSES, true));
    $revenue = array_sum(array_column($valid, 'total'));
    $qty = 0;
    foreach ($valid as $o) $qty += array_sum(array_column($items[$o['id']] ?? [], 'qty'));
    $prev = date('Y-m-d', strtotime("$day -1 day"));
    $next = date('Y-m-d', strtotime("$day +1 day"));
    admin_header(tr_date($day, false) . ' ' . tr_day($day), 'Günün siparişleri tek tek',
        '<a class="btn btn-ghost" href="?gun=' . $prev . '">← Önceki gün</a>' . ($next <= date('Y-m-d') ? '<a class="btn btn-ghost" href="?gun=' . $next . '">Sonraki gün →</a>' : '') .
        '<a class="btn btn-yellow" href="?gun=' . $day . '&indir=1">Excel (CSV) indir</a><a class="btn btn-dark" href="' . url('admin/raporlar.php') . '">Tüm günler</a>');
    ?>
    <div class="kpis">
        <?= kpi('Günün Cirosu', money($revenue), 'İptal ve iadeler hariç', 'red') ?>
        <?= kpi('Sipariş', (string) count($valid), count($orders) - count($valid) . ' iptal/iade', 'yellow') ?>
        <?= kpi('Satılan Ürün', (string) $qty . ' adet', '', 'dark') ?>
        <?= kpi('Ortalama Sepet', money(count($valid) ? $revenue / count($valid) : 0)) ?>
    </div>
    <section class="panel">
        <?php if (!$orders): ?><p class="muted">Bu tarihte sipariş yok.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Saat</th><th>Sipariş No</th><th>Müşteri</th><th>Ürünler</th><th>Toplam</th><th>Durum</th><th>Sorumlu</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr data-href="<?= url('admin/siparis.php?id=' . $o['id']) ?>" class="<?= in_array($o['status'], SALE_STATUSES, true) ? '' : 'row-muted' ?>">
                    <td><?= date('H:i', strtotime($o['created_at'])) ?></td>
                    <td><strong><?= e($o['order_no']) ?></strong></td>
                    <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['email']) ?> · <?= e($o['city']) ?></small></td>
                    <td class="small"><?php foreach ($items[$o['id']] ?? [] as $i): ?><?= $i['qty'] ?>× <?= e($i['name']) ?><?= $i['size'] ? ' <span class="muted">(' . e($i['size']) . ')</span>' : '' ?> — <?= money($i['price'] * $i['qty']) ?><br><?php endforeach; ?></td>
                    <td><strong><?= money($o['total']) ?></strong><?= $o['shipping'] > 0 ? '<br><small class="muted">+' . money($o['shipping']) . ' kargo dâhil</small>' : '' ?></td>
                    <td><?= order_badge($o['status']) ?></td>
                    <td><?= e($o['rep'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <?php
    admin_footer();
    exit;
}

/* ---------- Gün gün özet ---------- */
$raw = [];
foreach (rows("SELECT date(created_at) d,
        SUM(CASE WHEN status IN ($sale) THEN 1 ELSE 0 END) n,
        SUM(CASE WHEN status IN ($sale) THEN total ELSE 0 END) s,
        SUM(CASE WHEN status IN ('iptal','iade') THEN 1 ELSE 0 END) c
    FROM orders WHERE date(created_at) BETWEEN ? AND ? GROUP BY d", [$from, $to]) as $r) $raw[$r['d']] = $r;
$qtyRaw = [];
foreach (rows("SELECT date(o.created_at) d, SUM(oi.qty) q FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ($sale) AND date(o.created_at) BETWEEN ? AND ? GROUP BY d", [$from, $to]) as $r) $qtyRaw[$r['d']] = (int) $r['q'];

$daysList = [];
for ($t = strtotime($to); $t >= strtotime($from); $t -= 86400) {
    $d = date('Y-m-d', $t);
    $daysList[] = ['d' => $d, 'n' => (int) ($raw[$d]['n'] ?? 0), 's' => (float) ($raw[$d]['s'] ?? 0), 'c' => (int) ($raw[$d]['c'] ?? 0), 'q' => $qtyRaw[$d] ?? 0];
}
$sumN = array_sum(array_column($daysList, 'n'));
$sumS = array_sum(array_column($daysList, 's'));
$sumQ = array_sum(array_column($daysList, 'q'));
$sumC = array_sum(array_column($daysList, 'c'));

if (input('indir')) {
    log_activity('Satış raporunu indirdi', tr_date($from, false) . ' - ' . tr_date($to, false));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="satis-raporu-' . $from . '-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tarih', 'Gün', 'Sipariş', 'Satılan Adet', 'Ciro (TL)', 'İptal/İade'], ';');
    foreach ($daysList as $r) fputcsv($out, [date('d.m.Y', strtotime($r['d'])), tr_day($r['d']), $r['n'], $r['q'], number_format($r['s'], 2, ',', ''), $r['c']], ';');
    fputcsv($out, ['TOPLAM', '', $sumN, $sumQ, number_format($sumS, 2, ',', ''), $sumC], ';');
    exit;
}

$chartDays = array_slice(array_reverse($daysList), -31);
$chart = array_map(fn($r) => ['label' => date('d', strtotime($r['d'])), 'value' => $r['s'], 'tip' => tr_date($r['d'], false) . ' ' . tr_day($r['d']) . ' · ' . $r['n'] . ' sipariş · ' . money($r['s']), 'href' => '?gun=' . $r['d']], $chartDays);
$top = rows("SELECT oi.name, SUM(oi.qty) q, SUM(oi.qty * oi.price) s FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ($sale) AND date(o.created_at) BETWEEN ? AND ? GROUP BY oi.name ORDER BY s DESC LIMIT 8", [$from, $to]);
$reps = rows("SELECT u.name, COUNT(*) n, SUM(o.total) s FROM orders o JOIN users u ON u.id = o.assigned_to WHERE o.status IN ($sale) AND date(o.created_at) BETWEEN ? AND ? GROUP BY u.id ORDER BY s DESC", [$from, $to]);

admin_header('Günlük Satış Raporu', 'Yalnızca Süper Admin görebilir · ' . tr_date($from, false) . ' – ' . tr_date($to, false),
    '<a class="btn btn-yellow" href="?bas=' . $from . '&bit=' . $to . '&indir=1">Excel (CSV) indir</a>');
?>
<form class="filter-bar">
    <label>Başlangıç <input type="date" name="bas" value="<?= e($from) ?>"></label>
    <label>Bitiş <input type="date" name="bit" value="<?= e($to) ?>" max="<?= date('Y-m-d') ?>"></label>
    <button class="btn btn-dark">Uygula</button>
    <span class="quick">
        <a href="?bas=<?= date('Y-m-d') ?>&bit=<?= date('Y-m-d') ?>">Bugün</a>
        <a href="?bas=<?= date('Y-m-d', strtotime('-6 days')) ?>&bit=<?= date('Y-m-d') ?>">Son 7 gün</a>
        <a href="?bas=<?= date('Y-m-01') ?>&bit=<?= date('Y-m-d') ?>">Bu ay</a>
        <a href="?bas=<?= date('Y-m-d', strtotime('-29 days')) ?>&bit=<?= date('Y-m-d') ?>">Son 30 gün</a>
    </span>
</form>
<div class="kpis">
    <?= kpi('Toplam Ciro', money($sumS), 'İptal ve iadeler hariç', 'red') ?>
    <?= kpi('Sipariş', (string) $sumN, $sumC . ' iptal/iade', 'yellow') ?>
    <?= kpi('Satılan Ürün', $sumQ . ' adet', '', 'dark') ?>
    <?= kpi('Ortalama Sepet', money($sumN ? $sumS / $sumN : 0)) ?>
</div>
<section class="panel">
    <h2>Günlük Ciro</h2>
    <?= bar_chart($chart, 'Günlük ciro (son ' . count($chart) . ' gün). Sütunun üzerine gelince ayrıntı, tıklayınca o günün siparişleri.') ?>
</section>
<div class="grid-2-1">
    <section class="panel">
        <h2>Gün Gün Satışlar</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Tarih</th><th>Sipariş</th><th>Satılan Adet</th><th>Ciro</th><th>İptal/İade</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daysList as $r): ?>
                <tr data-href="?gun=<?= $r['d'] ?>" class="<?= $r['n'] ? '' : 'row-muted' ?>">
                    <td><strong><?= tr_date($r['d'], false) ?></strong> <span class="muted small"><?= tr_day($r['d']) ?></span></td>
                    <td><?= $r['n'] ?></td><td><?= $r['q'] ?></td><td><strong><?= money($r['s']) ?></strong></td><td><?= $r['c'] ?: '—' ?></td>
                    <td class="small nowrap"><a href="?gun=<?= $r['d'] ?>">Siparişler →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th>Toplam</th><th><?= $sumN ?></th><th><?= $sumQ ?></th><th><?= money($sumS) ?></th><th><?= $sumC ?></th><th></th></tr></tfoot>
        </table></div>
    </section>
    <div>
        <section class="panel">
            <h2>En Çok Satanlar</h2>
            <table class="table"><tbody>
                <?php foreach ($top as $t): ?><tr><td><?= e($t['name']) ?><br><small class="muted"><?= (int) $t['q'] ?> adet</small></td><td style="text-align:right"><strong><?= money($t['s']) ?></strong></td></tr><?php endforeach; ?>
                <?php if (!$top): ?><tr><td class="muted">Veri yok</td></tr><?php endif; ?>
            </tbody></table>
        </section>
        <section class="panel">
            <h2>Temsilci Performansı</h2>
            <table class="table"><tbody>
                <?php foreach ($reps as $r): ?><tr><td><?= e($r['name']) ?><br><small class="muted"><?= (int) $r['n'] ?> sipariş</small></td><td style="text-align:right"><strong><?= money($r['s']) ?></strong></td></tr><?php endforeach; ?>
                <?php if (!$reps): ?><tr><td class="muted">Veri yok</td></tr><?php endif; ?>
            </tbody></table>
        </section>
    </div>
</div>
<?php admin_footer();
