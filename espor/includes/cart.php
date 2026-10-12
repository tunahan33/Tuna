<?php
/**
 * Sepet: oturumda paket kimlikleri tutulur ($_SESSION['cart']). Her koçluk paketi sepete bir kez eklenir.
 * Sepet, ödemesi onaylanan siparişin sonuç sayfasında boşaltılır (odeme-sonuc.php).
 */

const CART_MAX_ITEMS = 20;

function cart_ids(): array
{
    $ids = $_SESSION['cart'] ?? [];
    return is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
}

/** Sepetteki aktif paketler (fiyatlar her zaman güncel veritabanından okunur) */
function cart_items(): array
{
    $ids = cart_ids();
    if (!$ids) {
        return [];
    }
    $rows = rows('SELECT p.*, s.title AS service_title, s.slug AS service_slug, s.icon AS service_icon FROM packages p JOIN services s ON s.id = p.service_id
        WHERE p.is_active = 1 AND s.is_active = 1 AND p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
    $byId = array_column($rows, null, 'id');
    $items = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $items[] = $byId[$id];
        }
    }
    if (count($items) !== count($ids)) {
        $_SESSION['cart'] = array_column($items, 'id'); // yayından kalkan paketleri temizle
    }
    return $items;
}

function cart_count(): int
{
    return count(cart_ids());
}

function cart_has(int $id): bool
{
    return in_array($id, cart_ids(), true);
}

function cart_total(array $items): float
{
    return round(array_sum(array_map(fn($p) => (float) $p['price'], $items)), 2);
}

/** Eklendiyse true, zaten sepetteyse false */
function cart_add(int $id): bool
{
    $ids = cart_ids();
    if (in_array($id, $ids, true)) {
        return false;
    }
    $ids[] = $id;
    $_SESSION['cart'] = array_slice($ids, -CART_MAX_ITEMS);
    return true;
}

function cart_remove(int $id): void
{
    $_SESSION['cart'] = array_values(array_filter(cart_ids(), fn($x) => $x !== $id));
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['checkout_order']);
}

/** Siparişin kalemleri (eski tek paketli siparişlerde de en az bir kalem bulunur) */
function order_items(array $order): array
{
    $items = rows('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
    if (!$items) {
        $items = [['package_id' => $order['package_id'], 'service_title' => $order['service_title'], 'package_name' => $order['package_name'],
            'duration' => '', 'price' => (float) $order['amount'] + (float) ($order['voucher_amount'] ?? 0)]];
    }
    return $items;
}

/** Sipariş özet alanları (listeler, raporlar, e-postalar için) */
function order_summary_fields(array $items): array
{
    if (count($items) === 1) {
        return ['service_title' => $items[0]['service_title'], 'package_name' => $items[0]['name'] ?? $items[0]['package_name']];
    }
    $names = array_map(fn($i) => $i['name'] ?? $i['package_name'], $items);
    return [
        'service_title' => mb_substr('Koçluk Paketleri (' . count($items) . ' paket)', 0, 160),
        'package_name' => mb_strimwidth(implode(', ', $names), 0, 160, '…'),
    ];
}
