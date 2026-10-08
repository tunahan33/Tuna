<?php
/**
 * Ödeme altyapısı
 *
 * Şu anda bağlı bir ödeme sağlayıcısı (sanal POS / ödeme kuruluşu) yoktur. Müşteri paketi seçip
 * fatura bilgilerini girer ve sözleşmeleri onaylar; sipariş "Ödeme Bekliyor" olarak kaydedilir,
 * ancak kartla tahsilat yapılmaz. Tamamı iade çekiyle karşılanan siparişler yine tamamlanır.
 *
 * Bir sağlayıcı bağlandığında:
 *   1. payment_provider_active() true döndürür,
 *   2. payment_start($order) müşteriyi sağlayıcının güvenli ödeme sayfasına yönlendirir,
 *   3. sağlayıcının dönüş adresinde sonuç finalize_order() ile siparişe işlenir.
 */

function payment_provider_active(): bool
{
    return false;
}

/** Sözleşmelerde gösterilen ödeme şekli */
function payment_methods_text(): string
{
    return 'Kredi kartı / banka kartı ile tek çekim (3D Secure)';
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
