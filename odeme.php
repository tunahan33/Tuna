<?php
/** Sipariş ve ödeme akışı: 1) Fatura bilgileri  2) Sözleşme onayı ve ödeme  3) Sipariş alındı */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/payment.php';

$user = require_login();

function contract_vars(array $o): array
{
    return [
        'alici_ad' => $o['invoice_type'] === 'kurumsal' && $o['company_name'] ? $o['company_name'] . ' (' . $o['customer_name'] . ')' : $o['customer_name'],
        'alici_adres' => $o['customer_address'] . ($o['customer_city'] ? ' / ' . $o['customer_city'] : ''),
        'alici_telefon' => $o['customer_phone'], 'alici_eposta' => $o['customer_email'],
        'hizmet_adi' => $o['service_title'], 'paket_adi' => $o['package_name'],
        'paket_sure' => $o['_duration'] ?? '-', 'toplam_tutar' => money($o['amount']),
        'siparis_tarihi' => tr_date($o['created_at']),
    ];
}

/* ---------- 2. adım: sözleşme onayı ve siparişin oluşturulması ---------- */
if ($orderNo = input('siparis')) {
    $order = row('SELECT o.*, p.duration AS _duration, p.sessions AS _sessions FROM orders o LEFT JOIN packages p ON p.id = o.package_id WHERE o.order_no = ? AND o.user_id = ?', [$orderNo, $user['id']]);
    if (!$order) {
        flash('error', 'Sipariş bulunamadı.');
        redirect('hesabim.php');
    }
    // Onaylanmış siparişler tekrar düzenlenemez
    if ($order['contract_accepted_at'] || !in_array($order['status'], ['pending', 'failed'], true)) {
        redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
    }

    if (is_post() && input('action') === 'confirm') {
        verify_csrf();
        if (!input('accept_pre') || !input('accept_contract') || !input('accept_start')) {
            flash('error', 'Siparişi tamamlamak için tüm onay kutularını işaretlemelisiniz.');
            redirect('odeme.php?siparis=' . urlencode($order['order_no']));
        }
        $contact = in_array(input('contact_time'), ['Hemen', 'Sabah (09-12)', 'Öğle (12-15)', 'Akşamüstü (15-18)'], true) ? input('contact_time') : 'Hemen';
        q('UPDATE orders SET status = ?, payment_method = ?, contract_accepted_at = ?, ip = ?, payment_message = ?, updated_at = ? WHERE id = ?',
            ['pending', 'iletisim', now(), client_ip(), 'Müşteri ödeme için aranmak istiyor · Uygun zaman: ' . $contact, now(), $order['id']]);
        $order = row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
        log_activity('Siparişi onayladı', $order['order_no'] . ' · ' . money($order['amount']) . ' · Aranma: ' . $contact, 'order', (int) $order['id']);

        send_mail($order['customer_email'], 'Siparişiniz alındı - ' . $order['order_no'],
            '<p>Merhaba ' . e($order['customer_name']) . ',</p><p><b>' . e($order['service_title']) . ' - ' . e($order['package_name']) . '</b> siparişiniz alındı.</p>'
            . '<p>Sipariş No: <b>' . e($order['order_no']) . '</b><br>Tutar: <b>' . money($order['amount']) . '</b> (KDV dahil)</p>'
            . '<p>Ekibimiz ödeme ve başlangıç planı için en kısa sürede <b>' . e($order['customer_phone']) . '</b> numarasından sizinle iletişime geçecek.</p>');
        send_mail(setting('notify_email'), 'Yeni sipariş: ' . $order['order_no'] . ' (' . money($order['amount']) . ')',
            '<p><b>' . e($order['customer_name']) . '</b> · ' . e($order['customer_phone']) . ' · ' . e($order['customer_email']) . '</p>'
            . '<p>' . e($order['service_title']) . ' / ' . e($order['package_name']) . ' · <b>' . money($order['amount']) . '</b></p>'
            . '<p>Uygun aranma zamanı: <b>' . e($contact) . '</b></p><p>Ödeme alındığında panelden siparişi “Ödendi” yapın.</p>');
        redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
    }

    $vars = contract_vars($order);
    $pre = row('SELECT content FROM pages WHERE slug = ?', ['on-bilgilendirme-formu'])['content'] ?? '';
    $contract = row('SELECT content FROM pages WHERE slug = ?', ['mesafeli-satis-sozlesmesi'])['content'] ?? '';
    $pageTitle = 'Ödeme';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section"><div class="container">
        <ol class="checkout-steps"><li class="done">Fatura Bilgileri</li><li class="active">Onay ve Ödeme</li><li>Sipariş Alındı</li></ol>
        <div class="checkout-grid">
            <form method="post" class="card pay-card">
                <?= csrf_field() ?><input type="hidden" name="action" value="confirm">
                <h2 class="h3">1. Sözleşmeler</h2>
                <details class="contract-acc"><summary>Ön Bilgilendirme Formu</summary><div class="contract-box prose"><?= fill_placeholders($pre, $vars) ?></div></details>
                <details class="contract-acc"><summary>Mesafeli Satış Sözleşmesi</summary><div class="contract-box prose"><?= fill_placeholders($contract, $vars) ?></div></details>
                <div class="form consent">
                    <label class="check"><input type="checkbox" name="accept_pre" value="1" required> <span>Ön Bilgilendirme Formu'nu okudum ve onaylıyorum.</span></label>
                    <label class="check"><input type="checkbox" name="accept_contract" value="1" required> <span>Mesafeli Satış Sözleşmesi'ni ve <a href="<?= url('sayfa.php?s=iptal-ve-iade-kosullari') ?>" target="_blank">İptal ve İade Koşulları</a>'nı okudum, kabul ediyorum.</span></label>
                    <label class="check"><input type="checkbox" name="accept_start" value="1" required> <span>Hizmetin cayma süresi içinde başlatılmasını talep ediyorum; hizmet başladıktan sonra cayma hakkımın kalmayacağını biliyorum.</span></label>
                </div>

                <h2 class="h3 mt-2">2. Ödeme</h2>
                <div class="pay-options">
                    <div class="pay-option selected">
                        <span class="po-icon">📞</span>
                        <div><strong>Ödeme için sizi arayalım</strong><small>Siparişinizi oluşturun; danışmanınız sizi arayarak ödemeyi ve başlangıç tarihini birlikte netleştirsin. Ödeme alınmadan hizmet başlamaz ve sizden ücret alınmaz.</small></div>
                    </div>
                    <div class="pay-option disabled" aria-disabled="true">
                        <span class="po-icon">💳</span>
                        <div><strong>Kartla online ödeme <span class="soon">Yakında</span></strong><small>Kredi ve banka kartıyla 3D Secure güvenli ödeme çok yakında bu sayfada.</small></div>
                    </div>
                </div>
                <label class="contact-time">Sizi ne zaman arayalım?
                    <select name="contact_time"><option>Hemen</option><option>Sabah (09-12)</option><option>Öğle (12-15)</option><option>Akşamüstü (15-18)</option></select>
                </label>
                <p class="small muted">Arayacağımız numara: <strong><?= e($order['customer_phone']) ?></strong> · <a href="<?= url('odeme.php?paket=' . (int) $order['package_id']) ?>">değiştir</a></p>
                <button class="btn btn-primary btn-lg btn-block">Siparişi Oluştur</button>
                <p class="small muted center">🔒 Bilgileriniz 256-bit SSL ile şifrelenerek iletilir.</p>
            </form>
            <aside class="card summary">
                <h3>Sipariş Özeti</h3>
                <div class="sum-package">
                    <span class="eyebrow"><?= e($order['service_title']) ?></span>
                    <strong><?= e($order['package_name']) ?></strong>
                    <small><?= e($order['_duration'] ?? '') ?><?= !empty($order['_sessions']) ? ' · ' . e($order['_sessions']) : '' ?></small>
                </div>
                <dl>
                    <dt>Sipariş No</dt><dd><?= e($order['order_no']) ?></dd>
                    <dt>Fatura</dt><dd><?= e($vars['alici_ad']) ?><br><small><?= e($vars['alici_adres']) ?></small></dd>
                    <dt>E-posta</dt><dd><?= e($order['customer_email']) ?></dd>
                </dl>
                <div class="summary-total"><span>Toplam (KDV dahil)</span><strong><?= money($order['amount']) ?></strong></div>
                <ul class="trust-mini">
                    <li>✓ Ödeme alınmadan hizmet başlamaz</li>
                    <li>✓ Hizmet başlamadan iptalde tam iade</li>
                    <li>✓ 3 iş günü içinde ilk görüşme</li>
                </ul>
                <a class="small" href="<?= url('odeme.php?paket=' . (int) $order['package_id']) ?>">← Bilgileri düzenle</a>
            </aside>
        </div>
    </div></section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---------- 1. adım: fatura bilgileri ---------- */
$pkg = row('SELECT p.*, s.title AS service_title FROM packages p JOIN services s ON s.id = p.service_id WHERE p.id = ? AND p.is_active = 1 AND s.is_active = 1', [(int) input('paket')]);
if (!$pkg) {
    flash('error', 'Paket bulunamadı.');
    redirect('paketler.php');
}
$last = row('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']]) ?? [];
$f = [
    'customer_name' => $last['customer_name'] ?? $user['name'], 'customer_email' => $last['customer_email'] ?? $user['email'],
    'customer_phone' => $last['customer_phone'] ?? $user['phone'], 'customer_address' => $last['customer_address'] ?? '',
    'customer_city' => $last['customer_city'] ?? '', 'identity_no' => $last['identity_no'] ?? '',
    'invoice_type' => $last['invoice_type'] ?? 'bireysel', 'company_name' => $last['company_name'] ?? '',
    'tax_office' => $last['tax_office'] ?? '', 'tax_number' => $last['tax_number'] ?? '', 'customer_note' => '',
];

