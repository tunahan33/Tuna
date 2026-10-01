<?php
/** Demo modunda ödeme simülasyonu (Garanti bilgileri girilmeden önce akışı test etmek için) */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/garanti.php';

$user = require_login();
if (pos_mode() !== 'demo' || !is_post()) {
    http_response_code(403);
    exit('Demo ödeme kapalı.');
}
verify_csrf();
$order = row("SELECT * FROM orders WHERE order_no = ? AND user_id = ? AND status = 'pending'", [input('order_no'), $user['id']]);
if (!$order) {
    flash('error', 'Sipariş bulunamadı.');
    redirect('hesabim.php');
}
$success = input('result') === 'success';
$order = finalize_order($order, $success, $success ? 'Onaylandı (DEMO)' : 'Kart limiti yetersiz (DEMO)', $success ? 'DEMO-' . random_int(100000, 999999) : null, ['mode' => 'demo']);
redirect('odeme-sonuc.php?no=' . urlencode($order['order_no']) . '&t=' . order_access_token($order));
