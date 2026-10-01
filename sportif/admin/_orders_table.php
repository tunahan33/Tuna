<?php /** Sipariş tablosu parçası. $list değişkeni beklenir. */ ?>
<?php if (!$list): ?>
    <p class="muted">Kayıt bulunamadı.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Müşteri</th><th>Ürünler</th><th class="num">Tutar</th><th>Durum</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $o): ?>
        <tr class="row-link" data-href="order.php?id=<?= (int) $o['id'] ?>">
            <td><strong><?= e($o['order_no']) ?></strong></td>
            <td><?= tr_date($o['created_at']) ?></td>
            <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['customer_phone']) ?></small></td>
            <td><?php $__it = rows('SELECT product_name, size, qty, print_name, print_number FROM order_items WHERE order_id = ? LIMIT 3', [$o['id']]); ?>
                <?php foreach ($__it as $__i): ?><div class="small"><?= (int) $__i['qty'] ?>× <?= e($__i['product_name']) ?> <span class="muted">(<?= e($__i['size']) ?>)</span><?= ($__i['print_name'] || $__i['print_number']) ? ' <span class="badge badge-yellow">Baskı</span>' : '' ?></div><?php endforeach; ?>
                <?php if ((int) $o['item_count'] > array_sum(array_column($__it, 'qty'))): ?><small class="muted">+ diğer ürünler</small><?php endif; ?>
                <?php if ($o['tracking_no']): ?><small class="muted">📦 <?= e($o['cargo_company']) ?> <?= e($o['tracking_no']) ?></small><?php endif; ?></td>
            <td class="num"><strong><?= money($o['amount']) ?></strong></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><a class="btn btn-xs btn-outline" href="order.php?id=<?= (int) $o['id'] ?>">Detay</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
