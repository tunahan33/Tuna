<?php
/**
 * Ödeme: 1) Teslimat bilgileri  2) Sözleşmeler + kart bilgileri  → sipariş oluşturulur.
 * Ödeme yöntemi Panel > Mağaza Ayarları > Ödeme bölümünden seçilir:
 *  - PayTR: sipariş "Ödeme Bekleniyor" olarak açılır, kart bilgileri PayTR'nin güvenli sayfasına girilir (odeme-paytr.php)
 *  - Demo: kart doğrulanır ama çekim yapılmaz (deneme amaçlı)
 * Her iki durumda da kart bilgisi sitemizde saklanmaz.
 * Gerçek satışa geçerken bankanızın / ödeme kuruluşunuzun (iyzico, PayTR, banka sanal POS) entegrasyonu eklenir.
 */
require __DIR__ . '/app/bootstrap.php';

$s = cart_summary();
if (!$s['lines']) {
    flash('info', 'Sepetiniz boş.');
    redirect('sepet.php');
}
$u = current_user();
$paytr = paytr_enabled();
$step = isset($_SESSION['checkout']) && input('adim') !== '1' ? 2 : 1;
$buyer = $_SESSION['checkout'] ?? [
    'name' => $u['name'] ?? '', 'email' => $u['email'] ?? '', 'phone' => $u['phone'] ?? '',
    'city' => '', 'district' => '', 'address' => '', 'note' => '',
];

