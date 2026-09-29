<?php
require_once APP_ROOT . '/app/garanti.php';

const CITIES = ['Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara','Antalya','Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman','Bayburt','Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa','Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne','Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun','Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul','İzmir','Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri','Kilis','Kırıkkale','Kırklareli','Kırşehir','Kocaeli','Konya','Kütahya','Malatya','Manisa','Mardin','Mersin','Muğla','Muş','Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun','Şanlıurfa','Siirt','Sinop','Sivas','Şırnak','Tekirdağ','Tokat','Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak'];

function contract_vars(array $d, bool $bind = false): array
{
    $v = fn(string $key, string $val) => $bind ? '<span class="bind" data-bind="' . $key . '">' . e($val ?: '…') . '</span>' : e($val);
    return [
        '{{alici_ad}}'      => $v('customer_name', $d['invoice_type'] === 'kurumsal' && $d['company_name'] ? $d['company_name'] . ' (' . $d['customer_name'] . ')' : $d['customer_name']),
        '{{alici_adres}}'   => $v('address', trim($d['address'] . ($d['city'] ? ' ' . $d['city'] : ''))),
        '{{alici_telefon}}' => $v('phone', $d['phone']),
        '{{alici_eposta}}'  => $v('email', $d['email']),
        '{{hizmet}}'        => e($d['service_title']),
        '{{paket}}'         => e($d['package_name'] . ' (' . $d['duration'] . ')'),
        '{{tutar}}'         => money((int)$d['amount']),
        '{{tarih}}'         => date('d.m.Y H:i'),
        '{{siparis_no}}'    => e($d['order_no'] ?? 'Sipariş onayında oluşturulacaktır'),
    ];
}

function owns_order(array $o): bool
{
    $u = current_user();
    return in_array($o['order_no'], $_SESSION['orders'] ?? [], true)
        || ($u && (int)$o['user_id'] === (int)$u['id'])
        || ($u && can('orders.view', $u));
}

