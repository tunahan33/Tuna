<?php
require __DIR__ . '/_layout.php';
$u = require_perm('panel');
$today = date('Y-m-d');
$sale = "'" . implode("','", SALE_STATUSES) . "'";

admin_header('Gösterge Paneli', 'Hoş geldiniz, ' . e(explode(' ', $u['name'])[0]) . ' · ' . tr_date($today, false) . ', ' . tr_day($today));

/* ---------------- SÜPER ADMİN ---------------- */
if ($u['role'] === 'super_admin'):
    $t = row("SELECT COUNT(*) n, COALESCE(SUM(total),0) s FROM orders WHERE date(created_at) = ? AND status IN ($sale)", [$today]);
    $y = row("SELECT COUNT(*) n, COALESCE(SUM(total),0) s FROM orders WHERE date(created_at) = ? AND status IN ($sale)", [date('Y-m-d', strtotime('-1 day'))]);
    $m = row("SELECT COUNT(*) n, COALESCE(SUM(total),0) s FROM orders WHERE strftime('%Y-%m', created_at) = ? AND status IN ($sale)", [date('Y-m')]);
    $pending = (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('yeni','hazirlaniyor')");
    $diff = $y['s'] > 0 ? round(($t['s'] - $y['s']) / $y['s'] * 100) : null;
    $days = [];
    $raw = [];
    foreach (rows("SELECT date(created_at) d, COUNT(*) n, SUM(total) s FROM orders WHERE created_at >= ? AND status IN ($sale) GROUP BY d", [date('Y-m-d', strtotime('-13 days'))]) as $r) $raw[$r['d']] = $r;
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $days[] = ['label' => date('d', strtotime($d)) . ' ' . mb_substr(TR_MONTHS[(int) date('n', strtotime($d))], 0, 3), 'value' => (float) ($raw[$d]['s'] ?? 0),
            'tip' => tr_date($d, false) . ' · ' . (int) ($raw[$d]['n'] ?? 0) . ' sipariş · ' . money($raw[$d]['s'] ?? 0), 'href' => url('admin/raporlar.php?gun=' . $d)];
    }
    $todayOrders = rows('SELECT o.*, u.name rep FROM orders o LEFT JOIN users u ON u.id = o.assigned_to WHERE date(o.created_at) = ? ORDER BY o.created_at DESC', [$today]);
    $feed = rows('SELECT * FROM activity ORDER BY id DESC LIMIT 12');
    ?>
    <div class="kpis">
        <?= kpi('Bugünkü Ciro', money($t['s']), $diff === null ? 'Dün satış yok' : ($diff >= 0 ? '▲ %' . $diff : '▼ %' . abs($diff)) . ' düne göre', 'red') ?>
        <?= kpi('Bugünkü Sipariş', (string) $t['n'], 'Dün: ' . $y['n'] . ' sipariş', 'yellow') ?>
        <?= kpi('Bu Ay Ciro', money($m['s']), $m['n'] . ' sipariş', 'dark') ?>
        <?= kpi('Bekleyen Sipariş', (string) $pending, 'Yeni + hazırlanıyor') ?>
    </div>
    <div class="grid-2-1">
        <section class="panel">
            <div class="panel-head"><h2>Son 14 Gün Satış</h2><a href="<?= url('admin/raporlar.php') ?>">Detaylı rapor →</a></div>
            <?= bar_chart($days, 'Günlük ciro (iptal ve iadeler hariç). Bir güne tıklayarak o günün siparişlerini görün.') ?>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Canlı Aktivite</h2><a href="<?= url('admin/aktivite.php') ?>">Tümü →</a></div>
            <ul class="feed compact">
                <?php foreach ($feed as $a): ?>
                    <li><span class="dot role-<?= e($a['role']) ?>"></span><div><strong><?= e($a['user_name']) ?></strong> <?= e(mb_strtolower($a['action'])) ?><?php if ($a['details']): ?> <span class="muted">· <?= e(mb_strimwidth($a['details'], 0, 60, '…')) ?></span><?php endif; ?><small><?= tr_date($a['created_at']) ?></small></div></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Bugünün Siparişleri (<?= count($todayOrders) ?>)</h2><a href="<?= url('admin/raporlar.php?gun=' . $today) ?>">Gün raporu →</a></div>
        <?php if (!$todayOrders): ?><p class="muted">Bugün henüz sipariş yok.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Saat</th><th>Sipariş No</th><th>Müşteri</th><th>Ürünler</th><th>Tutar</th><th>Durum</th><th>Sorumlu</th></tr></thead>
            <tbody><?php foreach ($todayOrders as $o): $items = rows('SELECT name, qty FROM order_items WHERE order_id = ?', [$o['id']]); ?>
                <tr data-href="<?= url('admin/siparis.php?id=' . $o['id']) ?>">
                    <td><?= date('H:i', strtotime($o['created_at'])) ?></td><td><strong><?= e($o['order_no']) ?></strong></td><td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['city']) ?></small></td>
                    <td class="small"><?= e(implode(', ', array_map(fn($i) => $i['qty'] . '× ' . $i['name'], $items))) ?></td>
                    <td><strong><?= money($o['total']) ?></strong></td><td><?= order_badge($o['status']) ?></td><td><?= e($o['rep'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </section>
<?php
/* ---------------- ADMİN ---------------- */
elseif ($u['role'] === 'admin'):
    $c = fn($sql) => (int) val($sql);
    ?>
    <div class="kpis">
        <?= kpi('Yeni Sipariş', (string) $c("SELECT COUNT(*) FROM orders WHERE status='yeni'"), 'Onay bekliyor', 'red') ?>
        <?= kpi('Hazırlanıyor', (string) $c("SELECT COUNT(*) FROM orders WHERE status='hazirlaniyor'"), 'Kargoya verilecek', 'yellow') ?>
        <?= kpi('Yeni Talep', (string) $c("SELECT COUNT(*) FROM requests WHERE status='yeni'"), 'Mesaj, arıza, KVKK, İK', 'dark') ?>
        <?= kpi('Kritik Stok', (string) $c('SELECT COUNT(*) FROM products WHERE active=1 AND stock <= 5'), '5 adet ve altı') ?>
    </div>
    <?php require __DIR__ . '/_dash_lists.php'; ?>
<?php
/* ---------------- SATIŞ TEMSİLCİSİ ---------------- */
elseif ($u['role'] === 'satis'):
    $mine = rows("SELECT * FROM orders WHERE assigned_to = ? AND status IN ('hazirlaniyor','kargoda') ORDER BY id DESC LIMIT 20", [$u['id']]);
    ?>
    <div class="kpis">
        <?= kpi('Sahipsiz Yeni Sipariş', (string) val("SELECT COUNT(*) FROM orders WHERE status='yeni'"), 'İlk ilgilenen üstlenir', 'red') ?>
        <?= kpi('Bende Açık', (string) count($mine), 'Hazırlanıyor + kargoda', 'yellow') ?>
        <?= kpi('Bu Ay Teslim Ettiğim', (string) val("SELECT COUNT(*) FROM orders WHERE assigned_to = ? AND status='teslim' AND strftime('%Y-%m', updated_at) = ?", [$u['id'], date('Y-m')]), '', 'dark') ?>
        <?= kpi('Yeni Talep', (string) val("SELECT COUNT(*) FROM requests WHERE status='yeni'"), 'Müşteri talepleri') ?>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Bana Atanan Açık Siparişler</h2><a href="<?= url('admin/siparisler.php?benim=1') ?>">Tümü →</a></div>
        <?php if (!$mine): ?><p class="muted">Üzerinizde açık sipariş yok. <a href="<?= url('admin/siparisler.php?durum=yeni') ?>">Yeni siparişlere göz atın.</a></p><?php else: ?>
        <div class="table-wrap"><table class="table"><thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tarih</th><th>Durum</th></tr></thead><tbody>
            <?php foreach ($mine as $o): ?><tr data-href="<?= url('admin/siparis.php?id=' . $o['id']) ?>"><td><strong><?= e($o['order_no']) ?></strong></td><td><?= e($o['customer_name']) ?></td><td><?= tr_date($o['created_at']) ?></td><td><?= order_badge($o['status']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <?php require __DIR__ . '/_dash_lists.php'; ?>
<?php
/* ---------------- EDİTÖR ---------------- */
else: ?>
    <div class="kpis">
        <?= kpi('Aktif Ürün', (string) val('SELECT COUNT(*) FROM products WHERE active=1'), '', 'red') ?>
        <?= kpi('Kategori', (string) val('SELECT COUNT(*) FROM categories'), '', 'yellow') ?>
        <?= kpi('Sayfa & Sözleşme', (string) val('SELECT COUNT(*) FROM pages'), '', 'dark') ?>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Son Güncellenen Ürünler</h2><a href="<?= url('admin/urunler.php') ?>">Tümü →</a></div>
        <div class="table-wrap"><table class="table"><tbody>
            <?php foreach (rows('SELECT * FROM products ORDER BY updated_at DESC LIMIT 8') as $p): ?><tr data-href="<?= url('admin/urun-duzenle.php?id=' . $p['id']) ?>"><td><?= e($p['name']) ?></td><td class="muted"><?= tr_date($p['updated_at']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php endif;
admin_footer();
