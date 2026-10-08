<?php
/**
 * Ödeme altyapısı: PayTR iFrame API
 *
 * Akış:
 *   1. odeme.php sözleşme onayından sonra paytr_get_token() ile PayTR'dan ödeme anahtarı (token) alır
 *      ve PayTR'ın güvenli ödeme formunu sayfada iframe içinde gösterir. Kart bilgileri yalnızca
 *      PayTR'a girilir; sitemizin sunucusuna hiç gelmez.
 *   2. Ödeme sonucu PayTR sunucusundan paytr-bildirim.php adresine (PayTR panelinde "Bildirim URL")
 *      imzalı olarak gelir; imza ve tutar doğrulanıp finalize_order() ile siparişe işlenir.
 *   3. Müşteri ödeme sonrası odeme-sonuc.php'ye döner (bu dönüş ödemeyi onaylamaz, yalnızca bilgi verir).
 *
 * Modlar: off (kapalı) · test (PayTR test modu, yalnızca panel personeline görünür) · live (canlı).
 */

const PAYTR_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';
const PAYTR_IFRAME_URL = 'https://www.paytr.com/odeme/guvenli/';

function paytr_mode(): string
{
    $m = setting('paytr_mode', 'off');
    return in_array($m, ['off', 'test', 'live'], true) ? $m : 'off';
}

function paytr_configured(): bool
{
    return trim(setting('paytr_merchant_id')) !== '' && setting('paytr_merchant_key') !== '' && setting('paytr_merchant_salt') !== '';
}

/** Kartla ödeme bu ziyaretçiye açık mı? Test modunda yalnızca panel personeli görür. */
function payment_provider_active(): bool
{
    $m = paytr_mode();
    return $m !== 'off' && paytr_configured() && ($m === 'live' || can('panel.access'));
}

/** [no_installment, max_installment] */
function paytr_installment(): array
{
    $v = setting('paytr_installment', '1');
    return $v === '1' ? [1, 0] : [0, in_array($v, ['0', '3', '6', '9', '12'], true) ? (int) $v : 0];
}

/** Sözleşmelerde gösterilen ödeme şekli */
function payment_methods_text(): string
{
    return paytr_installment()[0] === 1
        ? 'Kredi kartı / banka kartı ile tek çekim (PayTR güvenli ödeme altyapısı, 3D Secure)'
        : 'Kredi kartı / banka kartı ile tek çekim veya taksitli (PayTR güvenli ödeme altyapısı, 3D Secure; taksit farkı ödeme ekranında gösterilir)';
}

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
        'order_id' => $order['id'], 'oid' => $oid, 'amount' => (int) $amount, 'status' => 'started',
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
