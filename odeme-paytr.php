<?php
/** PayTR güvenli ödeme formu (iframe). Kart bilgileri doğrudan PayTR'ye girilir. */
require __DIR__ . '/app/bootstrap.php';

$no = (string) input('no');
// Yalnızca siparişi oluşturan oturum ödeme sayfasını açabilir
if ($no === '' || ($_SESSION['last_order'] ?? '') !== $no) {
    redirect('sepet.php');
}
$order = row('SELECT * FROM orders WHERE order_no = ?', [$no]);
if (!$order) {
    redirect('sepet.php');
}
if (!in_array($order['status'], ['odeme_bekliyor', 'odeme_basarisiz'], true)) {
    redirect('siparis-tamam.php?no=' . urlencode($no));
}
if (!paytr_enabled()) {
    flash('error', 'Online ödeme şu an kullanılamıyor. Lütfen bizimle iletişime geçin.');
    redirect('sepet.php');
}

// Başarısız ödemeden sonra yeniden deneme: PayTR her deneme için yeni sipariş numarası ister
if ($order['status'] === 'odeme_basarisiz') {
    $newNo = new_order_no();
    update('orders', ['order_no' => $newNo, 'status' => 'odeme_bekliyor', 'updated_at' => now()], (int) $order['id']);
    order_history((int) $order['id'], 'Ödeme yeniden denendi (önceki no: ' . $no . ')', $order['customer_name']);
    $_SESSION['last_order'] = $newNo;
    redirect('odeme-paytr.php?no=' . urlencode($newNo));
}

// Sayfa yenilenirse aynı token kullanılır (PayTR token'ı 30 dk geçerlidir)
$cache = $_SESSION['paytr_token'][$no] ?? null;
if ($cache && time() - $cache['t'] < 25 * 60) {
    $token = $cache['token'];
    $error = null;
} else {
    [$token, $error] = paytr_get_token($order);
    if ($token) {
        $_SESSION['paytr_token'][$no] = ['token' => $token, 't' => time()];
    } else {
        order_history((int) $order['id'], 'PayTR token alınamadı: ' . $error, 'Sistem');
    }
}

$title = 'Güvenli Ödeme';
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="container narrow">
        <div class="steps"><span>1. Sepet</span><span>2. Teslimat Bilgileri</span><span>3. Sözleşme</span><span class="on">4. Ödeme</span></div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
                <h2 style="margin:0">Güvenli Ödeme</h2>
                <span class="muted small">Sipariş <?= e($order['order_no']) ?> · <strong><?= money($order['total']) ?></strong></span>
            </div>
            <?php if ($token): ?>
                <div class="secure-note" style="margin-bottom:14px">🔒 Ödeme, PayTR güvencesiyle 3D Secure ile alınır. Kart bilgileriniz sitemize iletilmez ve saklanmaz.</div>
                <?php if (paytr_test_mode()): ?><p class="alert alert-info small">TEST modu: gerçek para çekilmez. PayTR'nin test kartlarını kullanın.</p><?php endif; ?>
                <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                <iframe src="<?= e(PAYTR_IFRAME_URL . $token) ?>" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:600px;border:0" title="PayTR güvenli ödeme"></iframe>
                <script>if (window.iFrameResize) iFrameResize({}, '#paytriframe');</script>
            <?php else: ?>
                <div class="alert alert-error">Ödeme sayfası şu an açılamadı. Lütfen birkaç dakika sonra tekrar deneyin ya da bizimle iletişime geçin.</div>
                <a class="btn btn-primary" href="<?= url('odeme-paytr.php?no=' . urlencode($no)) ?>">Tekrar Dene</a>
                <a class="btn btn-dark" href="<?= url('iletisim.php') ?>">İletişim</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
