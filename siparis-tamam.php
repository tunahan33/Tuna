<?php
require __DIR__ . '/app/bootstrap.php';

$order = isset($_SESSION['last_order']) ? row('SELECT * FROM orders WHERE order_no = ?', [$_SESSION['last_order']]) : null;
if (!$order) {
    redirect('');
}
$items = rows('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
$title = 'Siparişiniz alındı';
require __DIR__ . '/app/header.php';
?>
<section class="section">
    <div class="container narrow">
        <div class="card center" style="padding:40px">
            <div style="font-size:3rem">🎉</div>
            <h1>Teşekkürler, siparişiniz alındı!</h1>
            <p class="muted">Sipariş numaranız</p>
            <p style="font-size:1.6rem;font-weight:800;color:var(--red);margin:0"><?= e($order['order_no']) ?></p>
            <p class="muted">Sipariş özetiniz <strong><?= e($order['email']) ?></strong> adresine gönderilecektir. Siparişiniz <?= e(setting('shipping_days')) ?> içinde kargoya verilir.</p>
            <table class="data-table" style="margin:20px 0;text-align:left">
                <?php foreach ($items as $i): ?><tr><td><?= e($i['name']) ?><?= $i['size'] ? ' · ' . e($i['size']) : '' ?></td><td><?= $i['qty'] ?> adet</td><td style="text-align:right"><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?>
                <tr><td colspan="2">Kargo</td><td style="text-align:right"><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Ücretsiz' ?></td></tr>
                <tr><th colspan="2">Toplam</th><th style="text-align:right"><?= money($order['total']) ?></th></tr>
            </table>
            <a class="btn btn-primary" href="<?= url('siparis-takibi.php?no=' . urlencode($order['order_no']) . '&email=' . urlencode($order['email'])) ?>">Siparişimi Takip Et</a>
            <a class="btn btn-dark" href="<?= url('urunler.php') ?>">Alışverişe Devam Et</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
