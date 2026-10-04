<?php
/**
 * Garanti BBVA dönüş adresi (successurl / errorurl) ve ödeme sonuç sayfası.
 * Banka bu adrese POST ile döner; tarayıcı çerezleri gelmeyebileceği için sipariş,
 * banka yanıtındaki orderid ile bulunur ve ardından sonuç sayfasına yönlendirilir.
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/garanti.php';

if (is_post() && isset($_POST['orderid'])) {
    $post = $_POST;
    $order = row('SELECT * FROM orders WHERE order_no = ?', [(string) $post['orderid']]);
    if (!$order) {
        http_response_code(404);
        exit('Sipariş bulunamadı.');
    }
    $p = array_change_key_case($post, CASE_LOWER);
    if (!garanti_verify_response($post)) {
        log_activity('Ödeme doğrulama hatası', $order['order_no'] . ' · Banka imzası (hash) doğrulanamadı', 'order', (int) $order['id']);
        $order = finalize_order($order, false, 'Banka yanıtı doğrulanamadı (hash hatası).', null, $post);
    } elseif (isset($p['txnamount']) && $p['txnamount'] !== '' && (string) $p['txnamount'] !== garanti_amount($order)) {
        $order = finalize_order($order, false, 'Tutar uyuşmazlığı.', null, $post);
    } elseif (garanti_is_success($post)) {
        $ref = trim(($p['authcode'] ?? '') . ' / ' . ($p['hostrefnum'] ?? $p['retrefnum'] ?? ''), ' /');
        $order = finalize_order($order, true, 'Onaylandı', $ref, $post);
    } else {
        $order = finalize_order($order, false, garanti_error_message($post), null, $post);
    }
    redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
}

$order = row('SELECT * FROM orders WHERE order_no = ?', [input('no')]);
if (!$order || !hash_equals(order_access_token($order), (string) input('t'))) {
    http_response_code(404);
    exit('Sipariş bulunamadı.');
}
$ok = in_array($order['status'], SALE_STATUSES, true);
$pageTitle = $ok ? 'Ödeme Başarılı' : 'Ödeme Sonucu';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container narrow-sm">
    <div class="card center result-card <?= $ok ? 'ok' : 'fail' ?>">
        <div class="result-icon"><?= $ok ? '✓' : '!' ?></div>
        <?php if ($ok): ?>
            <h1 class="h2">Ödemeniz alındı, teşekkürler!</h1>
            <p><b><?= e($order['service_title']) ?> - <?= e($order['package_name']) ?></b> siparişiniz onaylandı. Koçunuz en geç 24 saat içinde sizinle iletişime geçerek ilk dersinizi planlayacak.</p>
        <?php elseif ($order['status'] === 'pending'): ?>
            <h1 class="h2">Ödeme bekleniyor</h1>
            <p>Siparişiniz için henüz ödeme tamamlanmadı.</p>
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
        <?php else: ?>
            <a class="btn btn-primary" href="<?= url('odeme.php?siparis=' . urlencode($order['order_no'])) ?>">Tekrar Dene</a>
            <a class="btn btn-outline" href="<?= url('iletisim.php') ?>">Destek Al</a>
        <?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
