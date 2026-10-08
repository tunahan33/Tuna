<?php
/** Sipariş sonuç sayfası (sipariş numarası + erişim anahtarı ile açılır) */
require __DIR__ . '/includes/bootstrap.php';

$order = row('SELECT * FROM orders WHERE order_no = ?', [input('no')]);
if (!$order || !hash_equals(order_access_token($order), (string) input('t'))) {
    http_response_code(404);
    exit('Sipariş bulunamadı.');
}
$paid = in_array($order['status'], SALE_STATUSES, true);
$waiting = $order['status'] === 'pending' && $order['contract_accepted_at'];
$pageTitle = $paid ? 'Ödeme Alındı' : 'Sipariş Alındı';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container narrow-sm">
    <ol class="checkout-steps"><li class="done">Fatura Bilgileri</li><li class="done">Onay ve Ödeme</li><li class="active">Sipariş Alındı</li></ol>
    <div class="card center result-card <?= $paid || $waiting ? 'ok' : 'fail' ?>">
        <div class="result-icon"><?= $paid || $waiting ? '✓' : '!' ?></div>
        <?php if ($paid): ?>
            <h1 class="h2">Ödemeniz alındı, teşekkürler!</h1>
            <p><b><?= e($order['service_title']) ?> - <?= e($order['package_name']) ?></b> siparişiniz onaylandı. Danışmanınız en geç 3 iş günü içinde sizinle iletişime geçecek.</p>
        <?php elseif ($waiting): ?>
            <h1 class="h2">Siparişiniz alındı!</h1>
            <p><b><?= e($order['service_title']) ?> - <?= e($order['package_name']) ?></b> siparişiniz oluşturuldu. Ekibimiz ödeme ve başlangıç planı için <b><?= e($order['customer_phone']) ?></b> numarasından en kısa sürede sizi arayacak.</p>
            <ol class="next-steps">
                <li><strong>Sizi arıyoruz</strong><span>Ödeme ve başlangıç tarihini birlikte netleştiriyoruz.</span></li>
                <li><strong>Ödeme</strong><span>Ödeme onaylandığında e-posta ile bilgilendirilirsiniz.</span></li>
                <li><strong>İlk görüşme</strong><span>En geç 3 iş günü içinde danışmanınızla tanışırsınız.</span></li>
            </ol>
        <?php elseif (in_array($order['status'], ['cancelled', 'refunded'], true)): ?>
            <h1 class="h2">Bu sipariş <?= $order['status'] === 'cancelled' ? 'iptal edildi' : 'iade edildi' ?></h1>
            <p>Sorularınız için bizimle iletişime geçebilirsiniz.</p>
        <?php else: ?>
            <h1 class="h2">Siparişiniz tamamlanmadı</h1>
            <p>Siparişi oluşturmak için onay adımını tamamlayın.</p>
        <?php endif; ?>
        <dl class="inline-dl">
            <dt>Sipariş No</dt><dd><?= e($order['order_no']) ?></dd>
            <dt>Tutar</dt><dd><?= money($order['amount']) ?></dd>
            <dt>Durum</dt><dd><?= status_badge($order['status']) ?></dd>
        </dl>
        <?php if (in_array($order['status'], ['pending', 'failed'], true) && !$order['contract_accepted_at']): ?>
            <a class="btn btn-primary" href="<?= url('odeme.php?siparis=' . urlencode($order['order_no'])) ?>">Siparişi Tamamla</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= url('hesabim.php') ?>">Siparişlerim</a>
            <a class="btn btn-outline" href="<?= url('iletisim.php') ?>">Bize Ulaşın</a>
        <?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
