<?php
/**
 * PayTR iFrame API entegrasyonu
 *
 * Akış:
 *  1) Müşteri sözleşmeleri onaylar → sipariş "Ödeme Bekleniyor" durumunda oluşturulur (stok henüz düşmez)
 *  2) paytr_get_token() ile PayTR'den token alınır, odeme-paytr.php sayfasında PayTR'nin güvenli ödeme formu (iframe) açılır.
 *     Kart bilgileri sitemize hiç gelmez; doğrudan PayTR'ye girilir.
 *  3) Ödeme sonucunu PayTR, sunucudan sunucuya paytr-bildirim.php adresine bildirir. İmza (hash) doğrulanır,
 *     başarılıysa sipariş "Yeni Sipariş" olur ve stok düşer. Müşterinin yönlendirildiği sayfa sonucu belirlemez.
 *
 * Gerekli bilgiler (PayTR Mağaza Paneli): Mağaza No (merchant_id), Mağaza Parola (merchant_key), Mağaza Gizli Anahtar (merchant_salt).
 * Panel > Mağaza Ayarları > Ödeme bölümünden girilir.
 */

const PAYTR_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';
const PAYTR_IFRAME_URL = 'https://www.paytr.com/odeme/guvenli/';

/** Ödeme PayTR ile mi alınıyor? (Ayarlarda seçili ve bilgiler eksiksizse) */
function paytr_enabled(): bool
{
    return setting('payment_mode') === 'paytr'
        && setting('paytr_merchant_id') !== '' && setting('paytr_merchant_key') !== '' && setting('paytr_merchant_salt') !== '';
}

function paytr_test_mode(): bool
{
    return setting('paytr_test_mode', '1') === '1';
}

/** Site adresi (PayTR'nin müşteriyi geri yönlendireceği tam adres) */
function site_base_url(): string
{
    $https = request_is_https();
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(setting('site_url'), PHP_URL_HOST);
    return ($https ? 'https://' : 'http://') . $host . base_path();
}

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/**
 * PayTR'den ödeme formu için token alır.
 * @return array{0: ?string, 1: ?string} [token, hata mesajı]
 */
function paytr_get_token(array $order): array
{
    $merchantId = setting('paytr_merchant_id');
    $key = setting('paytr_merchant_key');
    $salt = setting('paytr_merchant_salt');

    $basket = [];
    foreach (rows('SELECT name, size, price, qty FROM order_items WHERE order_id = ?', [$order['id']]) as $i) {
        $basket[] = [mb_substr($i['name'] . ($i['size'] ? ' (' . $i['size'] . ')' : ''), 0, 100), number_format((float) $i['price'], 2, '.', ''), (int) $i['qty']];
    }
    if ((float) $order['shipping'] > 0) {
        $basket[] = ['Kargo', number_format((float) $order['shipping'], 2, '.', ''), 1];
    }
    $userBasket = base64_encode(json_encode($basket, JSON_UNESCAPED_UNICODE));

    $params = [
        'merchant_id'       => $merchantId,
        'user_ip'           => client_ip(),
        'merchant_oid'      => $order['order_no'],
        'email'             => $order['email'],
        'payment_amount'    => (string) (int) round((float) $order['total'] * 100), // kuruş cinsinden
        'currency'          => 'TL',
        'test_mode'         => paytr_test_mode() ? '1' : '0',
        'no_installment'    => setting('paytr_no_installment', '0') === '1' ? '1' : '0',
        'max_installment'   => (string) max(0, min(12, (int) setting('paytr_max_installment', '0'))),
        'user_basket'       => $userBasket,
    ];
    $hashStr = $params['merchant_id'] . $params['user_ip'] . $params['merchant_oid'] . $params['email'] . $params['payment_amount']
        . $params['user_basket'] . $params['no_installment'] . $params['max_installment'] . $params['currency'] . $params['test_mode'];
    $params['paytr_token'] = base64_encode(hash_hmac('sha256', $hashStr . $salt, $key, true));
    $params += [
        'user_name'         => mb_substr($order['customer_name'], 0, 60),
        'user_address'      => mb_substr($order['address'] . ' ' . $order['district'] . '/' . $order['city'], 0, 400),
        'user_phone'        => mb_substr(preg_replace('/[^0-9+]/', '', $order['phone']), 0, 20),
        'merchant_ok_url'   => site_base_url() . '/siparis-tamam.php?no=' . rawurlencode($order['order_no']),
        'merchant_fail_url' => site_base_url() . '/siparis-tamam.php?no=' . rawurlencode($order['order_no']),
        'debug_on'          => paytr_test_mode() ? '1' : '0',
        'timeout_limit'     => '30',
        'lang'              => 'tr',
    ];

    $url = getenv('PAYTR_TOKEN_URL') ?: PAYTR_TOKEN_URL; // testlerde sahte sunucuya yönlendirmek için
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FRESH_CONNECT => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
    } else { // curl eklentisi yoksa PHP'nin kendi bağlantısı
        $raw = @file_get_contents($url, false, stream_context_create(['http' => [
            'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query($params), 'timeout' => 20,
        ]]));
        $err = $raw === false ? 'bağlantı kurulamadı' : '';
    }
    if ($raw === false) {
        return [null, 'PayTR bağlantı hatası: ' . $err];
    }
    $res = json_decode($raw, true);
    if (($res['status'] ?? '') === 'success' && !empty($res['token'])) {
        return [$res['token'], null];
    }
    return [null, 'PayTR: ' . ($res['reason'] ?? 'beklenmeyen yanıt')];
}

/** PayTR bildirimindeki imzayı doğrular */
function paytr_verify_callback(array $post): bool
{
    $expected = base64_encode(hash_hmac('sha256',
        ($post['merchant_oid'] ?? '') . setting('paytr_merchant_salt') . ($post['status'] ?? '') . ($post['total_amount'] ?? ''),
        setting('paytr_merchant_key'), true));
    return setting('paytr_merchant_key') !== '' && hash_equals($expected, (string) ($post['hash'] ?? ''));
}

/**
 * Ödemesi onaylanan siparişi işler: stok düşer, durum "Yeni Sipariş" olur. Aynı bildirim iki kez gelirse tekrar işlemez.
 */
function order_mark_paid(array $order, string $who, string $note): void
{
    if (in_array($order['status'], SALE_STATUSES, true) || in_array($order['status'], ['iptal', 'iade'], true)) {
        return;
    }
    $pdo = db();
    $pdo->beginTransaction();
    $short = [];
    foreach (rows('SELECT product_id, name, qty FROM order_items WHERE order_id = ?', [$order['id']]) as $i) {
        $ok = q('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$i['qty'], $i['product_id'], $i['qty']])->rowCount();
        if (!$ok) {
            q('UPDATE products SET stock = 0 WHERE id = ?', [$i['product_id']]);
            $short[] = $i['name'];
        }
    }
    update('orders', ['status' => 'yeni', 'updated_at' => now()], (int) $order['id']);
    order_history((int) $order['id'], $note, $who);
    if ($short) {
        order_history((int) $order['id'], 'DİKKAT: Ödeme alındı ancak stok yetersiz: ' . implode(', ', $short), 'Sistem');
    }
    $pdo->commit();
    log_activity('Sipariş verdi', $order['order_no'] . ' · ' . money($order['total']) . ' · ' . $note, 'admin/siparis.php?id=' . $order['id'],
        ['id' => $order['user_id'], 'name' => $order['customer_name'], 'role' => $order['user_id'] ? 'uye' : 'ziyaretci']);
}
