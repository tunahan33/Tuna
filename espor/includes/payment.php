<?php
/**
 * Ödeme altyapısı: PayTR iFrame API ve Akbank Sanal POS (3D Pay Hosting)
 *
 * Panelde (Site & Ödeme Ayarları > Ödeme) hangisinin aktif olacağı seçilir; müşteri her zaman tek bir
 * "Kart ile Öde" düğmesi görür. Kart bilgileri yalnızca PayTR'ın / Akbank'ın sayfasında girilir,
 * sitemizin sunucusuna hiç gelmez.
 *
 * PayTR : paytr_get_token() ile iframe açılır; sonuç PayTR sunucusundan paytr-bildirim.php'ye imzalı gelir.
 * Akbank: akbank_form_fields() ile müşteri Akbank'ın güvenli ödeme sayfasına yönlendirilir; sonuç
 *         müşterinin tarayıcısıyla akbank-donus.php'ye imzalı (HMAC-SHA512) döner ve orada doğrulanır.
 * Her denemede benzersiz bir sağlayıcı sipariş numarası üretilir ve payment_attempts tablosuna yazılır.
 *
 * Modlar: off (kapalı) · test (sağlayıcının test ortamı, yalnızca panel personeli görür)
 *         · staff (canlı ortam, yalnızca panel personeli görür - gerçek kartla deneme için) · live (herkese açık)
 */

const PAYTR_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';
const PAYTR_IFRAME_URL = 'https://www.paytr.com/odeme/guvenli/';
const AKBANK_URL_LIVE = 'https://virtualpospaymentgateway.akbank.com/payhosting';
const AKBANK_URL_TEST = 'https://virtualpospaymentgatewaypre.akbank.com/payhosting';
const AKBANK_SUCCESS_CODE = 'VPS-0000';

const PAYMENT_PROVIDERS = ['paytr' => 'PayTR', 'akbank' => 'Akbank'];

/** Panelde seçilen aktif ödeme altyapısı */
function payment_provider(): string
{
    $p = setting('payment_provider', 'paytr');
    return isset(PAYMENT_PROVIDERS[$p]) ? $p : 'paytr';
}

function payment_provider_name(?string $p = null): string
{
    return PAYMENT_PROVIDERS[$p ?? payment_provider()];
}

function provider_mode(string $p): string
{
    $m = setting($p . '_mode', 'off');
    return in_array($m, ['off', 'test', 'staff', 'live'], true) ? $m : 'off';
}

function provider_configured(string $p): bool
{
    return $p === 'akbank'
        ? trim(setting('akbank_merchant_safe_id')) !== '' && trim(setting('akbank_terminal_safe_id')) !== '' && setting('akbank_secret_key') !== ''
        : trim(setting('paytr_merchant_id')) !== '' && setting('paytr_merchant_key') !== '' && setting('paytr_merchant_salt') !== '';
}

function paytr_mode(): string
{
    return provider_mode('paytr');
}

function paytr_configured(): bool
{
    return provider_configured('paytr');
}

/** Aktif altyapının modu */
function payment_mode(): string
{
    return provider_mode(payment_provider());
}

/** Kartla ödeme bu ziyaretçiye açık mı? Test ve personel modlarında yalnızca panel personeli görür. */
function payment_provider_active(): bool
{
    $p = payment_provider();
    $m = provider_mode($p);
    return $m !== 'off' && provider_configured($p) && ($m === 'live' || can('panel.access'));
}

/** Herkese açık canlı ödeme var mı (alt bilgi yazısı için) */
function payment_live_public(): bool
{
    return payment_mode() === 'live' && provider_configured(payment_provider());
}

/** [no_installment, max_installment] — yalnızca PayTR'da kullanılır, Akbank tek çekimdir */
function paytr_installment(): array
{
    $v = setting('paytr_installment', '1');
    return $v === '1' ? [1, 0] : [0, in_array($v, ['0', '3', '6', '9', '12'], true) ? (int) $v : 0];
}

function payment_single_shot(): bool
{
    return payment_provider() === 'akbank' || paytr_installment()[0] === 1;
}

/** Sözleşmelerde gösterilen ödeme şekli */
function payment_methods_text(): string
{
    if (payment_provider() === 'akbank') {
        return 'Kredi kartı / banka kartı ile tek çekim (Akbank sanal POS, 3D Secure)';
    }
    return paytr_installment()[0] === 1
        ? 'Kredi kartı / banka kartı ile tek çekim (PayTR güvenli ödeme altyapısı, 3D Secure)'
        : 'Kredi kartı / banka kartı ile tek çekim veya taksitli (PayTR güvenli ödeme altyapısı, 3D Secure; taksit farkı ödeme ekranında gösterilir)';
}

/* ---------------- Akbank Sanal POS (3D Pay Hosting) ---------------- */

