<?php
/**
 * İade çekleri: iade tutarının kart yerine (isteğe bağlı olarak %10 fazlasıyla) bakiye kodu olarak tanımlanması.
 * Kod yalnızca tanımlandığı e-posta adresiyle verilen siparişlerde kullanılabilir, 12 ay geçerlidir,
 * kalan bakiye sonraki siparişlerde kullanılabilir. Bakiye, ödeme onaylandığında düşülür.
 */

const VOUCHER_BONUS_RATE = 0.10;   // Kart iadesi yerine iade çeki seçilirse eklenen oran
const VOUCHER_VALID_MONTHS = 12;

const VOUCHER_STATUSES = [
    'active'    => ['Kullanılabilir', 'green'],
    'used'      => ['Tamamen kullanıldı', 'gray'],
    'expired'   => ['Süresi doldu', 'yellow'],
    'cancelled' => ['İptal edildi', 'red'],
];

function voucher_generate_code(): string
{
    $chars = 'ABCDEFGHJKLMNPRSTUVYZ23456789'; // karışan harf/rakamlar çıkarıldı
    do {
        $c = 'IC-';
        for ($i = 0; $i < 8; $i++) {
            $c .= $chars[random_int(0, strlen($chars) - 1)];
            if ($i === 3) $c .= '-';
        }
    } while (row('SELECT id FROM vouchers WHERE code = ?', [$c]));
    return $c;
}

function voucher_normalize(string $code): string
{
    return strtoupper(preg_replace('/\s+/', '', $code));
}

/** Süresi geçenleri işaretler ve kodu döner */
function voucher_find(string $code): ?array
{
    $v = row('SELECT * FROM vouchers WHERE code = ?', [voucher_normalize($code)]);
    if ($v && $v['status'] === 'active' && strtotime($v['expires_at']) < time()) {
        q("UPDATE vouchers SET status = 'expired' WHERE id = ?", [$v['id']]);
        $v['status'] = 'expired';
    }
    return $v;
}

/**
 * Kodun bu e-posta ile kullanılabilirliğini kontrol eder.
 * Başarılıysa [çek, düşülecek tutar, null], değilse [null, 0, hata mesajı] döner.
 */
function voucher_check(string $code, string $email, float $price): array
{
    $v = voucher_find($code);
    if (!$v) {
        return [null, 0.0, 'İade çeki kodu bulunamadı.'];
    }
    if ($v['status'] !== 'active') {
        return [null, 0.0, 'Bu iade çeki kullanılamaz: ' . VOUCHER_STATUSES[$v['status']][0] . '.'];
    }
    if (mb_strtolower(trim($v['customer_email'])) !== mb_strtolower(trim($email))) {
        return [null, 0.0, 'İade çeki yalnızca tanımlandığı e-posta adresiyle verilen siparişlerde kullanılabilir. Sipariş e-postanızı kontrol edin.'];
    }
    $use = round(min((float) $v['balance'], $price), 2);
    if ($use <= 0) {
        return [null, 0.0, 'İade çekinin bakiyesi kalmamış.'];
    }
    return [$v, $use, null];
}

/** Ödeme onaylandığında bakiyeden düşer (finalize_order çağırır) */
function voucher_redeem(array $order): void
{
    if (empty($order['voucher_code']) || (float) $order['voucher_amount'] <= 0) {
        return;
    }
    $v = row('SELECT * FROM vouchers WHERE code = ?', [$order['voucher_code']]);
    if (!$v || row('SELECT id FROM voucher_uses WHERE voucher_id = ? AND order_id = ?', [$v['id'], $order['id']])) {
        return;
    }
    $use = round(min((float) $v['balance'], (float) $order['voucher_amount']), 2);
    $left = round((float) $v['balance'] - $use, 2);
    q('UPDATE vouchers SET balance = ?, status = ? WHERE id = ?', [$left, $left <= 0 ? 'used' : $v['status'], $v['id']]);
    insert('voucher_uses', ['voucher_id' => $v['id'], 'order_id' => $order['id'], 'order_no' => $order['order_no'], 'amount' => $use, 'created_at' => now()]);
    log_activity('İade çeki kullanıldı', $v['code'] . ' · ' . money($use) . ' · Sipariş ' . $order['order_no'] . ' · Kalan ' . money($left), 'voucher', (int) $v['id']);
}

function voucher_badge(string $status): string
{
    [$l, $c] = VOUCHER_STATUSES[$status] ?? [$status, 'gray'];
    return '<span class="badge badge-' . $c . '">' . e($l) . '</span>';
}