function page_checkout(string $id): void
{
    $p = row('SELECT p.*, s.title AS service_title, s.slug AS service_slug, s.icon FROM packages p JOIN services s ON s.id = p.service_id
              WHERE p.id = ? AND p.is_active = 1 AND s.is_active = 1', [(int)$id]);
    if (!$p) not_found();
    $u = current_user();
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $d = [
            'customer_name' => post('customer_name'), 'email' => post('email'), 'phone' => post('phone'),
            'identity_no' => preg_replace('/\D/', '', post('identity_no')), 'invoice_type' => post('invoice_type') === 'kurumsal' ? 'kurumsal' : 'bireysel',
            'company_name' => post('company_name'), 'tax_office' => post('tax_office'), 'tax_no' => preg_replace('/\D/', '', post('tax_no')),
            'address' => post('address'), 'city' => post('city'), 'note' => mb_substr(post('note'), 0, 1000),
        ];
        if (mb_strlen($d['customer_name']) < 5 || !str_contains($d['customer_name'], ' ')) $errors[] = 'Lütfen adınızı ve soyadınızı eksiksiz yazın.';
        if (!valid_email($d['email'])) $errors[] = 'Geçerli bir e-posta adresi girin.';
        if (strlen(preg_replace('/\D/', '', $d['phone'])) < 10) $errors[] = 'Geçerli bir telefon numarası girin.';
        if ($d['identity_no'] !== '' && strlen($d['identity_no']) !== 11) $errors[] = 'T.C. kimlik numarası 11 haneli olmalıdır.';
        if ($d['invoice_type'] === 'kurumsal' && ($d['company_name'] === '' || $d['tax_office'] === '' || strlen($d['tax_no']) < 10))
            $errors[] = 'Kurumsal fatura için firma unvanı, vergi dairesi ve vergi numarası zorunludur.';
        if (mb_strlen($d['address']) < 10) $errors[] = 'Fatura adresinizi eksiksiz girin.';
        if (!in_array($d['city'], CITIES, true)) $errors[] = 'Lütfen il seçin.';
        if (empty($_POST['accept_contracts'])) $errors[] = 'Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi\'ni onaylamanız gerekir.';
        if (empty($_POST['accept_kvkk'])) $errors[] = 'Genel Aydınlatma Metni\'ni okuduğunuzu onaylamanız gerekir.';

        if (!$errors) {
            do { $orderNo = 'GS' . date('ymd') . random_int(10000, 99999); } while (val('SELECT 1 FROM orders WHERE order_no = ?', [$orderNo]));
            $vars = contract_vars($d + ['service_title' => $p['service_title'], 'package_name' => $p['name'], 'duration' => $p['duration'], 'amount' => $p['price'], 'order_no' => $orderNo]);
            $contract = '<h2>Ön Bilgilendirme Formu</h2>' . fill_placeholders(page('on-bilgilendirme-formu')['content'] ?? '', $vars)
                . '<hr><h2>Mesafeli Satış Sözleşmesi</h2>' . fill_placeholders(page('mesafeli-satis-sozlesmesi')['content'] ?? '', $vars)
                . '<p class="small muted">Elektronik onay: ' . date('d.m.Y H:i:s') . ' — IP: ' . e(client_ip()) . '</p>';
            q('INSERT INTO orders(order_no, user_id, package_id, package_name, service_title, amount, customer_name, email, phone, identity_no, invoice_type,
                company_name, tax_office, tax_no, address, city, note, status, contract_html, ip, created_at, updated_at)
               VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $orderNo, $u['id'] ?? null, $p['id'], $p['name'], $p['service_title'], $p['price'], $d['customer_name'], $d['email'], $d['phone'],
                $d['identity_no'], $d['invoice_type'], $d['company_name'], $d['tax_office'], $d['tax_no'], $d['address'], $d['city'], $d['note'],
                'pending', $contract, client_ip(), now(), now(),
            ]);
            $_SESSION['orders'][] = $orderNo;
            log_activity('order_created', $orderNo . ' — ' . $p['service_title'] . ' / ' . $p['name'] . ' — ' . money((int)$p['price']), $u, $u['name'] ?? $d['customer_name']);
            redirect('odeme/banka/' . $orderNo);
        }
    }

    $prefill = [
        'customer_name' => $_POST['customer_name'] ?? ($u['name'] ?? ''), 'email' => $_POST['email'] ?? ($u['email'] ?? ''),
        'phone' => $_POST['phone'] ?? ($u['phone'] ?? ''), 'address' => $_POST['address'] ?? '', 'city' => $_POST['city'] ?? '',
        'invoice_type' => $_POST['invoice_type'] ?? 'bireysel', 'company_name' => $_POST['company_name'] ?? '',
    ];
    $bindVars = contract_vars($prefill + ['service_title' => $p['service_title'], 'package_name' => $p['name'], 'duration' => $p['duration'], 'amount' => $p['price']], true);

    render('Ödeme — ' . $p['name'], function () use ($p, $errors, $prefill, $bindVars, $u) { ?>
<section class="page-hero slim"><div class="container">
  <div class="checkout-steps"><span class="on">1. Bilgiler</span><span>2. Güvenli ödeme</span><span>3. Onay</span></div>
</div></section>
<section class="section"><div class="container">
  <?php if (!$u): ?><div class="alert alert-info">Üye misiniz? <a href="<?= url('giris') ?>">Giriş yapın</a>, siparişleriniz hesabınızda listelensin. Üye olmadan da satın alabilirsiniz.</div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="checkout" id="checkoutForm" novalidate>
    <?= csrf_field() ?>
    <div class="card">
      <h2>Fatura ve iletişim bilgileri</h2>
      <div class="grid-2">
        <label>Ad Soyad *<input name="customer_name" required value="<?= e($prefill['customer_name']) ?>" data-bind-src="customer_name" autocomplete="name"></label>
        <label>E-posta *<input type="email" name="email" required value="<?= e($prefill['email']) ?>" data-bind-src="email" autocomplete="email"></label>
        <label>Cep telefonu *<input type="tel" name="phone" required placeholder="05xx xxx xx xx" value="<?= e($prefill['phone']) ?>" data-bind-src="phone" autocomplete="tel"></label>
        <label>T.C. Kimlik No <small>(e-Arşiv fatura için)</small><input name="identity_no" inputmode="numeric" maxlength="11" value="<?= old('identity_no') ?>"></label>
      </div>
      <div class="radio-row">
        <label class="radio"><input type="radio" name="invoice_type" value="bireysel" <?= $prefill['invoice_type'] !== 'kurumsal' ? 'checked' : '' ?>> Bireysel fatura</label>
        <label class="radio"><input type="radio" name="invoice_type" value="kurumsal" <?= $prefill['invoice_type'] === 'kurumsal' ? 'checked' : '' ?>> Kurumsal fatura</label>
      </div>
      <div class="grid-3 corporate" <?= $prefill['invoice_type'] === 'kurumsal' ? '' : 'hidden' ?>>
        <label>Firma unvanı *<input name="company_name" value="<?= e($prefill['company_name']) ?>"></label>
        <label>Vergi dairesi *<input name="tax_office" value="<?= old('tax_office') ?>"></label>
        <label>Vergi no *<input name="tax_no" inputmode="numeric" maxlength="11" value="<?= old('tax_no') ?>"></label>
      </div>
      <div class="grid-2">
        <label class="span-2">Fatura adresi *<textarea name="address" rows="2" required data-bind-src="address" autocomplete="street-address"><?= e($prefill['address']) ?></textarea></label>
        <label>İl *<select name="city" required data-bind-src="city">
          <option value="">Seçiniz</option>
          <?php foreach (CITIES as $c): ?><option <?= $prefill['city'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select></label>
        <label>Sipariş notu <small>(isteğe bağlı)</small><input name="note" value="<?= old('note') ?>" placeholder="Branşınız, uygun görüşme saatleri vb."></label>
      </div>
      <div class="agreements">
        <label class="check"><input type="checkbox" name="accept_contracts" value="1" required <?= !empty($_POST['accept_contracts']) ? 'checked' : '' ?>>
          <span><a href="#" data-modal="doc-onbilgi">Ön Bilgilendirme Formu</a>'nu ve <a href="#" data-modal="doc-mesafeli">Mesafeli Satış Sözleşmesi</a>'ni okudum, onaylıyorum.</span></label>
        <label class="check"><input type="checkbox" name="accept_kvkk" value="1" required <?= !empty($_POST['accept_kvkk']) ? 'checked' : '' ?>>
          <span><a href="<?= url('aydinlatma-metni') ?>" target="_blank">Genel Aydınlatma Metni</a>'ni okudum; kişisel verilerimin hizmetin ifası amacıyla işlenmesini kabul ediyorum.</span></label>
      </div>
    </div>
    <aside class="card summary">
      <h3>Sipariş özeti</h3>
      <div class="sum-item">
        <span class="sc-icon sm"><?= icon($p['icon']) ?></span>
        <div><strong><?= e($p['service_title']) ?></strong><span><?= e($p['name']) ?> paketi · <?= e($p['duration']) ?></span></div>
      </div>
      <dl class="sum-lines">
        <div><dt>Paket bedeli</dt><dd><?= money((int)round($p['price'] / 1.2)) ?></dd></div>
        <div><dt>KDV (%20)</dt><dd><?= money((int)$p['price'] - (int)round($p['price'] / 1.2)) ?></dd></div>
        <div class="total"><dt>Toplam</dt><dd><?= money((int)$p['price']) ?></dd></div>
      </dl>
      <button class="btn btn-primary btn-block btn-lg" type="submit"><?= icon('lock') ?> Güvenli ödemeye geç</button>
      <p class="small muted center">Kart bilgileriniz Garanti BBVA 3D Secure sayfasında alınır, sitemizde saklanmaz.</p>
      <?= payment_logos() ?>
    </aside>
  </form>
</div></section>

<dialog class="modal modal-lg" id="doc-onbilgi"><div class="modal-head"><h3>Ön Bilgilendirme Formu</h3><button class="modal-close" data-close aria-label="Kapat"><?= icon('x') ?></button></div>
  <div class="modal-body prose"><?= fill_placeholders(page('on-bilgilendirme-formu')['content'] ?? '', $bindVars) ?></div>
  <div class="modal-foot"><button class="btn btn-primary" data-close>Okudum</button></div></dialog>
<dialog class="modal modal-lg" id="doc-mesafeli"><div class="modal-head"><h3>Mesafeli Satış Sözleşmesi</h3><button class="modal-close" data-close aria-label="Kapat"><?= icon('x') ?></button></div>
  <div class="modal-body prose"><?= fill_placeholders(page('mesafeli-satis-sozlesmesi')['content'] ?? '', $bindVars) ?></div>
  <div class="modal-foot"><button class="btn btn-primary" data-close>Okudum</button></div></dialog>
<?php
    });
}