if (is_post()) {
    verify_csrf();
    if (input('step') === '1') {
        foreach (array_keys($buyer) as $k) {
            $buyer[$k] = mb_substr((string) input($k), 0, $k === 'address' || $k === 'note' ? 500 : 120);
        }
        $errors = [];
        if (mb_strlen($buyer['name']) < 5 || !str_contains($buyer['name'], ' ')) $errors[] = 'Ad ve soyadınızı girin.';
        if (!filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
        if (strlen(preg_replace('/\D/', '', $buyer['phone'])) < 10) $errors[] = 'Geçerli bir telefon numarası girin.';
        if (!$buyer['city'] || !$buyer['district'] || mb_strlen($buyer['address']) < 10) $errors[] = 'Teslimat adresini eksiksiz girin.';
        if ($errors) {
            flash('error', implode(' ', $errors));
            $_SESSION['checkout_draft'] = $buyer;
            redirect('odeme.php?adim=1');
        }
        $_SESSION['checkout'] = $buyer;
        redirect('odeme.php');
    }

    // Adım 2: siparişi oluştur
    $card = preg_replace('/\D/', '', (string) input('card_number'));
    $exp = (string) input('card_exp');
    $errors = [];
    if (!input('accept_contract') || !input('accept_info')) $errors[] = 'Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi\'ni onaylamanız gerekir.';
    if (!$paytr) {
        if (!luhn_ok($card)) $errors[] = 'Kart numarası geçersiz.';
        if (!preg_match('#^(0[1-9]|1[0-2])/(\d{2})$#', $exp, $m) || mktime(0, 0, 0, (int) $m[1] + 1, 1, 2000 + (int) $m[2]) <= time()) $errors[] = 'Son kullanma tarihi geçersiz.';
        if (!preg_match('/^\d{3,4}$/', (string) input('card_cvv'))) $errors[] = 'CVV geçersiz.';
        if (mb_strlen((string) input('card_name')) < 4) $errors[] = 'Kart üzerindeki ismi girin.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('odeme.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Stok kontrolü; demo ödemede stok hemen düşer, PayTR'de ödeme onaylanınca (paytr-bildirim.php) düşer
        foreach ($s['lines'] as $l) {
            $ok = $paytr
                ? (int) val('SELECT stock >= ? FROM products WHERE id = ?', [$l['qty'], $l['product']['id']])
                : q('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$l['qty'], $l['product']['id'], $l['qty']])->rowCount();
            if (!$ok) {
                throw new RuntimeException($l['product']['name'] . ' için yeterli stok kalmadı.');
            }
        }
        $no = new_order_no();
        $oid = insert('orders', [
            'order_no' => $no, 'user_id' => $u['id'] ?? null, 'customer_name' => $buyer['name'], 'email' => mb_strtolower($buyer['email']),
            'phone' => $buyer['phone'], 'city' => $buyer['city'], 'district' => $buyer['district'], 'address' => $buyer['address'], 'note' => $buyer['note'],
            'subtotal' => $s['subtotal'], 'shipping' => $s['shipping'], 'total' => $s['total'], 'status' => $paytr ? 'odeme_bekliyor' : 'yeni',
            'card_last4' => $paytr ? null : substr($card, -4), 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($s['lines'] as $l) {
            insert('order_items', ['order_id' => $oid, 'product_id' => $l['product']['id'], 'name' => $l['product']['name'], 'size' => $l['size'], 'price' => $l['product']['price'], 'qty' => $l['qty']]);
        }
        order_history($oid, $paytr ? 'Sipariş oluşturuldu, PayTR ödeme sayfasına yönlendirildi' : 'Sipariş oluşturuldu, ödeme alındı (DEMO, kart ****' . substr($card, -4) . ')', $buyer['name']);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        flash('error', $ex instanceof RuntimeException ? $ex->getMessage() : 'Sipariş oluşturulamadı, lütfen tekrar deneyin.');
        redirect('sepet.php');
    }
    $_SESSION['last_order'] = $no;
    if ($paytr) {
        // Sepet, ödeme onaylanınca boşaltılır (siparis-tamam.php)
        redirect('odeme-paytr.php?no=' . urlencode($no));
    }
    log_activity('Sipariş verdi', $no . ' · ' . money($s['total']) . ' · DEMO ödeme', 'admin/siparis.php?id=' . $oid, $u ?? ['id' => null, 'name' => $buyer['name'], 'role' => 'ziyaretci']);
    unset($_SESSION['cart'], $_SESSION['checkout']);
    redirect('siparis-tamam.php?no=' . urlencode($no));
}

if ($step === 1 && isset($_SESSION['checkout_draft'])) {
    $buyer = $_SESSION['checkout_draft'];
    unset($_SESSION['checkout_draft']);
}

/** Sözleşme metnini alıcı ve sepet bilgileriyle doldurur */
function contract(string $slug, array $buyer, array $s): string
{
    $page = row('SELECT content FROM pages WHERE slug = ?', [$slug]);
    $list = '<table><tr><th>Ürün</th><th>Beden</th><th>Adet</th><th>Birim Fiyat</th><th>Tutar</th></tr>';
    foreach ($s['lines'] as $l) {
        $list .= '<tr><td>' . e($l['product']['name']) . '</td><td>' . e($l['size'] ?: '-') . '</td><td>' . $l['qty'] . '</td><td>' . money($l['product']['price']) . '</td><td>' . money($l['total']) . '</td></tr>';
    }
    $list .= '</table>';
    $html = str_replace('{{urun_listesi}}', $list, $page['content'] ?? '');
    return fill_placeholders($html, [
        'alici_ad' => $buyer['name'], 'alici_adres' => $buyer['address'] . ' ' . $buyer['district'] . '/' . $buyer['city'],
        'alici_telefon' => $buyer['phone'], 'alici_eposta' => $buyer['email'], 'ara_toplam' => money($s['subtotal']),
        'kargo_bedeli' => $s['shipping'] > 0 ? money($s['shipping']) : 'Ücretsiz', 'toplam_tutar' => money($s['total']), 'siparis_tarihi' => date('d.m.Y'),
    ]);
}

$cities = ['Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın', 'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş', 'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kilis', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Siirt', 'Sinop', 'Sivas', 'Şanlıurfa', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'];
$title = 'Ödeme';
require __DIR__ . '/app/header.php';
?>
<section class="section-sm">
    <div class="container">
        <div class="steps"><span>1. Sepet</span><span class="<?= $step === 1 ? 'on' : '' ?>">2. Teslimat Bilgileri</span><span class="<?= $step === 2 ? 'on' : '' ?>">3. Sözleşme ve Ödeme</span></div>
        <div class="two-col">
            <div>
            <?php if ($step === 1): ?>
                <form method="post" class="card form">
                    <?= csrf_field() ?><input type="hidden" name="step" value="1">
                    <h2>Teslimat Bilgileri</h2>
                    <?php if (!$u): ?><p class="alert alert-info">Üye olmadan alışveriş yapabilirsiniz. Siparişlerinizi tek yerden görmek için <a href="<?= url('giris.php') ?>">giriş yapın</a> veya <a href="<?= url('kayit.php') ?>">üye olun</a>.</p><?php endif; ?>
                    <div class="grid-2">
                        <label>Ad Soyad *<input name="name" value="<?= e($buyer['name']) ?>" required autocomplete="name"></label>
                        <label>Telefon *<input name="phone" value="<?= e($buyer['phone']) ?>" required placeholder="05xx xxx xx xx" autocomplete="tel"></label>
                    </div>
                    <label>E-posta *<input type="email" name="email" value="<?= e($buyer['email']) ?>" required autocomplete="email"></label>
                    <div class="grid-2">
                        <label>İl *<select name="city" required><option value="">Seçin</option><?php foreach ($cities as $c): ?><option <?= $buyer['city'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></label>
                        <label>İlçe *<input name="district" value="<?= e($buyer['district']) ?>" required></label>
                    </div>
                    <label>Açık Adres *<textarea name="address" rows="3" required placeholder="Mahalle, cadde/sokak, bina no, daire"><?= e($buyer['address']) ?></textarea></label>
                    <label>Sipariş Notu<input name="note" value="<?= e($buyer['note']) ?>" placeholder="İsteğe bağlı"></label>
                    <button class="btn btn-primary btn-lg">Devam Et →</button>
                </form>
            <?php else: ?>
                <div class="card" style="margin-bottom:18px">
                    <div style="display:flex;justify-content:space-between;gap:12px"><h3 style="margin:0">Teslimat Adresi</h3><a href="<?= url('odeme.php?adim=1') ?>">Değiştir</a></div>
                    <p style="margin:8px 0 0"><strong><?= e($buyer['name']) ?></strong> · <?= e($buyer['phone']) ?> · <?= e($buyer['email']) ?><br><?= e($buyer['address']) ?> <?= e($buyer['district']) ?> / <?= e($buyer['city']) ?></p>
                </div>
                <form method="post" class="card form">
                    <?= csrf_field() ?><input type="hidden" name="step" value="2">
                    <h2>Sözleşmeler</h2>
                    <strong>Ön Bilgilendirme Formu</strong>
                    <div class="contract-box"><?= contract('on-bilgilendirme-formu', $buyer, $s) ?></div>
                    <label class="check"><input type="checkbox" name="accept_info" value="1" required> <span>Ön Bilgilendirme Formu'nu okudum ve kabul ediyorum.</span></label>
                    <strong>Mesafeli Satış Sözleşmesi</strong>
                    <div class="contract-box"><?= contract('mesafeli-satis-sozlesmesi', $buyer, $s) ?></div>
                    <label class="check"><input type="checkbox" name="accept_contract" value="1" required> <span>Mesafeli Satış Sözleşmesi'ni okudum ve kabul ediyorum.</span></label>

                    <?php if ($paytr): ?>
                        <h2 style="margin-top:14px">Ödeme</h2>
                        <div class="secure-note">🔒 Kart bilgilerinizi bir sonraki adımda <strong>PayTR</strong> güvenli ödeme sayfasına gireceksiniz. 3D Secure ile doğrulanır, taksit seçenekleri orada gösterilir. Kart bilgileriniz sitemize iletilmez.</div>
                        <?php if (paytr_test_mode()): ?><p class="alert alert-info small" style="margin:0">PayTR TEST modu açık: gerçek para çekilmez.</p><?php endif; ?>
                        <button class="btn btn-primary btn-lg"><?= money($s['total']) ?> Ödemeye Geç →</button>
                    <?php else: ?>
                        <h2 style="margin-top:14px">Kart Bilgileri</h2>
                        <p class="alert alert-info small" style="margin:0">DEMO ödeme: Gerçek çekim yapılmaz. Deneme için <strong>4242 4242 4242 4242</strong>, ileri bir tarih ve herhangi bir CVV kullanabilirsiniz.</p>
                        <div class="card-visual"><div>GS SPORTİF</div><div class="num" data-card-num>•••• •••• •••• ••••</div><div data-card-name>AD SOYAD</div></div>
                        <label>Kart Üzerindeki İsim<input name="card_name" required autocomplete="cc-name"></label>
                        <label>Kart Numarası<input name="card_number" inputmode="numeric" required autocomplete="cc-number" placeholder="0000 0000 0000 0000"></label>
                        <div class="grid-2">
                            <label>Son Kullanma (AA/YY)<input name="card_exp" inputmode="numeric" required autocomplete="cc-exp" placeholder="12/29"></label>
                            <label>CVV<input name="card_cvv" inputmode="numeric" maxlength="4" required autocomplete="cc-csc" placeholder="000"></label>
                        </div>
                        <div class="secure-note">🔒 Kart bilgileriniz şifreli iletilir ve sitemizde saklanmaz.</div>
                        <button class="btn btn-primary btn-lg"><?= money($s['total']) ?> Öde ve Siparişi Tamamla</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            </div>
            <aside class="card summary">
                <h3>Sipariş Özeti</h3>
                <?php foreach ($s['lines'] as $l): ?>
                    <div class="cart-item" style="margin-bottom:12px"><?= product_media($l['product']) ?><div><strong class="small"><?= e($l['product']['name']) ?></strong><div class="small muted"><?= $l['size'] ? e($l['size']) . ' · ' : '' ?><?= $l['qty'] ?> adet · <?= money($l['total']) ?></div></div></div>
                <?php endforeach; ?>
                <dl>
                    <dt>Ara toplam</dt><dd><?= money($s['subtotal']) ?></dd>
                    <dt>Kargo</dt><dd><?= $s['shipping'] > 0 ? money($s['shipping']) : 'Ücretsiz' ?></dd>
                    <dt class="total">Toplam</dt><dd class="total"><?= money($s['total']) ?></dd>
                </dl>
            </aside>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/footer.php'; ?>
