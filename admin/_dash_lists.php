<?php /* Admin ve satış temsilcisi gösterge panelindeki ortak listeler */
if (!function_exists('admin_header')) { http_response_code(404); exit; } ?>
<div class="grid-2-1">
    <section class="panel">
        <div class="panel-head"><h2>Bekleyen Siparişler</h2><a href="<?= url('admin/siparisler.php?durum=yeni') ?>">Tümü →</a></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tarih</th><th>Durum</th></tr></thead><tbody>
            <?php foreach (rows("SELECT * FROM orders WHERE status IN ('yeni','hazirlaniyor') ORDER BY id DESC LIMIT 10") as $o): ?>
                <tr data-href="<?= url('admin/siparis.php?id=' . $o['id']) ?>"><td><strong><?= e($o['order_no']) ?></strong></td><td><?= e($o['customer_name']) ?></td><td><?= tr_date($o['created_at']) ?></td><td><?= order_badge($o['status']) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Yeni Talepler</h2><a href="<?= url('admin/talepler.php') ?>">Tümü →</a></div>
        <ul class="feed compact">
            <?php foreach (rows("SELECT * FROM requests WHERE status IN ('yeni','inceleniyor') ORDER BY id DESC LIMIT 8") as $r): ?>
                <li><span class="dot"></span><div><a href="<?= url('admin/talepler.php?no=' . $r['ticket_no']) ?>"><strong><?= e(REQUEST_TYPES[$r['type']][0]) ?></strong> · <?= e($r['name']) ?></a> <span class="muted">· <?= e($r['subject']) ?></span><small><?= tr_date($r['created_at']) ?></small></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
