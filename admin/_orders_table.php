<?php /** Sipariş tablosu parçası. $list değişkeni beklenir. */ ?>
<?php if (!$list): ?>
    <p class="muted">Kayıt bulunamadı.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Müşteri</th><th>Hizmet / Paket</th><th class="num">Tutar</th><th>Durum</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $o): ?>
        <tr class="row-link" data-href="order.php?id=<?= (int) $o['id'] ?>">
            <td><strong><?= e($o['order_no']) ?></strong></td>
            <td><?= tr_date($o['created_at']) ?></td>
            <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['customer_phone']) ?></small></td>
            <td><?= e($o['service_title']) ?><br><small class="muted"><?= e($o['package_name']) ?></small></td>
            <td class="num"><strong><?= money($o['amount']) ?></strong></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><a class="btn btn-xs btn-outline" href="order.php?id=<?= (int) $o['id'] ?>">Detay</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
