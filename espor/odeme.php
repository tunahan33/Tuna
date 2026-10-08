<?php
/** Ödeme akışı: 1) Fatura bilgileri  2) Sözleşme onayı  3) Garanti BBVA 3D ödeme */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/garanti.php';

$user = require_login();

function contract_vars(array $o): array
{
    return [
        'alici_ad' => $o['invoice_type'] === 'kurumsal' && $o['company_name'] ? $o['company_name'] . ' (' . $o['customer_name'] . ')' : $o['customer_name'],
        'alici_adres' => $o['customer_address'] . ($o['customer_city'] ? ' / ' . $o['customer_city'] : ''),
        'alici_telefon' => $o['customer_phone'], 'alici_eposta' => $o['customer_email'],
        'hizmet_adi' => $o['service_title'], 'paket_adi' => $o['package_name'],
        'paket_sure' => $o['_duration'] ?? '-',
        'toplam_tutar' => money($o['amount']) . (!empty($o['voucher_code']) ? ' (Paket bedeli ' . money((float) $o['amount'] + (float) $o['voucher_amount']) . ', ' . money($o['voucher_amount']) . ' iade çeki ile ödenmiştir)' : ''),
        'siparis_tarihi' => tr_date($o['created_at']),
        'odeme_sekli' => payment_methods_text($o),
    ];
}

