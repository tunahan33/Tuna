<?php
/**
 * Garanti BBVA Sanal POS — 3D Secure entegrasyonu (GVP, apiversion 512).
 *
 * Desteklenen modeller:
 *  - 3D_OOS_PAY : Ortak Ödeme Sayfası. Müşteri kart bilgisini bankanın sayfasına girer.
 *                 Kart verisi sitemize hiç gelmez (PCI yükü en düşük model, önerilen).
 *  - 3D_PAY     : Kart formu sitemizde görünür ama form doğrudan bankaya POST edilir;
 *                 kart verisi yine sunucumuza ulaşmaz.
 *
 * Banka tarafından verilen bilgiler Süper Admin > Ayarlar > Sanal POS ekranından girilir:
 * Terminal ID, Üye İşyeri (Merchant) ID, Provizyon kullanıcı şifresi (PROVAUT), 3D Store Key.
 */

const GARANTI_URLS = [
    'test' => 'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine',
    'prod' => 'https://sanalposprov.garanti.com.tr/servlet/gt3dengine',
];

function pos_mode(): string
{
    $m = setting('pos_mode', 'demo');
    return in_array($m, ['demo', 'test', 'prod'], true) ? $m : 'demo';
}

function garanti_configured(): bool
{
    foreach (['pos_terminal_id', 'pos_merchant_id', 'pos_prov_password', 'pos_store_key'] as $k) {
        if (setting($k) === '') return false;
    }
    return true;
}

function garanti_security_data(): string
{
    $terminalId = setting('pos_terminal_id');
    return strtoupper(sha1(setting('pos_prov_password') . str_pad($terminalId, 9, '0', STR_PAD_LEFT)));
}

/** Bankaya POST edilecek form alanlarını üretir. */
function garanti_request(array $order): array
{
    $terminalId  = setting('pos_terminal_id');
    $storeKey    = setting('pos_store_key');
    $level       = setting('pos_security', '3D_OOS_PAY') === '3D_PAY' ? '3D_PAY' : '3D_OOS_PAY';
    $amount      = (string)(int)$order['amount'];     // kuruş: 1.250,00 TL => 125000
    $currency    = '949';                             // TRY
    $type        = 'sales';
    $installment = '';                                // peşin
    $successUrl  = abs_url('odeme/sonuc');
    $errorUrl    = abs_url('odeme/sonuc');

    $hash = strtoupper(hash('sha512',
        $terminalId . $order['order_no'] . $amount . $currency . $successUrl . $errorUrl
        . $type . $installment . $storeKey . garanti_security_data()
    ));

    return [
        'action' => GARANTI_URLS[pos_mode() === 'prod' ? 'prod' : 'test'],
        'level'  => $level,
        'fields' => [
            'mode'                  => pos_mode() === 'prod' ? 'PROD' : 'TEST',
            'apiversion'            => '512',
            'secure3dsecuritylevel' => $level,
            'terminalprovuserid'    => setting('pos_prov_user', 'PROVAUT'),
            'terminaluserid'        => setting('pos_user_id', 'PROVAUT'),
            'terminalmerchantid'    => setting('pos_merchant_id'),
            'terminalid'            => $terminalId,
            'orderid'               => $order['order_no'],
            'customeremailaddress'  => $order['email'],
            'customeripaddress'     => $order['ip'] ?: client_ip(),
            'txntype'               => $type,
            'txnamount'             => $amount,
            'txncurrencycode'       => $currency,
            'txninstallmentcount'   => $installment,
            'companyname'           => mb_substr(setting('site_name'), 0, 30),
            'successurl'            => $successUrl,
            'errorurl'              => $errorUrl,
            'secure3dhash'          => $hash,
            'lang'                  => 'tr',
            'txntimestamp'          => (string)time(),
            'refreshtime'           => '5',
        ],
    ];
}

/**
 * Bankadan dönen yanıtı doğrular.
 * Dönüş: ['ok' => bool, 'verified' => bool, 'message' => string, 'ref' => string]
 */
function garanti_verify(array $post, array $order): array
{
    $storeKey = setting('pos_store_key');
    $verified = false;

    $hashParams = (string)($post['hashparams'] ?? '');
    $given      = (string)($post['hash'] ?? $post['secure3dhash'] ?? '');
    if ($hashParams !== '' && $given !== '' && $storeKey !== '') {
        $digest = '';
        foreach (explode(':', $hashParams) as $p) {
            if ($p !== '') $digest .= (string)($post[$p] ?? '');
        }
        $digest .= $storeKey;
        $sha512 = strtoupper(hash('sha512', $digest));
        $sha1b64 = base64_encode(pack('H*', sha1($digest)));
        $verified = hash_equals($sha512, strtoupper($given)) || hash_equals($sha1b64, $given);
    }

    $procCode = (string)($post['procreturncode'] ?? '');
    $mdStatus = (string)($post['mdstatus'] ?? '');
    $amountOk = (string)($post['txnamount'] ?? $order['amount']) === (string)(int)$order['amount'];
    $orderOk  = (string)($post['orderid'] ?? $post['oid'] ?? '') === $order['order_no'];

    $message = trim((string)($post['errmsg'] ?? $post['mderrormessage'] ?? $post['response'] ?? ''));
    $ok = $verified && $orderOk && $amountOk && $procCode === '00' && in_array($mdStatus, ['1', '2', '3', '4'], true);

    if (!$verified)      $message = 'Banka yanıtı doğrulanamadı (hash). ' . $message;
    elseif (!$orderOk)   $message = 'Sipariş numarası eşleşmedi.';
    elseif (!$amountOk)  $message = 'Tutar eşleşmedi.';
    elseif ($ok)         $message = 'Onaylandı';
    elseif ($message === '') $message = 'Ödeme onaylanmadı (kod: ' . ($procCode ?: '-') . ', md: ' . ($mdStatus ?: '-') . ')';

    return [
        'ok'       => $ok,
        'verified' => $verified,
        'message'  => mb_substr($message, 0, 250),
        'ref'      => (string)($post['authcode'] ?? '') . (isset($post['hostrefnum']) ? ' / ' . $post['hostrefnum'] : ''),
    ];
}

/** Siparişi ödendi / başarısız olarak işaretler (bir kez). */
function finalize_order(array $order, bool $ok, string $message, string $ref = ''): void
{
    if ($order['status'] !== 'pending' && $order['status'] !== 'failed') return;
    if ($ok) {
        q('UPDATE orders SET status = ?, paid_at = ?, updated_at = ?, payment_ref = ?, payment_message = ? WHERE id = ?',
            ['paid', now(), now(), $ref, $message, $order['id']]);
        log_activity('payment_success', $order['order_no'] . ' — ' . money((int)$order['amount']) . ' — ' . $order['package_name'], null, $order['customer_name']);
    } else {
        q('UPDATE orders SET status = ?, updated_at = ?, payment_message = ? WHERE id = ?',
            ['failed', now(), $message, $order['id']]);
        log_activity('payment_failed', $order['order_no'] . ' — ' . $message, null, $order['customer_name']);
    }
}
