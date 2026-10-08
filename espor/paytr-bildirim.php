<?php
/**
 * PayTR Bildirim URL (callback). PayTR panelinde: Destek & Kurulum > Ayarlar > Bildirim URL
 *   https://www.gssportiffaaliyetler.com/paytr-bildirim.php
 * Ödeme sonucu yalnızca bu adreste, PayTR imzası doğrulanarak siparişe işlenir.
 * PayTR, yanıt olarak düz metin "OK" görene kadar bildirimi tekrarlar.
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/payment.php';

header('Content-Type: text/plain; charset=utf-8');
if (!is_post() || !isset($_POST['merchant_oid'], $_POST['status'], $_POST['total_amount'], $_POST['hash'])) {
    http_response_code(400);
    exit('Bad request');
}
$post = array_map(fn($v) => is_string($v) ? $v : '', $_POST);

if (!paytr_configured() || !hash_equals(paytr_callback_hash($post), $post['hash'])) {
    log_activity('Ödeme doğrulama hatası', 'PayTR bildirimi imzası geçersiz · ' . mb_substr($post['merchant_oid'], 0, 64), 'order');
    exit('PAYTR notification failed: bad hash');
}

$attempt = row('SELECT * FROM payment_attempts WHERE oid = ?', [$post['merchant_oid']]);
$order = $attempt ? row('SELECT * FROM orders WHERE id = ?', [$attempt['order_id']]) : null;
if (!$attempt || !$order) {
    log_activity('Ödeme bildirimi eşleşmedi', 'PayTR merchant_oid: ' . mb_substr($post['merchant_oid'], 0, 64), 'order');
    exit('OK'); // bilinmeyen bildirimi tekrar göndermesin
}
if ($attempt['status'] === 'success' || $attempt['status'] === 'failed') {
    exit('OK'); // aynı bildirim tekrar geldi
}

$raw = $post;
unset($raw['hash']);
$raw['provider'] = 'paytr';
$test = ($post['test_mode'] ?? '0') === '1';

if ($post['status'] === 'success') {
    // payment_amount: sepet tutarı (kuruş); total_amount taksit farkını da içerebilir
    $paid = isset($post['payment_amount']) && $post['payment_amount'] !== '' ? (int) $post['payment_amount'] : (int) $post['total_amount'];
    if ($paid !== (int) $attempt['amount']) {
        q("UPDATE payment_attempts SET status = 'mismatch', updated_at = ? WHERE id = ?", [now(), $attempt['id']]);
        log_activity('Ödeme tutarı uyuşmazlığı', $order['order_no'] . ' · beklenen ' . $attempt['amount'] . ' kuruş, gelen ' . $paid . ' kuruş', 'order', (int) $order['id']);
        send_mail(setting('notify_email'), 'DİKKAT: Ödeme tutarı uyuşmazlığı ' . $order['order_no'], '<p>PayTR bildiriminde tutar sipariş tutarıyla eşleşmedi. Siparişi ve PayTR panelini kontrol edin.</p>');
        exit('OK');
    }
    q("UPDATE payment_attempts SET status = 'success', updated_at = ? WHERE id = ?", [now(), $attempt['id']]);
    q("UPDATE orders SET payment_method = 'kart' WHERE id = ?", [$order['id']]);
    $order['payment_method'] = 'kart';
    $msg = 'Onaylandı (PayTR' . ($test ? ' TEST' : '') . ')' . ((int) $post['total_amount'] > $paid ? ' · taksitli, çekilen ' . money((int) $post['total_amount'] / 100) : '');
    finalize_order($order, true, $msg, $post['merchant_oid'], $raw);
} else {
    q("UPDATE payment_attempts SET status = 'failed', updated_at = ? WHERE id = ?", [now(), $attempt['id']]);
    $reason = trim(($post['failed_reason_msg'] ?? '') . (($post['failed_reason_code'] ?? '') !== '' ? ' (kod ' . $post['failed_reason_code'] . ')' : ''));
    finalize_order($order, false, mb_substr($reason ?: 'Ödeme tamamlanamadı.', 0, 400), null, $raw);
}
echo 'OK';
