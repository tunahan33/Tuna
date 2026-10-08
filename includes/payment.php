<?php
/**
 * Ödeme katmanı.
 *
 * Şu an siteye bağlı bir online ödeme kuruluşu yoktur: müşteri sözleşmeleri onaylayıp siparişini oluşturur,
 * ekibimiz ödeme için müşteriyle iletişime geçer, ödeme alındığında sipariş panelden “Ödendi” yapılır.
 *
 * İleride bir ödeme kuruluşu (iyzico, PayTR vb.) bağlanacağında yalnızca bu dosya genişletilir:
 * payment_provider() kuruluş adını döndürür, online ödeme adımı ve dönüş doğrulaması buraya eklenir.
 */

function payment_provider(): ?string
{
    return null;
}

function online_payment_available(): bool
{
    return payment_provider() !== null;
}

/**
 * Siparişi ödendi olarak işaretler: ödeme tarihini kaydeder, müşteriye ve yönetime e-posta gönderir.
 * Yalnızca ödeme bekleyen siparişlerde çalışır (tekrar işlenmez).
 */
function mark_order_paid(array $order, string $message, ?string $ref = null, ?array $actor = null): array
{
    if ($order['paid_at'] || !in_array($order['status'], ['pending', 'failed'], true)) {
        return $order;
    }
    q('UPDATE orders SET status = ?, payment_ref = ?, payment_message = ?, paid_at = ?, updated_at = ? WHERE id = ?',
        ['paid', $ref, mb_substr($message, 0, 500), now(), now(), $order['id']]);
    log_activity('Ödeme alındı', $order['order_no'] . ' · ' . $order['package_name'] . ' · ' . money($order['amount']), 'order', (int) $order['id'], $actor);

    send_mail($order['customer_email'], 'Ödemeniz alındı - ' . $order['order_no'],
        '<p>Merhaba ' . e($order['customer_name']) . ',</p><p><b>' . e($order['service_title']) . ' - ' . e($order['package_name']) . '</b> siparişiniz için ödemeniz alındı.</p>'
        . '<p>Sipariş No: <b>' . e($order['order_no']) . '</b><br>Tutar: <b>' . money($order['amount']) . '</b> (KDV dahil)</p>'
        . '<p>Danışmanınız en geç 3 iş günü içinde sizinle iletişime geçerek ilk görüşmeyi planlayacak.</p>');
    return row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
}