function page_pay(string $orderNo): void
{
    $o = row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]);
    if (!$o || !owns_order($o)) not_found();
    if (!in_array($o['status'], ['pending', 'failed'], true)) redirect('odeme/tamamlandi/' . $o['order_no']);

    $mode = pos_mode();
    if ($mode !== 'demo' && !garanti_configured()) {
        render('Ödeme', function () {
            echo '<section class="section"><div class="container narrow"><div class="alert alert-error">Ödeme sistemi şu anda yapılandırılıyor. Lütfen kısa süre sonra tekrar deneyin ya da bizimle iletişime geçin.</div></div></section>';
        });
        return;
    }
    // Banka aynı sipariş numarasıyla ikinci denemeyi reddedebilir: başarısız denemeden sonra yeni numara ver
    if ($o['status'] === 'failed' && $mode !== 'demo') {
        do { $newNo = 'GS' . date('ymd') . random_int(10000, 99999); } while (val('SELECT 1 FROM orders WHERE order_no = ?', [$newNo]));
        q("UPDATE orders SET order_no = ?, status = 'pending', updated_at = ? WHERE id = ?", [$newNo, now(), $o['id']]);
        $_SESSION['orders'][] = $newNo;
        log_activity('order_status', $o['order_no'] . ' → ' . $newNo . ' (ödeme tekrar deneniyor)', null, $o['customer_name']);
        $o = row('SELECT * FROM orders WHERE id = ?', [$o['id']]);
        $o['status'] = 'failed'; // uyarıyı göstermek için
    }
    $req = $mode === 'demo' ? null : garanti_request($o);

    render('Güvenli Ödeme', function () use ($o, $mode, $req) { ?>
<section class="page-hero slim"><div class="container">
  <div class="checkout-steps"><span class="done">1. Bilgiler</span><span class="on">2. Güvenli ödeme</span><span>3. Onay</span></div>
</div></section>
<section class="section"><div class="container narrow">
  <?php if ($o['status'] === 'failed'): ?><div class="alert alert-error">Önceki ödeme denemesi başarısız oldu<?= $o['payment_message'] ? ': ' . e($o['payment_message']) : '' ?>. Tekrar deneyebilirsiniz.</div><?php endif; ?>
  <div class="card pay-card">
    <div class="pay-head">
      <div><span class="muted small">Sipariş No</span><strong><?= e($o['order_no']) ?></strong></div>
      <div><span class="muted small"><?= e($o['service_title']) ?> — <?= e($o['package_name']) ?></span><strong class="amount"><?= money((int)$o['amount']) ?></strong></div>
    </div>

    <?php if ($mode === 'demo'): ?>
      <div class="alert alert-warn"><strong>Demo modu:</strong> Sanal POS bilgileri henüz girilmedi. Bu ekran ödeme akışını test etmek içindir, karttan çekim yapılmaz. Banka bilgileri Süper Admin &gt; Ayarlar'dan girildiğinde gerçek Garanti BBVA 3D ödeme sayfası devreye girer.</div>
      <form method="post" action="<?= url('odeme/demo') ?>" class="demo-pay">
        <?= csrf_field() ?><input type="hidden" name="order_no" value="<?= e($o['order_no']) ?>">
        <button class="btn btn-primary btn-lg" name="result" value="ok"><?= icon('check') ?> Ödemeyi onayla (demo)</button>
        <button class="btn btn-ghost btn-lg" name="result" value="fail">Başarısız ödeme dene</button>
      </form>
    <?php elseif ($req['level'] === '3D_PAY'): ?>
      <form method="post" action="<?= e($req['action']) ?>" class="card-form" autocomplete="on">
        <?php foreach ($req['fields'] as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
        <label>Kart üzerindeki isim<input name="cardholdername" required autocomplete="cc-name"></label>
        <label>Kart numarası<input name="cardnumber" required inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="0000 0000 0000 0000" data-card></label>
        <div class="grid-3">
          <label>Ay<select name="cardexpiredatemonth" required autocomplete="cc-exp-month"><?php for ($i = 1; $i <= 12; $i++): ?><option><?= sprintf('%02d', $i) ?></option><?php endfor; ?></select></label>
          <label>Yıl<select name="cardexpiredateyear" required autocomplete="cc-exp-year"><?php for ($y = (int)date('y'); $y <= (int)date('y') + 12; $y++): ?><option value="<?= sprintf('%02d', $y) ?>">20<?= sprintf('%02d', $y) ?></option><?php endfor; ?></select></label>
          <label>CVV<input name="cardcvv2" required inputmode="numeric" maxlength="4" autocomplete="cc-csc"></label>
        </div>
        <button class="btn btn-primary btn-lg btn-block"><?= icon('lock') ?> <?= money((int)$o['amount']) ?> öde</button>
        <p class="small muted center">Kart bilgileriniz doğrudan Garanti BBVA'ya iletilir, sunucularımızda saklanmaz.</p>
      </form>
    <?php else: ?>
      <form method="post" action="<?= e($req['action']) ?>" id="bankForm">
        <?php foreach ($req['fields'] as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
        <p>Kart bilgilerinizi güvenle girebileceğiniz <strong>Garanti BBVA 3D Secure ödeme sayfasına</strong> yönlendiriliyorsunuz.</p>
        <button class="btn btn-primary btn-lg btn-block"><?= icon('lock') ?> Garanti BBVA ile öde</button>
      </form>
    <?php endif; ?>
    <?= payment_logos() ?>
  </div>
</div></section>
<?php
    });
}

/** Garanti BBVA başarılı/başarısız dönüş adresi */
function page_callback(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('');
    $orderNo = (string)($_POST['orderid'] ?? $_POST['oid'] ?? '');
    $o = row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]);
    if (!$o) { http_response_code(400); exit('Geçersiz sipariş.'); }
    if (pos_mode() !== 'demo') {
        $res = garanti_verify($_POST, $o);
        finalize_order($o, $res['ok'], $res['message'], $res['ref']);
    }
    redirect('odeme/tamamlandi/' . $o['order_no']);
}