if (is_post()) {
    verify_csrf();
    foreach ($f as $k => $_) {
        $f[$k] = mb_substr((string) input($k), 0, $k === 'customer_note' ? 2000 : 500);
    }
    $f['invoice_type'] = $f['invoice_type'] === 'kurumsal' ? 'kurumsal' : 'bireysel';
    $err = null;
    if (mb_strlen($f['customer_name']) < 3) $err = 'Ad soyad girin.';
    elseif (!filter_var($f['customer_email'], FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta girin.';
    elseif (strlen(preg_replace('/\D/', '', $f['customer_phone'])) < 10) $err = 'Geçerli bir telefon numarası girin.';
    elseif (mb_strlen($f['customer_address']) < 10) $err = 'Fatura adresinizi eksiksiz girin.';
    elseif ($f['invoice_type'] === 'kurumsal' && (!$f['company_name'] || !$f['tax_office'] || !$f['tax_number'])) $err = 'Kurumsal fatura için firma ünvanı, vergi dairesi ve vergi numarası zorunludur.';
    elseif ($f['identity_no'] !== '' && !preg_match('/^\d{11}$/', $f['identity_no'])) $err = 'T.C. kimlik numarası 11 haneli olmalıdır.';

    if ($err) {
        flash('error', $err);
    } else {
        // Aynı paket için bekleyen sipariş varsa güncelle, yoksa yeni oluştur
        $existing = row("SELECT * FROM orders WHERE user_id = ? AND package_id = ? AND status IN ('pending','failed') AND contract_accepted_at IS NULL ORDER BY id DESC LIMIT 1", [$user['id'], $pkg['id']]);
        $data = $f + ['amount' => $pkg['price'], 'package_name' => $pkg['name'], 'service_title' => $pkg['service_title'], 'updated_at' => now(), 'ip' => client_ip()];
        if ($existing) {
            update('orders', $data, (int) $existing['id']);
            $no = $existing['order_no'];
            $oid = (int) $existing['id'];
        } else {
            $no = generate_order_no();
            $oid = insert('orders', $data + ['order_no' => $no, 'user_id' => $user['id'], 'package_id' => $pkg['id'], 'status' => 'pending', 'created_at' => now()]);
            log_activity('Sipariş oluşturdu', $no . ' · ' . $pkg['service_title'] . ' / ' . $pkg['name'] . ' · ' . money($pkg['price']), 'order', $oid);
        }
        if (!$user['phone'] && $f['customer_phone']) {
            q('UPDATE users SET phone = ? WHERE id = ?', [$f['customer_phone'], $user['id']]);
        }
        redirect('odeme.php?siparis=' . urlencode($no));
    }
}

$pageTitle = 'Ödeme - Fatura Bilgileri';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container">
    <ol class="checkout-steps"><li class="active">Fatura Bilgileri</li><li>Onay ve Ödeme</li><li>Sipariş Alındı</li></ol>
    <div class="checkout-grid">
        <div class="card">
            <h2 class="h3">Fatura ve İletişim Bilgileri</h2>
            <form method="post" class="form" data-invoice-form>
                <?= csrf_field() ?>
                <div class="segmented">
                    <label><input type="radio" name="invoice_type" value="bireysel" <?= $f['invoice_type'] !== 'kurumsal' ? 'checked' : '' ?>> Bireysel</label>
                    <label><input type="radio" name="invoice_type" value="kurumsal" <?= $f['invoice_type'] === 'kurumsal' ? 'checked' : '' ?>> Kurumsal</label>
                </div>
                <div class="grid-2">
                    <label>Ad Soyad *<input name="customer_name" value="<?= e($f['customer_name']) ?>" required></label>
                    <label>T.C. Kimlik No <small>(e-arşiv fatura için)</small><input name="identity_no" value="<?= e($f['identity_no']) ?>" inputmode="numeric" maxlength="11"></label>
                    <label>E-posta *<input type="email" name="customer_email" value="<?= e($f['customer_email']) ?>" required></label>
                    <label>Telefon *<input name="customer_phone" value="<?= e($f['customer_phone']) ?>" required></label>
                </div>
                <div class="grid-3 corporate-fields">
                    <label>Firma Ünvanı<input name="company_name" value="<?= e($f['company_name']) ?>"></label>
                    <label>Vergi Dairesi<input name="tax_office" value="<?= e($f['tax_office']) ?>"></label>
                    <label>Vergi No<input name="tax_number" value="<?= e($f['tax_number']) ?>"></label>
                </div>
                <div class="grid-2">
                    <label>İl *<input name="customer_city" value="<?= e($f['customer_city']) ?>" required></label>
                    <span></span>
                </div>
                <label>Fatura Adresi *<textarea name="customer_address" rows="2" required><?= e($f['customer_address']) ?></textarea></label>
                <label>Sipariş Notu <small>(branşınız, hedefiniz, uygun görüşme saatleri vb.)</small><textarea name="customer_note" rows="3"><?= e($f['customer_note']) ?></textarea></label>
                <button class="btn btn-primary btn-lg btn-block">Devam Et</button>
            </form>
        </div>
        <aside class="card summary">
            <h3>Sipariş Özeti</h3>
            <dl>
                <dt>Hizmet</dt><dd><?= e($pkg['service_title']) ?></dd>
                <dt>Paket</dt><dd><?= e($pkg['name']) ?></dd>
                <dt>Süre</dt><dd><?= e($pkg['duration']) ?><?= $pkg['sessions'] ? ' · ' . e($pkg['sessions']) : '' ?></dd>
            </dl>
            <ul class="check-list small"><?php foreach (features_list($pkg['features']) as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
            <div class="summary-total"><span>Toplam (KDV dahil)</span><strong><?= money($pkg['price']) ?></strong></div>
            <ul class="trust-mini"><li>✓ Ödeme alınmadan hizmet başlamaz</li><li>✓ Hizmet başlamadan iptalde tam iade</li></ul>
        </aside>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
