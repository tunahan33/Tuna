<?php
require __DIR__ . '/app/bootstrap.php';

$no = (string) input('no', $_SESSION['last_order'] ?? '');
$order = $no !== '' ? row('SELECT * FROM orders WHERE order_no = ?', [$no]) : null;
$u = current_user();
// Sipariş bilgileri yalnızca siparişi veren oturuma veya sipariş sahibi üyeye gösterilir
$owner = $order && (($_SESSION['last_order'] ?? '') === $order['order_no'] || ($u && (int) $order['user_id'] === (int) $u['id']));
if (!$order || !$owner) {
    redirect('');
}
$paid = in_array($order['status'], SALE_STATUSES, true);
if ($paid && ($_SESSION['last_order'] ?? '') === $order['order_no']) {
    unset($_SESSION['cart'], $_SESSION['checkout']); // ödeme onaylandı: sepeti boşalt
}
$items = rows('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
$waiting = $order['status'] === 'odeme_bekliyor';
$failed = $order['status'] === 'odeme_basarisiz';
$title = $paid ? 'Siparişiniz alındı' : ($failed ? 'Ödeme başarısız' : 'Ödemeniz kontrol ediliyor');
// Ödeme bildirimi PayTR'den birkaç saniye içinde gelir; beklerken sayfa kendini yeniler
$tries = (int) input('d', 0);
require __DIR__ . '/app/header.php';
?>
<?php if ($waiting && $tries < 10): ?><meta http-equiv="refresh" content="3;url=<?= e(url('siparis-tamam.php?no=' . urlencode($order['order_no']) . '&d=' . ($tries + 1))) ?>"><?php endif; ?>
<section class="section">
    <div class="container narrow">
        <div class="card center" style="padding:40px">
            <?php if ($paid): ?>
                <div style="font-size:3rem">🎉</div>
                <h1>Teşekkürler, siparişiniz alındı!</h1>
                <p class="muted">Sipariş numaranız</p>
                <p style="font-size:1.6rem;font-weight:800;color:var(--red);margin:0"><?= e($order['order_no']) ?></p>
                <p class="muted">Siparişiniz <?= e(setting('shipping_days')) ?> içinde kargoya verilir. Sipariş takibi sayfasından durumunu izleyebilirsiniz.</p>
            <?php elseif ($failed): ?>
                <div style="font-size:3rem">⚠️</div>
                <h1>Ödeme tamamlanamadı</h1>
                <p class="muted">Kartınızdan çekim yapılmadı. Kart bilgilerinizi kontrol edip tekrar deneyebilir veya farklı bir kart kullanabilirsiniz.</p>
                <a class="btn btn-primary btn-lg" href="<?= url('odeme-paytr.php?no=' . urlencode($order['order_no'])) ?>">Tekrar Dene</a>
                <a class="btn btn-dark" href="<?= url('sepet.php') ?>">Sepete Dön</a>
            <?php else: ?>
                <div style="font-size:3rem">⏳</div>
                <h1>Ödemeniz kontrol ediliyor…</h1>
                <p class="muted">Bankanızdan onay bekleniyor. Bu sayfa birkaç saniye içinde kendiliğinden yenilenecek.</p>
                <?php if ($tries >= 10): ?>
                    <p class="alert alert-info">Onay biraz gecikti. Ödemeniz başarılıysa siparişiniz birkaç dakika içinde onaylanır ve e-posta adresinize bilgi gelir. Sorunuz olursa sipariş numaranızla bizimle iletişime geçin: <strong><?= e($order['order_no']) ?></strong></p>
                    <a class="btn btn-dark" href="<?= url('siparis-tamam.php?no=' . urlencode($order['order_no'])) ?>">Tekrar Kontrol Et</a>
                <?php endif; ?>
            <?php endif; ?>
            <table class="data-table" style="margin:20px 0;text-align:left">
                <?php foreach ($items as $i): ?><tr><td><?= e($i['name']) ?><?= $i['size'] ? ' · ' . e($i['size']) : '' ?></td><td><?= $i['qty'] ?> adet</td><td style="text-align:right"><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?>
                <tr><td colspan="2">Kargo</td><td style="text-align:right"><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Ücretsiz' ?></td></tr>
                <tr><th colspan="2">Toplam</th><th style="text-align:right"><?= money($order['total']) ?></th></tr>
            </table>
            <?php if ($paid): ?>
                <a class="btn btn-primary" href="<?= url('siparis-takibi.php?no=' . urlencode($order['order_no']) . '&email=' . urlencode($order['email'])) ?>">Siparişimi Takip Et</a>
                <a class="btn btn-dark" href="<?= url('urunler.php') ?>">Alışverişe Devam Et</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
