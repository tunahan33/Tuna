<?php
/**
 * Garanti BBVA Sanal POS (GVPS) 3D entegrasyonu
 *
 * Desteklenen güvenlik seviyeleri:
 *  - 3D_OOS_PAY : Kart bilgileri bankanın ortak ödeme sayfasında girilir (önerilen, en kolay onay)
 *  - 3D_PAY     : Kart bilgileri sitemizdeki formdan DOĞRUDAN bankaya gönderilir (sunucumuza hiç gelmez)
 *
 * Çalışma modları (Yönetim > Site Ayarları > Ödeme):
 *  - demo : Banka bağlantısı olmadan ödeme akışını test etmek için simülasyon
 *  - test : Garanti test ortamı (bankanın verdiği test terminal bilgileriyle)
 *  - prod : Canlı ortam
 *
 * Hash yöntemi: Garanti GVPS "apiversion 512" (SHA-512)
 */

const GARANTI_URL_TEST = 'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine';
const GARANTI_URL_PROD = 'https://sanalposprov.garanti.com.tr/servlet/gt3dengine';

function pos_mode(): string
{
    $m = setting('pos_mode', 'demo');
    return in_array($m, ['demo', 'test', 'prod'], true) ? $m : 'demo';
}

function garanti_endpoint(): string
{
    return pos_mode() === 'prod' ? GARANTI_URL_PROD : GARANTI_URL_TEST;
}

function garanti_security_level(): string
{
    return setting('garanti_security_level') === '3D_PAY' ? '3D_PAY' : '3D_OOS_PAY';
}

function garanti_amount(array $order): string
{
    return (string) (int) round(((float) $order['amount']) * 100); // kuruş cinsinden, ayraçsız
}

function garanti_security_data(): string
{
    $terminalId = str_pad(setting('garanti_terminal_id'), 9, '0', STR_PAD_LEFT);
    return strtoupper(sha1(setting('garanti_prov_password') . $terminalId));
}

/** Bankaya gönderilecek form alanları */
function garanti_form_fields(array $order): array
{
    $terminalId = setting('garanti_terminal_id');
    $amount = garanti_amount($order);
    $currency = '949'; // TL
    $type = 'sales';
    $installment = '';
    $successUrl = url('odeme-sonuc.php');
    $errorUrl = url('odeme-sonuc.php');
    $storeKey = setting('garanti_store_key');

    $hash = strtoupper(hash('sha512',
        $terminalId . $order['order_no'] . $amount . $currency . $successUrl . $errorUrl . $type . $installment . $storeKey . garanti_security_data()
    ));

    return [
        'mode'                  => pos_mode() === 'prod' ? 'PROD' : 'TEST',
        'apiversion'            => '512',
        'secure3dsecuritylevel' => garanti_security_level(),
        'terminalprovuserid'    => setting('garanti_prov_user', 'PROVAUT'),
        'terminaluserid'        => setting('garanti_prov_user', 'PROVAUT'),
        'terminalmerchantid'    => setting('garanti_merchant_id'),
        'terminalid'            => $terminalId,
        'orderid'               => $order['order_no'],
        'successurl'            => $successUrl,
        'errorurl'              => $errorUrl,
        'customeremailaddress'  => $order['customer_email'],
        'customeripaddress'     => $order['ip'] ?: client_ip(),
        'companyname'           => setting('site_name', 'GS Projeler'),
        'lang'                  => 'tr',
        'txntype'               => $type,
        'txnamount'             => $amount,
        'txncurrencycode'       => $currency,
        'txninstallmentcount'   => $installment,
        'txntimestamp'          => gmdate('Y-m-d\TH:i:s\Z'),
        'refreshtime'           => '5',
        'secure3dhash'          => $hash,
    ];
}

/** Bankadan dönen yanıtın imzasını doğrular */
function garanti_verify_response(array $post): bool
{
    $p = array_change_key_case($post, CASE_LOWER);
    $hashParams = $p['hashparams'] ?? '';
    $hash = $p['hash'] ?? '';
    if ($hashParams === '' || $hash === '') {
        return false;
    }
    $str = '';
    foreach (explode(':', $hashParams) as $name) {
        if ($name !== '') {
            $str .= $p[strtolower($name)] ?? '';
        }
    }
    $str .= setting('garanti_store_key');

    $sha512 = strtoupper(hash('sha512', $str));
    $sha1 = base64_encode(sha1($str, true));
    return hash_equals($sha512, strtoupper($hash)) || hash_equals($sha1, $hash);
}

/** Yanıt başarılı ödeme mi? */
function garanti_is_success(array $post): bool
{
    $p = array_change_key_case($post, CASE_LOWER);
    $md = (string) ($p['mdstatus'] ?? '');
    return in_array($md, ['1', '2', '3', '4'], true) && ($p['procreturncode'] ?? '') === '00';
}

function garanti_error_message(array $post): string
{
    $p = array_change_key_case($post, CASE_LOWER);
    $msg = $p['errmsg'] ?? $p['mderrormessage'] ?? $p['hostmsg'] ?? '';
    $md = (string) ($p['mdstatus'] ?? '');
    if ($msg === '' && $md !== '' && !in_array($md, ['1', '2', '3', '4'], true)) {
        $msg = '3D Secure doğrulaması başarısız (mdstatus: ' . $md . ')';
    }
    return mb_substr($msg ?: 'Ödeme banka tarafından onaylanmadı.', 0, 500);
}

/** Ödeme sonucunu siparişe işler (banka ve demo modu ortak) */
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
        log_activity('Ödeme alındı', $order['order_no'] . ' · ' . $order['package_name'] . ' · ' . money($order['amount']), 'order', (int) $order['id'], $customer);

        send_mail($order['customer_email'], 'Siparişiniz onaylandı - ' . $order['order_no'],
            '<p>Merhaba ' . e($order['customer_name']) . ',</p><p><b>' . e($order['service_title']) . ' - ' . e($order['package_name']) . '</b> siparişiniz için ödemeniz başarıyla alındı.</p>'
            . '<p>Sipariş No: <b>' . e($order['order_no']) . '</b><br>Tutar: <b>' . money($order['amount']) . '</b> (KDV dahil)</p>'
            . '<p>Danışmanınız en geç 3 iş günü içinde sizinle iletişime geçecektir.</p>');
        send_mail(setting('notify_email'), 'Yeni satış: ' . $order['order_no'] . ' (' . money($order['amount']) . ')',
            '<p>' . e($order['customer_name']) . ' - ' . e($order['service_title']) . ' / ' . e($order['package_name']) . '</p><p>' . e($order['customer_phone']) . ' · ' . e($order['customer_email']) . '</p>');
    } else {
        q('UPDATE orders SET status = ?, payment_message = ?, payment_raw = ?, updated_at = ? WHERE id = ?',
            ['failed', $message, json_encode($raw, JSON_UNESCAPED_UNICODE), now(), $order['id']]);
        log_activity('Ödeme başarısız', $order['order_no'] . ' · ' . $message, 'order', (int) $order['id'], $customer);
    }
    return row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
}
