<?php
/**
 * PayTR ödeme bildirimi (Bildirim URL). PayTR Mağaza Paneli'nde şu adres tanımlanmalıdır:
 *   https://www.gssportifurunler.net/paytr-bildirim.php
 * Ödemenin kesin sonucu yalnızca buradan alınır. Yanıt olarak tam olarak "OK" dönülmelidir;
 * aksi hâlde PayTR bildirimi tekrar gönderir.
 */
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
if (!is_post()) {
    http_response_code(405);
    exit('POST bekleniyor');
}
if (!paytr_verify_callback($_POST)) {
    http_response_code(400);
    log_activity('PayTR bildirimi reddedildi', 'Geçersiz imza · ' . mb_substr((string) ($_POST['merchant_oid'] ?? ''), 0, 40), '', ['id' => null, 'name' => 'PayTR', 'role' => 'sistem']);
    exit('PAYTR notification failed: bad hash');
}

$no = (string) $_POST['merchant_oid'];
$order = row('SELECT * FROM orders WHERE order_no = ?', [$no]);
if (!$order) {
    exit('OK'); // bilinmeyen / yeniden denemeyle değişmiş sipariş numarası: tekrar gönderilmesin
}

if ($_POST['status'] === 'success') {
    $paid = number_format(((int) ($_POST['total_amount'] ?? 0)) / 100, 2, ',', '.') . ' ₺';
    $note = 'Ödeme alındı (PayTR' . (($_POST['test_mode'] ?? '0') === '1' ? ' TEST' : '') . ', tahsil edilen: ' . $paid
        . (!empty($_POST['installment_count']) && (int) $_POST['installment_count'] > 1 ? ', ' . (int) $_POST['installment_count'] . ' taksit' : '') . ')';
    // Sipariş tutarı ile PayTR'ye gönderilen tutar karşılaştırılır (taksit farkı total_amount'a yansıyabilir)
    if (isset($_POST['payment_amount']) && (int) $_POST['payment_amount'] !== (int) round((float) $order['total'] * 100)) {
        order_history((int) $order['id'], 'DİKKAT: PayTR tutarı sipariş tutarından farklı (' . (int) $_POST['payment_amount'] . ' kuruş)', 'Sistem');
    }
    order_mark_paid($order, 'PayTR', $note);
} elseif ($order['status'] === 'odeme_bekliyor') {
    $reason = trim(($_POST['failed_reason_code'] ?? '') . ' ' . ($_POST['failed_reason_msg'] ?? ''));
    update('orders', ['status' => 'odeme_basarisiz', 'updated_at' => now()], (int) $order['id']);
    order_history((int) $order['id'], 'Ödeme başarısız' . ($reason !== '' ? ': ' . mb_substr($reason, 0, 200) : ''), 'PayTR');
}
exit('OK');
