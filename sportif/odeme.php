<?php
/** Ödeme: 1) Teslimat ve fatura bilgileri  2) Sözleşme onayı  3) Garanti BBVA 3D ödeme */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/garanti.php';

$user = require_login();

function contract_vars(array $o): array
{
    return [
        'alici_ad' => $o['invoice_type'] === 'kurumsal' && $o['company_name'] ? $o['company_name'] . ' (' . $o['customer_name'] . ')' : $o['customer_name'],
        'alici_adres' => $o['customer_address'] . ($o['customer_city'] ? ' / ' . $o['customer_city'] : ''),
        'teslimat_adres' => $o['ship_name'] . ', ' . $o['ship_address'] . ' ' . $o['ship_district'] . ' / ' . $o['ship_city'],
        'alici_telefon' => $o['customer_phone'], 'alici_eposta' => $o['customer_email'],
        'ara_toplam' => money($o['subtotal']), 'indirim' => $o['discount'] > 0 ? '-' . money($o['discount']) . ' (' . $o['coupon_code'] . ')' : 'Yok',
        'kargo_ucreti' => $o['shipping_fee'] > 0 ? money($o['shipping_fee']) : 'Ücretsiz', 'toplam_tutar' => money($o['amount']),
        'siparis_tarihi' => tr_date($o['created_at']),
    ];
}

function contract_html(string $slug, array $o): string
{
    $content = row('SELECT content FROM pages WHERE slug = ?', [$slug])['content'] ?? '';
    return fill_placeholders(str_replace('{{urun_listesi}}', order_items_html((int) $o['id']), $content), contract_vars($o));
}

/** Siparişteki ürünlerin hâlâ stokta olup olmadığını kontrol eder */
function order_stock_problems(array $o): array
{
    $problems = [];
    foreach (rows('SELECT i.*, v.stock FROM order_items i LEFT JOIN product_variants v ON v.id = i.variant_id WHERE i.order_id = ?', [$o['id']]) as $it) {
        $need = (int) val('SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id = ? AND variant_id = ?', [$o['id'], $it['variant_id']]);
        if ($it['stock'] === null || (int) $it['stock'] < $need) {
            $problems[] = $it['product_name'] . ' (' . $it['size'] . ', ' . $it['color'] . ') için yeterli stok kalmadı.';
        }
    }
    return array_unique($problems);
}

