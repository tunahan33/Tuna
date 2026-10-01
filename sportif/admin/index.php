<?php
require __DIR__ . '/_init.php';
$u = require_perm('panel.access');

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$in = "'" . implode("','", SALE_STATUSES) . "'";

admin_header('Kontrol Paneli', 'Hoş geldiniz, <strong>' . e($u['name']) . '</strong> · ' . e(role_label($u['role'])));

if (can('dashboard.stats')):
    $todayOrders = (int) val('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$today . ' 00:00:00']);
    $todaySales = (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status IN ($in) AND paid_at >= ?", [$today . ' 00:00:00']);
    $todaySaleCount = (int) val("SELECT COUNT(*) FROM orders WHERE status IN ($in) AND paid_at >= ?", [$today . ' 00:00:00']);
    $monthSales = (float) val("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status IN ($in) AND paid_at >= ?", [$monthStart . ' 00:00:00']);
    $waiting = (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('paid','preparing')");
    $members = (int) val("SELECT COUNT(*) FROM users WHERE role = 'member'");
    ?>
    <div class="stat-grid">
        <div class="stat"><span>Bugünkü Siparişler</span><strong><?= $todayOrders ?></strong><small><?= $todaySaleCount ?> tanesi ödendi</small></div>
        <div class="stat accent-yellow"><span>Bugünkü Satış</span><strong><?= money($todaySales) ?></strong><small><?= tr_day_name($today) ?></small></div>
        <div class="stat accent-red"><span>Bu Ay Satış</span><strong><?= money($monthSales) ?></strong><small><?= date('m.Y') ?></small></div>
        <a class="stat" href="orders.php?service=kargo_bekleyen"><span>Kargo Bekleyen</span><strong><?= $waiting ?></strong><small>Hazırlanacak siparişler →</small></a>
        <?php if ($u['role'] !== 'sales'): ?><div class="stat"><span>Toplam Üye</span><strong><?= $members ?></strong><small>Kayıtlı müşteri</small></div><?php endif; ?>
    </div>
<?php endif; ?>

<?php if (can('reports.view')):
    // Son 14 gün satış grafiği (yalnızca süper admin)
    $days = [];
    for ($i = 13; $i >= 0; $i--) {
        $days[date('Y-m-d', strtotime("-$i day"))] = 0.0;
    }
    foreach (rows("SELECT DATE(paid_at) AS d, SUM(amount) AS t FROM orders WHERE status IN ($in) AND paid_at >= ? GROUP BY DATE(paid_at)", [array_key_first($days) . ' 00:00:00']) as $r) {
        if (isset($days[$r['d']])) $days[$r['d']] = (float) $r['t'];
    }
    $max = max($days) ?: 1;
    $activity = rows('SELECT * FROM activity_log ORDER BY id DESC LIMIT 12');
    ?>
    <div class="grid-main">
        <section class="panel">
            <div class="panel-head"><h2>Son 14 Gün Satış (₺)</h2><a href="reports.php" class="btn btn-xs btn-outline">Gün gün rapor →</a></div>
            <div class="bars" role="img" aria-label="Son 14 günün günlük satış tutarları">
                <?php foreach ($days as $d => $t): $h = $t > 0 ? max(3, round($t / $max * 100)) : 0; ?>
                    <a class="bar-col" href="reports.php?day=<?= $d ?>" data-tip="<?= e(tr_day_name($d) . ' ' . date('d.m', strtotime($d)) . ': ' . money($t)) ?>">
                        <span class="bar" style="height:<?= $h ?>%"></span>
                        <span class="bar-label"><?= date('d', strtotime($d)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="small muted">En yüksek gün: <?= money($max === 1 && !array_sum($days) ? 0 : $max) ?> · 14 gün toplamı: <strong><?= money(array_sum($days)) ?></strong>. Bir güne tıklayarak o günün siparişlerini görebilirsiniz.</p>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Canlı Aktivite</h2><a href="activity.php" class="btn btn-xs btn-outline">Tümü →</a></div>
            <ul class="feed compact">
                <?php foreach ($activity as $a): ?>
                    <li><span class="dot role-<?= e($a['user_role']) ?>"></span><div><strong><?= e($a['user_name']) ?></strong> <?= e(mb_strtolower($a['action'])) ?><br><small><?= e(mb_strimwidth((string) $a['details'], 0, 70, '…')) ?> · <?= tr_date($a['created_at']) ?></small></div></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
<?php endif; ?>

<?php if (can('orders.view')):
    if ($u['role'] === 'sales') {
        $list = rows("SELECT * FROM orders WHERE status IN ('paid','preparing') AND (assigned_to = ? OR assigned_to IS NULL) ORDER BY id ASC LIMIT 15", [$u['id']]);
        $listTitle = 'Kargoya verilmeyi bekleyen siparişler (size atanan / sahipsiz)';
    } else {
        $list = rows('SELECT * FROM orders ORDER BY id DESC LIMIT 10');
        $listTitle = 'Son Siparişler';
    }
    ?>
    <section class="panel">
        <div class="panel-head"><h2><?= e($listTitle) ?></h2><a href="orders.php" class="btn btn-xs btn-outline">Tüm siparişler →</a></div>
        <?php include __DIR__ . '/_orders_table.php'; ?>
    </section>
<?php endif; ?>

<?php if (can('content.edit')):
    $lim = (int) setting('low_stock_limit', '3');
    $low = rows('SELECT v.*, p.name, p.id AS pid FROM product_variants v JOIN products p ON p.id = v.product_id WHERE p.is_active = 1 AND v.stock <= ? ORDER BY v.stock ASC LIMIT 8', [$lim]);
    $team = can('team.view') ? rows("SELECT * FROM team_requests WHERE status IN ('new','contacted','quoted') ORDER BY id DESC LIMIT 5") : []; ?>
    <div class="grid-main">
        <section class="panel">
            <div class="panel-head"><h2>⚠ Azalan / Tükenen Stok</h2><a href="stock.php" class="btn btn-xs btn-outline">Stok takibi →</a></div>
            <?php if (!$low): ?><p class="muted">Tüm ürünlerde yeterli stok var. 👍</p><?php else: ?>
            <table class="table compact"><tbody><?php foreach ($low as $v): ?><tr class="<?= $v['stock'] <= 0 ? 'row-out' : 'row-low' ?>"><td><a href="product_edit.php?id=<?= (int) $v['pid'] ?>#stok"><?= e($v['name']) ?></a></td><td><?= e($v['size']) ?> · <?= e($v['color']) ?></td><td class="num"><strong><?= (int) $v['stock'] ?></strong></td></tr><?php endforeach; ?></tbody></table>
            <?php endif; ?>
        </section>
        <?php if ($team): ?>
        <section class="panel">
            <div class="panel-head"><h2>Açık Takım Talepleri</h2><a href="team.php" class="btn btn-xs btn-outline">Tümü →</a></div>
            <ul class="feed compact"><?php foreach ($team as $t): ?><li><span class="dot role-sales"></span><div><a href="team.php?open=<?= (int) $t['id'] ?>"><strong><?= e($t['club_name']) ?></strong></a> · <?= (int) $t['quantity'] ?> adet<br><small><?= e($t['contact_name']) ?> · <?= tr_date($t['created_at']) ?></small></div></li><?php endforeach; ?></ul>
        </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="panel">
    <div class="panel-head"><h2>Yetkileriniz</h2></div>
    <div class="perm-chips">
        <?php
        $labels = ['orders.view' => 'Siparişleri görme', 'orders.status' => 'Sipariş durumu güncelleme', 'orders.cancel' => 'İptal / iade', 'orders.delete' => 'Sipariş silme', 'customers.view' => 'Müşteriler', 'messages.view' => 'Mesajlar', 'orders.ship' => 'Kargoya verme', 'team.view' => 'Takım talepleri', 'content.edit' => 'Ürün metin ve görselleri', 'content.create' => 'Ürün/kategori ekleme', 'stock.edit' => 'Stok yönetimi', 'coupons.manage' => 'Kuponlar', 'prices.edit' => 'Fiyat değiştirme', 'pages.edit' => 'Sayfa & sözleşmeler', 'users.manage' => 'Kullanıcı yönetimi', 'reports.view' => 'Günlük satış raporları', 'activity.view' => 'Aktivite akışı', 'settings.edit' => 'Site & POS ayarları'];
        foreach ($labels as $perm => $l): ?>
            <span class="perm <?= can($perm) ? 'yes' : 'no' ?>"><?= can($perm) ? '✓' : '✕' ?> <?= e($l) ?></span>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer();