function page_demo_pay(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || pos_mode() !== 'demo') not_found();
    csrf_check();
    $o = row('SELECT * FROM orders WHERE order_no = ?', [post('order_no')]);
    if (!$o || !owns_order($o)) not_found();
    $ok = post('result') === 'ok';
    finalize_order($o, $ok, $ok ? 'Onaylandı (demo)' : 'Kart limiti yetersiz (demo)', $ok ? 'DEMO-' . random_int(100000, 999999) : '');
    redirect('odeme/tamamlandi/' . $o['order_no']);
}

function page_result(string $orderNo): void
{
    $o = row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]);
    if (!$o || !owns_order($o)) not_found();
    $paid = in_array($o['status'], REVENUE_STATUSES, true);
    render($paid ? 'Siparişiniz alındı' : 'Ödeme tamamlanamadı', function () use ($o, $paid) { ?>
<section class="page-hero slim"><div class="container">
  <div class="checkout-steps"><span class="done">1. Bilgiler</span><span class="done">2. Güvenli ödeme</span><span class="on">3. Onay</span></div>
</div></section>
<section class="section"><div class="container narrow">
  <div class="card result <?= $paid ? 'ok' : 'fail' ?>">
    <div class="result-icon"><?= icon($paid ? 'check' : 'x') ?></div>
    <?php if ($paid): ?>
      <h1>Teşekkürler, siparişiniz alındı!</h1>
      <p>Ödemeniz başarıyla tamamlandı. Sipariş onayınız <strong><?= e($o['email']) ?></strong> adresine gönderilecek. Danışmanınız en geç <strong>1 iş günü</strong> içinde sizinle iletişime geçecek.</p>
    <?php else: ?>
      <h1>Ödeme tamamlanamadı</h1>
      <p><?= e($o['payment_message'] ?: 'Ödeme işlemi banka tarafından onaylanmadı.') ?> Kartınızdan herhangi bir tutar tahsil edilmedi.</p>
    <?php endif; ?>
    <dl class="sum-lines">
      <div><dt>Sipariş No</dt><dd><strong><?= e($o['order_no']) ?></strong></dd></div>
      <div><dt>Hizmet</dt><dd><?= e($o['service_title']) ?> — <?= e($o['package_name']) ?></dd></div>
      <div><dt>Tutar</dt><dd><?= money((int)$o['amount']) ?></dd></div>
      <div><dt>Durum</dt><dd><?= status_badge($o['status']) ?></dd></div>
    </dl>
    <div class="result-actions">
      <?php if ($paid): ?>
        <a class="btn btn-primary" href="<?= url('siparis-takibi') ?>?no=<?= e($o['order_no']) ?>&amp;email=<?= e(urlencode($o['email'])) ?>">Siparişi takip et</a>
        <a class="btn btn-ghost" href="<?= url() ?>">Ana sayfa</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= url('odeme/banka/' . $o['order_no']) ?>">Tekrar dene</a>
        <a class="btn btn-ghost" href="<?= url('iletisim') ?>">Destek al</a>
      <?php endif; ?>
    </div>
  </div>
</div></section>
<?php
    });
}