/* ---------- 2. ve 3. adım: mevcut sipariş ---------- */
if ($orderNo = input('siparis')) {
    $order = row('SELECT o.*, p.duration AS _duration FROM orders o LEFT JOIN packages p ON p.id = o.package_id WHERE o.order_no = ? AND o.user_id = ?', [$orderNo, $user['id']]);
    if (!$order) {
        flash('error', 'Sipariş bulunamadı.');
        redirect('hesabim.php');
    }
    if (!in_array($order['status'], ['pending', 'failed', 'awaiting_transfer'], true)) {
        redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
    }

    if (is_post() && input('action') === 'pay') {
        verify_csrf();
        if (!input('accept_pre') || !input('accept_contract') || !input('accept_start')) {
            flash('error', 'Ödemeye geçmek için tüm sözleşme onaylarını işaretlemelisiniz.');
            redirect('odeme.php?siparis=' . urlencode($order['order_no']));
        }
        $method = input('payment_method') === 'havale' ? 'havale' : 'kart';
        if (($method === 'havale' && !transfer_payment_available()) || ($method === 'kart' && !card_payment_available())) {
            flash('error', 'Seçilen ödeme yöntemi şu anda kullanılamıyor. Lütfen başka bir yöntem seçin veya bizimle iletişime geçin.');
            redirect('odeme.php?siparis=' . urlencode($order['order_no']));
        }
        // Havale seçip sonradan kartla ödemek isteyen sipariş tekrar ödeme bekliyor durumuna alınır
        if ($order['status'] === 'awaiting_transfer') {
            q("UPDATE orders SET status = 'pending', updated_at = ? WHERE id = ?", [now(), $order['id']]);
            $order['status'] = 'pending';
        }
        // Başarısız bir denemeden sonra bankaya yeni sipariş numarası ile gidilir
        if ($order['status'] === 'failed') {
            $newNo = generate_order_no();
            q('UPDATE orders SET order_no = ?, status = ?, updated_at = ? WHERE id = ?', [$newNo, 'pending', now(), $order['id']]);
            $order['order_no'] = $newNo;
            $order['status'] = 'pending';
        }
        q('UPDATE orders SET contract_accepted_at = ?, ip = ?, updated_at = ? WHERE id = ?', [now(), client_ip(), now(), $order['id']]);
        $order['ip'] = client_ip();

        // İade çeki ödeme anında tekrar kontrol edilir (başka siparişte kullanılmış olabilir)
        if (!empty($order['voucher_code'])) {
            [$v, $use, $verr] = voucher_check($order['voucher_code'], $order['customer_email'], (float) $order['amount'] + (float) $order['voucher_amount']);
            if ($verr || abs($use - (float) $order['voucher_amount']) > 0.009) {
                flash('error', ($verr ?: 'İade çeki bakiyeniz değişmiş.') . ' Lütfen bilgileri tekrar onaylayın.');
                redirect('odeme.php?paket=' . (int) $order['package_id']);
            }
            if ((float) $order['amount'] <= 0) {
                q("UPDATE orders SET payment_method = 'iade_ceki' WHERE id = ?", [$order['id']]);
                $order = finalize_order(row('SELECT * FROM orders WHERE id = ?', [$order['id']]), true, 'İade çeki ile ödendi', 'IC-' . $order['order_no'], ['mode' => 'voucher', 'voucher' => $order['voucher_code']]);
                redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
            }
        }

        // Havale / EFT: sipariş "Havale Bekleniyor" durumuna alınır, ödeme panelden onaylanır
        if ($method === 'havale') {
            q("UPDATE orders SET status = 'awaiting_transfer', payment_method = 'havale', updated_at = ? WHERE id = ?", [now(), $order['id']]);
            $order = row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
            log_activity('Havale ile ödemeyi seçti', $order['order_no'] . ' · ' . money($order['amount']), 'order', (int) $order['id']);
            send_mail($order['customer_email'], 'Siparişiniz alındı, ödeme bilgileri - ' . $order['order_no'],
                '<p>Merhaba ' . e($order['customer_name']) . ',</p><p><b>' . e($order['service_title']) . ' - ' . e($order['package_name']) . '</b> siparişiniz alındı. '
                . 'Ödemenizi aşağıdaki hesaba <b>açıklama kısmına sipariş numaranızı yazarak</b> yapabilirsiniz:</p>' . transfer_info_html($order)
                . '<p>Ödemeniz hesabımıza geçtiğinde (genellikle aynı iş günü) siparişiniz onaylanır ve koçunuz sizinle iletişime geçer. 3 iş günü içinde ödemesi yapılmayan siparişler iptal edilir.</p>');
            send_mail(setting('notify_email'), 'Havale bekleyen sipariş: ' . $order['order_no'] . ' (' . money($order['amount']) . ')',
                '<p>' . e($order['customer_name']) . ' - ' . e($order['service_title']) . ' / ' . e($order['package_name']) . '</p><p>Ödeme hesaba geçince panelden siparişi “Ödendi” durumuna alın.</p>');
            redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
        }
        q("UPDATE orders SET payment_method = 'kart' WHERE id = ?", [$order['id']]);
        log_activity('Ödemeye yönlendirildi', $order['order_no'] . ' · ' . money($order['amount']) . ' · Mod: ' . pos_mode(), 'order', (int) $order['id']);

        $mode = pos_mode();
        $pageTitle = 'Güvenli Ödeme';
        require __DIR__ . '/includes/header.php';
        echo '<section class="section"><div class="container narrow-sm"><div class="card center">';
        if ($mode === 'demo') {
            echo '<h1 class="h2">Demo Ödeme</h1><p>Site şu anda <b>demo modunda</b>. Garanti BBVA bilgileri girildiğinde bu adımda bankanın 3D Secure ödeme sayfası açılır.</p>';
            echo '<form method="post" action="' . url('odeme-demo.php') . '" class="form">' . csrf_field() . '<input type="hidden" name="order_no" value="' . e($order['order_no']) . '">';
            echo '<button name="result" value="success" class="btn btn-primary btn-block">Başarılı Ödeme Simüle Et</button> <button name="result" value="fail" class="btn btn-outline btn-block">Başarısız Ödeme Simüle Et</button></form>';
        } elseif (garanti_security_level() === '3D_OOS_PAY') {
            echo '<h1 class="h2">Bankaya yönlendiriliyorsunuz…</h1><p>Garanti BBVA güvenli ödeme sayfası açılıyor. Lütfen bekleyin.</p>';
            echo '<form id="bankForm" method="post" action="' . e(garanti_endpoint()) . '">';
            foreach (garanti_form_fields($order) as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
            echo '<button class="btn btn-primary">Ödeme sayfasına git</button></form><script>document.getElementById("bankForm").submit();</script>';
        } else {
            echo '<h1 class="h2">Kart Bilgileri</h1><p class="small muted">Kart bilgileriniz doğrudan Garanti BBVA\'ya iletilir, sitemizde saklanmaz.</p>';
            echo '<form method="post" action="' . e(garanti_endpoint()) . '" class="form card-form" autocomplete="on">';
            foreach (garanti_form_fields($order) as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
            echo '<label>Kart Üzerindeki İsim<input name="cardholdername" autocomplete="cc-name" required></label>';
            echo '<label>Kart Numarası<input name="cardnumber" inputmode="numeric" autocomplete="cc-number" pattern="[0-9 ]{15,19}" maxlength="19" required data-card-number></label>';
            echo '<div class="grid-3"><label>Ay<select name="cardexpiredatemonth" required>';
            for ($m = 1; $m <= 12; $m++) echo '<option>' . sprintf('%02d', $m) . '</option>';
            echo '</select></label><label>Yıl<select name="cardexpiredateyear" required>';
            for ($y = (int) date('y'); $y <= (int) date('y') + 12; $y++) echo '<option>' . sprintf('%02d', $y) . '</option>';
            echo '</select></label><label>CVV<input name="cardcvv2" inputmode="numeric" pattern="[0-9]{3,4}" maxlength="4" autocomplete="cc-csc" required></label></div>';
            echo '<div class="pay-total">Ödenecek Tutar: <strong>' . money($order['amount']) . '</strong></div>';
            echo '<button class="btn btn-primary btn-block btn-lg">Ödemeyi Tamamla</button></form>';
        }
        echo '<img src="' . asset('img/payment-logos.svg') . '" alt="" height="30" class="mt-1"></div></div></section>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    $vars = contract_vars($order);
    $pre = row('SELECT content FROM pages WHERE slug = ?', ['on-bilgilendirme-formu'])['content'] ?? '';
    $contract = row('SELECT content FROM pages WHERE slug = ?', ['mesafeli-satis-sozlesmesi'])['content'] ?? '';
    $pageTitle = 'Sipariş Onayı';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section"><div class="container">
        <ol class="checkout-steps"><li class="done">Fatura Bilgileri</li><li class="active">Sözleşme ve Onay</li><li>Güvenli Ödeme</li></ol>
        <div class="checkout-grid">
            <div class="card">
                <h2 class="h3">Sözleşmeler</h2>
                <?php if ($order['status'] === 'failed'): ?><div class="alert alert-error">Önceki ödeme denemesi başarısız oldu: <?= e($order['payment_message']) ?>. Tekrar deneyebilirsiniz.</div><?php endif; ?>
                <h4>Ön Bilgilendirme Formu</h4>
                <div class="contract-box prose"><?= fill_placeholders($pre, $vars) ?></div>
                <h4>Mesafeli Satış Sözleşmesi</h4>
                <div class="contract-box prose"><?= fill_placeholders($contract, $vars) ?></div>
                <form method="post" class="form mt-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="pay">
                    <?php $cardOk = card_payment_available(); $transferOk = transfer_payment_available(); $defMethod = $order['payment_method'] === 'havale' && $transferOk ? 'havale' : ($cardOk ? 'kart' : 'havale'); ?>
                    <h4>Ödeme Yöntemi</h4>
                    <?php if (!$cardOk && !$transferOk): ?>
                        <div class="alert alert-info">Online ödeme altyapımız kısa süre içinde aktif olacaktır. Siparişinizi tamamlamak için lütfen <a href="<?= url('iletisim.php?konu=' . urlencode('Sipariş ' . $order['order_no'])) ?>">bizimle iletişime geçin</a>.</div>
                    <?php else: ?>
                    <div class="pay-methods">
                        <?php if ($cardOk): ?>
                        <label class="pay-method"><input type="radio" name="payment_method" value="kart" <?= $defMethod === 'kart' ? 'checked' : '' ?>>
                            <span><strong>Kredi / Banka Kartı</strong><small>Garanti BBVA 3D Secure ile anında onay<?= pos_mode() === 'demo' ? ' · <b>DEMO: yalnızca personel görür, gerçek tahsilat yapılmaz</b>' : '' ?></small></span></label>
                        <?php endif; ?>
                        <?php if ($transferOk): ?>
                        <label class="pay-method"><input type="radio" name="payment_method" value="havale" <?= $defMethod === 'havale' ? 'checked' : '' ?>>
                            <span><strong>Havale / EFT</strong><small><?= e(setting('bank_name')) ?> hesabımıza; ödemeniz hesaba geçince siparişiniz onaylanır</small></span></label>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <label class="check"><input type="checkbox" name="accept_pre" value="1" required> <span>Ön Bilgilendirme Formu'nu okudum ve onaylıyorum.</span></label>
                    <label class="check"><input type="checkbox" name="accept_contract" value="1" required> <span>Mesafeli Satış Sözleşmesi'ni ve <a href="<?= url('sayfa.php?s=iade-ve-iade-ceki-kosullari') ?>" target="_blank">İade ve İade Çeki Koşulları</a>'nı okudum, kabul ediyorum.</span></label>
                    <label class="check"><input type="checkbox" name="accept_start" value="1" required> <span>Hizmetin cayma süresi içinde başlatılmasını talep ediyorum; hizmet başladıktan sonra cayma hakkımın kalmayacağını biliyorum.</span></label>
                    <button class="btn btn-primary btn-lg btn-block" <?= !$cardOk && !$transferOk && (float) $order['amount'] > 0 ? 'disabled' : '' ?>><?= (float) $order['amount'] > 0 ? money($order['amount']) . ' · Siparişi Onayla' : 'İade Çeki ile Siparişi Tamamla' ?></button>
                    <p class="small muted center">Kartla ödemede Garanti BBVA 3D Secure sayfasına yönlendirilirsiniz; Havale/EFT seçerseniz hesap bilgileri gösterilir.</p>
                </form>
            </div>
            <aside class="card summary">
                <h3>Sipariş Özeti</h3>
                <dl>
                    <dt>Sipariş No</dt><dd><?= e($order['order_no']) ?></dd>
                    <dt>Hizmet</dt><dd><?= e($order['service_title']) ?></dd>
                    <dt>Paket</dt><dd><?= e($order['package_name']) ?></dd>
                    <dt>Fatura</dt><dd><?= e($vars['alici_ad']) ?><br><small><?= e($vars['alici_adres']) ?></small></dd>
                </dl>
                <?php if (!empty($order['voucher_code'])): ?>
                    <dl class="voucher-lines"><dt>Paket bedeli</dt><dd><?= money((float) $order['amount'] + (float) $order['voucher_amount']) ?></dd><dt>İade çeki <small><?= e($order['voucher_code']) ?></small></dt><dd>− <?= money($order['voucher_amount']) ?></dd></dl>
                <?php endif; ?>
                <div class="summary-total"><span><?= !empty($order['voucher_code']) ? 'Kartla ödenecek' : 'Toplam (KDV dahil)' ?></span><strong><?= money($order['amount']) ?></strong></div>
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
    'voucher_code' => (string) input('iade_ceki'),
];
$voucherUse = 0.0;

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

    if (!$err && $f['voucher_code'] !== '') {
        [$v, $voucherUse, $err] = voucher_check($f['voucher_code'], $f['customer_email'], (float) $pkg['price']);
        $f['voucher_code'] = $v ? $v['code'] : $f['voucher_code'];
    }

    if ($err) {
        flash('error', $err);
    } else {
        // Aynı paket için bekleyen sipariş varsa güncelle, yoksa yeni oluştur
        $existing = row("SELECT * FROM orders WHERE user_id = ? AND package_id = ? AND status IN ('pending','failed','awaiting_transfer') ORDER BY id DESC LIMIT 1", [$user['id'], $pkg['id']]);
        $data = ['voucher_code' => $f['voucher_code'] !== '' ? $f['voucher_code'] : null, 'voucher_amount' => $voucherUse > 0 ? $voucherUse : null] + $f
            + ['amount' => round((float) $pkg['price'] - $voucherUse, 2), 'package_name' => $pkg['name'], 'service_title' => $pkg['service_title'], 'updated_at' => now(), 'ip' => client_ip()];
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
    <ol class="checkout-steps"><li class="active">Fatura Bilgileri</li><li>Sözleşme ve Onay</li><li>Güvenli Ödeme</li></ol>
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
                <details class="voucher-field" <?= $f['voucher_code'] !== '' ? 'open' : '' ?>>
                    <summary>İade çeki kodum var</summary>
                    <label>İade Çeki Kodu <small>(yalnızca çekin tanımlandığı e-posta adresiyle kullanılabilir)</small><input name="voucher_code" value="<?= e($f['voucher_code']) ?>" placeholder="IC-XXXX-XXXX" autocomplete="off"></label>
                </details>
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
            <p class="small muted">İade çeki kodunuz varsa sonraki adımda tutardan düşülür.</p>
            <img src="<?= asset('img/payment-logos.svg') ?>" alt="Visa, Mastercard, Troy, 3D Secure" class="w100 mt-1">
        </aside>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