function akbank_gateway_url(): string
{
    return provider_mode('akbank') === 'test' ? AKBANK_URL_TEST : AKBANK_URL_LIVE;
}

/** Akbank imzası: base64(HMAC-SHA512(değerler, secretKey)) */
function akbank_hmac(string $data): string
{
    return base64_encode(hash_hmac('sha512', $data, setting('akbank_secret_key'), true));
}

/** 3D Pay Hosting form imzası (alanlar sabit sırada, ayraçsız; kullanılmayanlar boş) */
function akbank_request_hash(array $f): string
{
    $order = ['paymentModel', 'txnCode', 'merchantSafeId', 'terminalSafeId', 'orderId', 'lang', 'amount',
        'ccbRewardAmount', 'pcbRewardAmount', 'xcbRewardAmount', 'currencyCode', 'installCount', 'okUrl', 'failUrl',
        'emailAddress', 'subMerchantId', 'creditCard', 'expiredDate', 'cvv', 'randomNumber', 'requestDateTime', 'b2bIdentityNumber'];
    $str = '';
    foreach ($order as $k) {
        $str .= (string) ($f[$k] ?? '');
    }
    return akbank_hmac($str);
}

/** Banka dönüşündeki imza: hashParams'ta "+" ile sıralanan alanların değerleri birleştirilir */
function akbank_response_hash_valid(array $post): bool
{
    if (empty($post['hash']) || empty($post['hashParams']) || setting('akbank_secret_key') === '') {
        return false;
    }
    $str = '';
    foreach (explode('+', (string) $post['hashParams']) as $k) {
        $str .= (string) ($post[$k] ?? '');
    }
    return hash_equals(akbank_hmac($str), (string) $post['hash']);
}

/**
 * Akbank ödeme sayfasına gönderilecek form alanları. Deneme payment_attempts tablosuna yazılır.
 * Dönüş adresi her iki durumda da akbank-donus.php'dir.
 */
