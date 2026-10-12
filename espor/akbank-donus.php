<?php
/**
 * Akbank Sanal POS dönüş adresi (okUrl / failUrl). Müşterinin tarayıcısı Akbank ödeme sayfasından
 * buraya POST ile döner. Sonuç yalnızca Akbank imzası (HMAC-SHA512, secretKey) ve iş yeri / terminal
 * numaraları doğrulandıktan sonra siparişe işlenir; doğrulanamayan dönüşler siparişi değiştirmez.
 */
define('GSE_NO_SESSION', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/payment.php';

if (!is_post() || empty($_POST['orderId'])) {
    redirect('');
}
$post = array_map(fn($v) => is_string($v) ? $v : '', $_POST);

$attempt = row("SELECT * FROM payment_attempts WHERE oid = ? AND provider = 'akbank'", [$post['orderId']]);
$order = $attempt ? row('SELECT * FROM orders WHERE id = ?', [$attempt['order_id']]) : null;
if (!$attempt || !$order) {
    log_activity('Ödeme dönüşü eşleşmedi', 'Akbank orderId: ' . mb_substr($post['orderId'], 0, 64), 'order');
    redirect('iletisim.php?konu=' . urlencode('Ödeme sonucu eşleşmedi: ' . mb_substr($post['orderId'], 0, 40)));
}
$back = fn(string $durum) => redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order) . '&durum=' . $durum);

// İmza secretKey ile atıldığı için kaynağı kanıtlar. Başarılı dönüşte iş yeri/terminal numaraları zorunludur;
// ret dönüşlerinde Akbank bu alanları göndermeyebilir, gönderdiyse eşleşmeleri gerekir.
$success = ($post['responseCode'] ?? '') === AKBANK_SUCCESS_CODE;
$idOk = fn(string $field, string $setting) => isset($post[$field]) ? hash_equals(trim(setting($setting)), $post[$field]) : !$success;
$valid = akbank_response_hash_valid($post)
    && $idOk('merchantSafeId', 'akbank_merchant_safe_id')
    && $idOk('terminalSafeId', 'akbank_terminal_safe_id');
if (!$valid) {
    q("UPDATE payment_attempts SET status = 'invalid', updated_at = ? WHERE id = ? AND status = 'started'", [now(), $attempt['id']]);
    log_activity('Ödeme doğrulama hatası', $order['order_no'] . ' · Akbank dönüş imzası doğrulanamadı (' . mb_substr($post['responseCode'] ?? '', 0, 20) . ' ' . mb_substr($post['responseMessage'] ?? '', 0, 120) . ')', 'order', (int) $order['id']);
    $back('hata');
}
if ($attempt['status'] !== 'started') {
    $back($attempt['status'] === 'success' ? 'ok' : 'hata'); // aynı dönüş tekrar geldi
}

$raw = $post;
unset($raw['hash']);
$raw['provider'] = 'akbank';

if ($success) {
    q("UPDATE payment_attempts SET status = 'success', updated_at = ? WHERE id = ?", [now(), $attempt['id']]);
    q("UPDATE orders SET payment_method = 'kart' WHERE id = ?", [$order['id']]);
    $order['payment_method'] = 'kart';
    $ref = trim(($post['authCode'] ?? '') . ' / ' . ($post['rrn'] ?? ''), ' /') ?: $post['orderId'];
    finalize_order($order, true, 'Onaylandı (Akbank' . ($attempt['test_mode'] ? ' TEST' : '') . ')', $ref, $raw);
    $back('ok');
}

q("UPDATE payment_attempts SET status = 'failed', updated_at = ? WHERE id = ?", [now(), $attempt['id']]);
$reason = trim(($post['hostMessage'] ?? '') ?: ($post['responseMessage'] ?? '')) ?: 'Ödeme tamamlanamadı.';
finalize_order($order, false, mb_substr($reason . ' (' . ($post['responseCode'] ?? '-') . ')', 0, 400), null, $raw);
$back('hata');
