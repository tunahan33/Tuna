<?php
/** Sipariş sonuç sayfası (ödeme sağlayıcısı bağlandığında dönüş adresi olarak da kullanılır) */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/payment.php';

$order = row('SELECT * FROM orders WHERE order_no = ?', [input('no')]);
if (!$order || !hash_equals(order_access_token($order), (string) input('t'))) {
    http_response_code(404);
    exit('Sipariş bulunamadı.');
}
$ok = in_array($order['status'], SALE_STATUSES, true);
$durum = (string) input('durum');
$refresh = (int) input('r');
// PayTR müşteriyi başarılı ödemeden sonra buraya döndürür; onay bildirimi birkaç saniye gecikebilir
$processing = !$ok && $order['status'] === 'pending' && $durum === 'ok' && $refresh < 10;
$pageTitle = $ok ? 'Sipariş Onaylandı' : 'Sipariş Durumu';
require __DIR__ . '/includes/header.php';
?>
<script>if (window.top !== window.self) { window.top.location.href = window.location.href; }</script>
<section class="section"><div class="container narrow-sm">
    <?php $wait = $order['status'] === 'pending'; ?>
    <div class="card center result-card <?= $ok ? 'ok' : ($wait ? 'wait' : 'fail') ?>">
        <div class="result-icon"><?= $ok ? '✓' : ($wait ? '⧗' : '!') ?></div>
        <?php if ($ok): ?>
            <h1 class="h2">Siparişiniz onaylandı, teşekkürler!</h1>
            <p><b><?= e($order['service_title']) ?> - <?= e($order['package_name']) ?></b> siparişiniz onaylandı. Koçunuz en geç 24 saat içinde sizinle iletişime geçerek ilk dersinizi planlayacak.</p>
        <?php elseif ($processing): ?>
            <h1 class="h2">Ödemeniz işleniyor…</h1>
            <p>Bankanızdan onay bekleniyor. Bu sayfa birkaç saniye içinde kendiliğinden yenilenecek; lütfen kapatmayın.</p>
            <script>setTimeout(function () { var u = new URL(location.href); u.searchParams.set('r', <?= $refresh + 1 ?>); location.replace(u.toString()); }, 3000);</script>
        <?php elseif ($order['status'] === 'pending' && $durum === 'hata'): ?>
            <h1 class="h2">Ödeme tamamlanamadı</h1>
            <p>Ödeme işleminiz banka tarafından onaylanmadı veya yarıda kaldı. Kartınızdan çekim yapılmadı; tekrar deneyebilirsiniz.</p>
        <?php elseif ($order['status'] === 'pending'): ?>
            <h1 class="h2">Ödeme bekleniyor</h1>
            <p>Siparişiniz kaydedildi; ödemesi henüz tamamlanmadı.<?= $durum === 'ok' ? ' Ödemeniz bankanızdan onaylandıysa birkaç dakika içinde siparişlerinize yansıyacak ve e-posta ile bilgilendirileceksiniz.' : '' ?></p>
        <?php else: ?>
            <h1 class="h2">Ödeme tamamlanamadı</h1>
            <p><?= e($order['payment_message'] ?: 'Ödeme işlemi banka tarafından onaylanmadı.') ?></p>
        <?php endif; ?>
        <dl class="inline-dl">
            <dt>Sipariş No</dt><dd><?= e($order['order_no']) ?></dd>
            <dt>Tutar</dt><dd><?= money($order['amount']) ?></dd>
            <dt>Durum</dt><dd><?= status_badge($order['status']) ?></dd>
        </dl>
        <?php if ($ok): ?>
            <a class="btn btn-primary" href="<?= url('hesabim.php') ?>">Siparişlerim</a>
        <?php elseif (!$processing): ?>
            <a class="btn btn-primary" href="<?= url('odeme.php?siparis=' . urlencode($order['order_no'])) ?>">Tekrar Dene</a>
            <a class="btn btn-outline" href="<?= url('iletisim.php') ?>">Destek Al</a>
        <?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