function akbank_form_fields(array $order): array
{
    $oid = $order['order_no'] . 'A' . strtoupper(bin2hex(random_bytes(3)));
    $back = url('akbank-donus.php');
    $f = [
        'paymentModel' => '3D_PAY_HOSTING',
        'txnCode' => '3000',
        'merchantSafeId' => trim(setting('akbank_merchant_safe_id')),
        'terminalSafeId' => trim(setting('akbank_terminal_safe_id')),
        'orderId' => $oid,
        'lang' => 'TR',
        'amount' => number_format((float) $order['amount'], 2, '.', ''),
        'currencyCode' => '949',
        'installCount' => '1',
        'okUrl' => $back,
        'failUrl' => $back,
        'emailAddress' => mb_substr($order['customer_email'], 0, 64),
        'randomNumber' => strtoupper(bin2hex(random_bytes(64))),
        'requestDateTime' => date('Y-m-d\TH:i:s') . '.000',
    ];
    $f['hash'] = akbank_request_hash($f);
    insert('payment_attempts', [
        'order_id' => $order['id'], 'oid' => $oid, 'provider' => 'akbank', 'amount' => (int) round((float) $order['amount'] * 100),
        'status' => 'started', 'test_mode' => provider_mode('akbank') === 'test' ? 1 : 0, 'ip' => client_ip(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    return $f;
}

/* ---------------- PayTR ---------------- */

/** Tutar kuruş cinsinden (PayTR: 100 ile çarpılmış tam sayı) */
function paytr_amount($amount): string
{
    return (string) (int) round((float) $amount * 100);
}

/**
 * PayTR'dan iframe ödeme anahtarı alır. Başarılıysa ['token' => ..., 'oid' => ...], değilse ['error' => ...] döner.
 * Her denemede yeni bir PayTR sipariş numarası (merchant_oid) üretilir ve payment_attempts tablosuna yazılır.
 */
function paytr_get_token(array $order): array
{
    $oid = $order['order_no'] . 'P' . strtoupper(bin2hex(random_bytes(3)));
    $amount = paytr_amount($order['amount']);
    $basket = base64_encode(json_encode([[mb_substr($order['service_title'] . ' - ' . $order['package_name'], 0, 100), number_format((float) $order['amount'], 2, '.', ''), 1]], JSON_UNESCAPED_UNICODE));
    [$noInst, $maxInst] = paytr_installment();
    $test = paytr_mode() === 'test' ? '1' : '0';
    $ip = client_ip();
    $id = trim(setting('paytr_merchant_id'));
    $hashStr = $id . $ip . $oid . $order['customer_email'] . $amount . $basket . $noInst . $maxInst . 'TL' . $test;
    $back = 'odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order);
    $fields = [
        'merchant_id' => $id,
        'user_ip' => $ip,
        'merchant_oid' => $oid,
        'email' => $order['customer_email'],
        'payment_amount' => $amount,
        'paytr_token' => base64_encode(hash_hmac('sha256', $hashStr . setting('paytr_merchant_salt'), setting('paytr_merchant_key'), true)),
        'user_basket' => $basket,
        'debug_on' => $test,
        'no_installment' => $noInst,
        'max_installment' => $maxInst,
        'user_name' => mb_substr($order['invoice_type'] === 'kurumsal' && $order['company_name'] ? $order['company_name'] : $order['customer_name'], 0, 60),
        'user_address' => mb_substr($order['customer_address'] . ($order['customer_city'] ? ' ' . $order['customer_city'] : ''), 0, 400),
        'user_phone' => mb_substr(preg_replace('/[^0-9+]/', '', $order['customer_phone']), 0, 20),
        'merchant_ok_url' => url($back . '&durum=ok'),
        'merchant_fail_url' => url($back . '&durum=hata'),
        'timeout_limit' => '30',
        'currency' => 'TL',
        'test_mode' => $test,
        'lang' => 'tr',
    ];
    $ch = curl_init(PAYTR_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        return ['error' => 'Ödeme sağlayıcısına bağlanılamadı (' . $err . ').'];
    }
    $data = json_decode($res, true);
    if (($data['status'] ?? '') !== 'success' || empty($data['token'])) {
        return ['error' => 'PayTR: ' . mb_substr((string) ($data['reason'] ?? $res), 0, 300)];
    }
    insert('payment_attempts', [
        'order_id' => $order['id'], 'oid' => $oid, 'provider' => 'paytr', 'amount' => (int) $amount, 'status' => 'started',
        'test_mode' => (int) $test, 'ip' => $ip, 'created_at' => now(), 'updated_at' => now(),
    ]);
    return ['token' => $data['token'], 'oid' => $oid];
}

/** PayTR bildirim imzası */
function paytr_callback_hash(array $post): string
{
    return base64_encode(hash_hmac('sha256', ($post['merchant_oid'] ?? '') . setting('paytr_merchant_salt') . ($post['status'] ?? '') . ($post['total_amount'] ?? ''), setting('paytr_merchant_key'), true));
}

/** Ödeme sonucunu siparişe işler (ödeme sağlayıcısı dönüşü, iade çeki ve panelden manuel onay ortak) */
function finalize_order(array $order, bool $success, string $message, ?string $ref, array $raw = []): array
{
    if (!in_array($order['status'], ['pending', 'failed'], true)) {
        return $order; // tekrar işlenmesin
    }
    // Hassas alanları kaydetme
    unset($raw['cardnumber'], $raw['cardcvv2'], $raw['cardexpiredatemonth'], $raw['cardexpiredateyear']);
    $customer = $order['user_id'] ? row('SELECT * FROM users WHERE id = ?', [$order['user_id']]) : null;

    if ($success) {
        q('UPDATE orders SET status = ?, payment_ref = ?, payment_message = ?, payment_raw = ?, paid_at = ?, updated_at = ? WHERE id = ?',
            ['paid', $ref, $message, json_encode($raw, JSON_UNESCAPED_UNICODE), now(), now(), $order['id']]);
        voucher_redeem($order);
        log_activity('Ödeme alındı', $order['order_no'] . ' · ' . $order['package_name'] . ' · ' . money($order['amount']), 'order', (int) $order['id'], $customer);

        send_mail($order['customer_email'], 'Siparişiniz onaylandı - ' . $order['order_no'],
            '<p>Merhaba ' . e($order['customer_name']) . ',</p><p><b>' . e($order['service_title']) . ' - ' . e($order['package_name']) . '</b> siparişiniz için ödemeniz başarıyla alındı.</p>'
            . '<p>Sipariş No: <b>' . e($order['order_no']) . '</b><br>Tutar: <b>' . money($order['amount']) . '</b> (KDV dahil)'
            . (!empty($order['voucher_code']) ? '<br>İade çeki ile ödenen: <b>' . money($order['voucher_amount']) . '</b> (' . e($order['voucher_code']) . ')' : '') . '</p>'
            . '<p>Koçunuz en geç 24 saat içinde sizinle iletişime geçerek ilk dersinizi planlayacaktır.</p>');
        send_mail(setting('notify_email'), 'Yeni satış: ' . $order['order_no'] . ' (' . money($order['amount']) . ')',
            '<p>' . e($order['customer_name']) . ' - ' . e($order['service_title']) . ' / ' . e($order['package_name']) . '</p><p>' . e($order['customer_phone']) . ' · ' . e($order['customer_email']) . '</p>');
    } else {
        q('UPDATE orders SET status = ?, payment_message = ?, payment_raw = ?, updated_at = ? WHERE id = ?',
            ['failed', $message, json_encode($raw, JSON_UNESCAPED_UNICODE), now(), $order['id']]);
        log_activity('Ödeme başarısız', $order['order_no'] . ' · ' . $message, 'order', (int) $order['id'], $customer);
    }
    return row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
}