/* ---------- 2. ve 3. adım ---------- */
if ($orderNo = input('siparis')) {
    $order = row('SELECT * FROM orders WHERE order_no = ? AND user_id = ?', [$orderNo, $user['id']]);
    if (!$order) {
        flash('error', 'Sipariş bulunamadı.');
        redirect('hesabim.php');
    }
    if (!in_array($order['status'], ['pending', 'failed'], true)) {
        redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
    }
    $hasPrint = (bool) val("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND (COALESCE(print_name,'') <> '' OR COALESCE(print_number,'') <> '')", [$order['id']]);

    if (is_post() && input('action') === 'pay') {
        verify_csrf();
        if (!input('accept_pre') || !input('accept_contract') || ($hasPrint && !input('accept_print'))) {
            flash('error', 'Ödemeye geçmek için tüm onay kutularını işaretlemelisiniz.');
            redirect('odeme.php?siparis=' . urlencode($order['order_no']));
        }
        if ($problems = order_stock_problems($order)) {
            foreach ($problems as $pr) flash('error', $pr);
            flash('info', 'Lütfen sepetinizi güncelleyip tekrar deneyin.');
            redirect('sepet.php');
        }
        if ($order['status'] === 'failed') {
            $newNo = generate_order_no();
            q('UPDATE orders SET order_no = ?, status = ?, updated_at = ? WHERE id = ?', [$newNo, 'pending', now(), $order['id']]);
            $order['order_no'] = $newNo;
            $order['status'] = 'pending';
        }
        q('UPDATE orders SET contract_accepted_at = ?, ip = ?, updated_at = ? WHERE id = ?', [now(), client_ip(), now(), $order['id']]);
        $order['ip'] = client_ip();
        log_activity('Ödemeye yönlendirildi', $order['order_no'] . ' · ' . money($order['amount']) . ' · Mod: ' . pos_mode(), 'order', (int) $order['id']);

        $pageTitle = 'Güvenli Ödeme';
        require __DIR__ . '/includes/header.php';
        echo '<section class="section"><div class="container narrow-sm"><div class="card center">';
        if (pos_mode() === 'demo' && !can('panel.access')) {
            echo '<h1 class="h2">Online ödeme yakında</h1><p>Kartla ödeme altyapımız kısa süre içinde aktif olacak. Siparişinizi tamamlamak için lütfen bizimle iletişime geçin: <b>' . e(setting('company_phone')) . '</b> · <a href="' . url('iletisim.php') . '">İletişim formu</a></p>';
        } elseif (pos_mode() === 'demo') {
            echo '<h1 class="h2">Demo Ödeme (Yalnızca Personel)</h1><p>Mağaza <b>demo modunda</b>. Bu ekranı yalnızca personel görür; Garanti BBVA bilgileri girildiğinde bu adımda bankanın 3D Secure ödeme sayfası açılır.</p>';
            echo '<form method="post" action="' . url('odeme-demo.php') . '" class="form">' . csrf_field() . '<input type="hidden" name="order_no" value="' . e($order['order_no']) . '">';
            echo '<button name="result" value="success" class="btn btn-primary btn-block">Başarılı Ödeme Simüle Et</button> <button name="result" value="fail" class="btn btn-outline btn-block">Başarısız Ödeme Simüle Et</button></form>';
        } elseif (garanti_security_level() === '3D_OOS_PAY') {
            echo '<h1 class="h2">Bankaya yönlendiriliyorsunuz…</h1><p>Garanti BBVA güvenli ödeme sayfası açılıyor.</p><form id="bankForm" method="post" action="' . e(garanti_endpoint()) . '">';
            foreach (garanti_form_fields($order) as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
            echo '<button class="btn btn-primary">Ödeme sayfasına git</button></form><script>document.getElementById("bankForm").submit();</script>';
        } else {
            echo '<h1 class="h2">Kart Bilgileri</h1><p class="small muted">Kart bilgileriniz doğrudan Garanti BBVA\'ya iletilir, sitemizde saklanmaz.</p><form method="post" action="' . e(garanti_endpoint()) . '" class="form card-form">';
            foreach (garanti_form_fields($order) as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
            echo '<label>Kart Üzerindeki İsim<input name="cardholdername" autocomplete="cc-name" required></label><label>Kart Numarası<input name="cardnumber" inputmode="numeric" autocomplete="cc-number" pattern="[0-9 ]{15,19}" maxlength="19" required data-card-number></label><div class="grid-3"><label>Ay<select name="cardexpiredatemonth" required>';
            for ($m = 1; $m <= 12; $m++) echo '<option>' . sprintf('%02d', $m) . '</option>';
            echo '</select></label><label>Yıl<select name="cardexpiredateyear" required>';
            for ($y = (int) date('y'); $y <= (int) date('y') + 12; $y++) echo '<option>' . sprintf('%02d', $y) . '</option>';
            echo '</select></label><label>CVV<input name="cardcvv2" inputmode="numeric" pattern="[0-9]{3,4}" maxlength="4" autocomplete="cc-csc" required></label></div><div class="pay-total">Ödenecek Tutar: <strong>' . money($order['amount']) . '</strong></div><button class="btn btn-primary btn-block btn-lg">Ödemeyi Tamamla</button></form>';
        }
        echo '<img src="' . asset('img/payment-logos.svg') . '" alt="" height="30" class="mt-1"></div></div></section>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    $items = rows('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
    $pageTitle = 'Sipariş Onayı';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section-sm"><div class="container">
        <ol class="checkout-steps"><li class="done">Sepet</li><li class="done">Teslimat</li><li class="active">Onay</li><li>Ödeme</li></ol>
        <div class="checkout-grid">
            <div class="card">
                <h2 class="h3">Sözleşmeler ve Onay</h2>
                <?php if ($order['status'] === 'failed'): ?><div class="alert alert-error">Önceki ödeme denemesi başarısız oldu: <?= e($order['payment_message']) ?>. Tekrar deneyebilirsiniz.</div><?php endif; ?>
                <h4>Ön Bilgilendirme Formu</h4>
                <div class="contract-box prose"><?= contract_html('on-bilgilendirme-formu', $order) ?></div>
                <h4>Mesafeli Satış Sözleşmesi</h4>
                <div class="contract-box prose"><?= contract_html('mesafeli-satis-sozlesmesi', $order) ?></div>
                <form method="post" class="form mt-1">
                    <?= csrf_field() ?><input type="hidden" name="action" value="pay">
                    <label class="check"><input type="checkbox" name="accept_pre" value="1" required> <span>Ön Bilgilendirme Formu'nu okudum ve onaylıyorum.</span></label>
                    <label class="check"><input type="checkbox" name="accept_contract" value="1" required> <span>Mesafeli Satış Sözleşmesi'ni ve <a href="<?= url('sayfa.php?s=iade-ve-degisim') ?>" target="_blank">İade ve Değişim Koşulları</a>'nı okudum, kabul ediyorum.</span></label>
                    <?php if ($hasPrint): ?><label class="check"><input type="checkbox" name="accept_print" value="1" required> <span>İsim/numara baskılı ürünlerin kişiye özel üretildiğini ve <strong>iade edilemeyeceğini</strong> biliyorum.</span></label><?php endif; ?>
                    <button class="btn btn-primary btn-lg btn-block"><?= money($order['amount']) ?> Öde</button>
                    <p class="small muted center">Ödeme sayfasında Garanti BBVA 3D Secure doğrulaması yapılacaktır.</p>
                </form>
            </div>
            <aside class="card summary">
                <h3>Sipariş Özeti</h3>
                <ul class="mini-items"><?php foreach ($items as $it): ?><li><span><?= e($it['product_name']) ?><small><?= e($it['size']) ?> · <?= e($it['color']) ?><?= ($it['print_name'] || $it['print_number']) ? ' · Baskı: ' . e(trim($it['print_name'] . ' ' . $it['print_number'])) : '' ?> · <?= (int) $it['qty'] ?> adet</small></span><strong><?= money($it['line_total']) ?></strong></li><?php endforeach; ?></ul>
                <div class="sum-rows">
                    <div><span>Ara Toplam</span><span><?= money($order['subtotal']) ?></span></div>
                    <?php if ($order['discount'] > 0): ?><div class="disc"><span>İndirim (<?= e($order['coupon_code']) ?>)</span><span>−<?= money($order['discount']) ?></span></div><?php endif; ?>
                    <div><span>Kargo</span><span><?= $order['shipping_fee'] > 0 ? money($order['shipping_fee']) : 'Ücretsiz' ?></span></div>
                </div>
                <div class="summary-total"><span>Toplam</span><strong><?= money($order['amount']) ?></strong></div>
                <p class="small"><strong>Teslimat:</strong> <?= e($order['ship_name']) ?>, <?= e($order['ship_address']) ?> <?= e($order['ship_district']) ?>/<?= e($order['ship_city']) ?></p>
                <a class="small" href="<?= url('odeme.php') ?>">← Bilgileri düzenle</a>
            </aside>
        </div>
    </div></section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---------- 1. adım: teslimat ve fatura ---------- */
$sum = cart_summary();
if (!$sum['lines']) {
    flash('info', 'Sepetiniz boş.');
    redirect('sepet.php');
}
$last = row('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']]) ?? [];
$fields = ['customer_name', 'customer_email', 'customer_phone', 'ship_name', 'ship_phone', 'ship_address', 'ship_district', 'ship_city', 'customer_address', 'customer_city', 'identity_no', 'invoice_type', 'company_name', 'tax_office', 'tax_number', 'customer_note'];
$f = [];
foreach ($fields as $k) $f[$k] = $last[$k] ?? '';
$f['customer_name'] = $f['customer_name'] ?: $user['name'];
$f['customer_email'] = $f['customer_email'] ?: $user['email'];
$f['customer_phone'] = $f['customer_phone'] ?: (string) $user['phone'];
$f['invoice_type'] = $f['invoice_type'] ?: 'bireysel';
$f['customer_note'] = '';
$sameBilling = !$last || ($last['customer_address'] ?? '') === ($last['ship_address'] ?? '');

if (is_post()) {
    verify_csrf();
    foreach ($fields as $k) $f[$k] = mb_substr(trim((string) input($k)), 0, $k === 'customer_note' ? 1000 : 300);
    $f['invoice_type'] = $f['invoice_type'] === 'kurumsal' ? 'kurumsal' : 'bireysel';
    $sameBilling = (bool) input('same_billing');
    if ($sameBilling) {
        $f['customer_address'] = trim($f['ship_address'] . ' ' . $f['ship_district']);
        $f['customer_city'] = $f['ship_city'];
    }
    $digits = fn($v) => strlen(preg_replace('/\D/', '', $v));
    $err = null;
    if (mb_strlen($f['customer_name']) < 3) $err = 'Ad soyad girin.';
    elseif (!filter_var($f['customer_email'], FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta girin.';
    elseif ($digits($f['customer_phone']) < 10) $err = 'Geçerli bir telefon numarası girin.';
    elseif (mb_strlen($f['ship_name']) < 3 || $digits($f['ship_phone']) < 10) $err = 'Teslimat için alıcı adı ve telefonu girin.';
    elseif (mb_strlen($f['ship_address']) < 10 || $f['ship_district'] === '' || $f['ship_city'] === '') $err = 'Teslimat adresini il, ilçe ve açık adres olarak eksiksiz girin.';
    elseif (mb_strlen($f['customer_address']) < 10) $err = 'Fatura adresini girin.';
    elseif ($f['invoice_type'] === 'kurumsal' && (!$f['company_name'] || !$f['tax_office'] || !$f['tax_number'])) $err = 'Kurumsal fatura için firma ünvanı, vergi dairesi ve vergi numarası zorunludur.';
    elseif ($f['identity_no'] !== '' && !preg_match('/^\d{11}$/', $f['identity_no'])) $err = 'T.C. kimlik numarası 11 haneli olmalıdır.';

    $sum = cart_summary();
    if (!$err && !$sum['lines']) $err = 'Sepetinizdeki ürünler stokta kalmadı.';
    if ($err) {
        flash('error', $err);
    } else {
        $data = $f + [
            'subtotal' => $sum['subtotal'], 'discount' => $sum['discount'], 'coupon_code' => $sum['coupon']['code'] ?? null,
            'shipping_fee' => $sum['shipping'], 'amount' => $sum['total'], 'item_count' => $sum['count'], 'ip' => client_ip(), 'updated_at' => now(),
        ];
        $existing = !empty($_SESSION['checkout_order']) ? row("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending','failed')", [$_SESSION['checkout_order'], $user['id']]) : null;
        if ($existing) {
            update('orders', $data, (int) $existing['id']);
            $oid = (int) $existing['id'];
            $no = $existing['order_no'];
            q('DELETE FROM order_items WHERE order_id = ?', [$oid]);
        } else {
            $no = generate_order_no();
            $oid = insert('orders', $data + ['order_no' => $no, 'user_id' => $user['id'], 'status' => 'pending', 'created_at' => now()]);
            log_activity('Sipariş oluşturdu', $no . ' · ' . $sum['count'] . ' ürün · ' . money($sum['total']), 'order', $oid);
        }
        foreach ($sum['lines'] as $l) {
            insert('order_items', [
                'order_id' => $oid, 'product_id' => $l['product_id'], 'variant_id' => $l['variant_id'], 'product_name' => $l['name'],
                'size' => $l['size'], 'color' => $l['color'], 'print_name' => $l['personal'] ? $l['print_name'] : null, 'print_number' => $l['personal'] ? $l['print_number'] : null,
                'unit_price' => $l['unit_price'], 'qty' => $l['qty'], 'line_total' => $l['line_total'],
            ]);
        }
        $_SESSION['checkout_order'] = $oid;
        if (!$user['phone']) q('UPDATE users SET phone = ? WHERE id = ?', [$f['customer_phone'], $user['id']]);
        redirect('odeme.php?siparis=' . urlencode($no));
    }
}

$pageTitle = 'Teslimat Bilgileri';
require __DIR__ . '/includes/header.php';
?>
<section class="section-sm"><div class="container">
    <ol class="checkout-steps"><li class="done">Sepet</li><li class="active">Teslimat</li><li>Onay</li><li>Ödeme</li></ol>
    <div class="checkout-grid">
        <form method="post" class="card form" data-invoice-form>
            <?= csrf_field() ?>
            <h2 class="h3">İletişim Bilgileri</h2>
            <div class="grid-3">
                <label>Ad Soyad *<input name="customer_name" value="<?= e($f['customer_name']) ?>" required></label>
                <label>E-posta *<input type="email" name="customer_email" value="<?= e($f['customer_email']) ?>" required></label>
                <label>Telefon *<input name="customer_phone" value="<?= e($f['customer_phone']) ?>" required placeholder="05xx xxx xx xx"></label>
            </div>
            <h2 class="h3 mt-1">Teslimat Adresi</h2>
            <div class="grid-2">
                <label>Alıcı Adı Soyadı *<input name="ship_name" value="<?= e($f['ship_name'] ?: $f['customer_name']) ?>" required></label>
                <label>Alıcı Telefonu *<input name="ship_phone" value="<?= e($f['ship_phone'] ?: $f['customer_phone']) ?>" required></label>
                <label>İl *<input name="ship_city" value="<?= e($f['ship_city']) ?>" required></label>
                <label>İlçe *<input name="ship_district" value="<?= e($f['ship_district']) ?>" required></label>
            </div>
            <label>Açık Adres * <small>(mahalle, sokak, bina ve daire no)</small><textarea name="ship_address" rows="2" required><?= e($f['ship_address']) ?></textarea></label>
            <h2 class="h3 mt-1">Fatura Bilgileri</h2>
            <label class="check"><input type="checkbox" name="same_billing" value="1" <?= $sameBilling ? 'checked' : '' ?> data-same-billing> <span>Fatura adresim teslimat adresiyle aynı</span></label>
            <div class="billing-fields" data-billing>
                <div class="grid-2"><label>Fatura Adresi<textarea name="customer_address" rows="2"><?= e($f['customer_address']) ?></textarea></label><label>Fatura İli<input name="customer_city" value="<?= e($f['customer_city']) ?>"></label></div>
            </div>
            <div class="segmented">
                <label><input type="radio" name="invoice_type" value="bireysel" <?= $f['invoice_type'] !== 'kurumsal' ? 'checked' : '' ?>> Bireysel</label>
                <label><input type="radio" name="invoice_type" value="kurumsal" <?= $f['invoice_type'] === 'kurumsal' ? 'checked' : '' ?>> Kurumsal</label>
            </div>
            <label>T.C. Kimlik No <small>(isteğe bağlı, e-arşiv fatura için)</small><input name="identity_no" value="<?= e($f['identity_no']) ?>" inputmode="numeric" maxlength="11"></label>
            <div class="grid-3 corporate-fields">
                <label>Firma / Kulüp Ünvanı<input name="company_name" value="<?= e($f['company_name']) ?>"></label>
                <label>Vergi Dairesi<input name="tax_office" value="<?= e($f['tax_office']) ?>"></label>
                <label>Vergi No<input name="tax_number" value="<?= e($f['tax_number']) ?>"></label>
            </div>
            <label>Sipariş Notu <small>(isteğe bağlı)</small><textarea name="customer_note" rows="2"><?= e($f['customer_note']) ?></textarea></label>
            <button class="btn btn-primary btn-lg btn-block">Devam Et</button>
        </form>
        <aside class="card summary">
            <h3>Sepet Özeti</h3>
            <ul class="mini-items"><?php foreach ($sum['lines'] as $l): ?><li><span><?= e($l['name']) ?><small><?= e($l['size']) ?> · <?= e($l['color']) ?><?= $l['personal'] ? ' · Baskı: ' . e(trim($l['print_name'] . ' ' . $l['print_number'])) : '' ?> · <?= $l['qty'] ?> adet</small></span><strong><?= money($l['line_total']) ?></strong></li><?php endforeach; ?></ul>
            <div class="sum-rows">
                <div><span>Ara Toplam</span><span><?= money($sum['subtotal']) ?></span></div>
                <?php if ($sum['coupon']): ?><div class="disc"><span>İndirim (<?= e($sum['coupon']['code']) ?>)</span><span>−<?= money($sum['discount']) ?></span></div><?php endif; ?>
                <div><span>Kargo</span><span><?= $sum['shipping'] > 0 ? money($sum['shipping']) : 'Ücretsiz' ?></span></div>
            </div>
            <div class="summary-total"><span>Toplam</span><strong><?= money($sum['total']) ?></strong></div>
            <a class="small" href="<?= url('sepet.php') ?>">← Sepete dön</a>
        </aside>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
