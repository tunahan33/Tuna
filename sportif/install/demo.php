<?php
/**
 * YEREL TEST İÇİN örnek personel ve sipariş verisi oluşturur. Canlı sitede ÇALIŞTIRMAYIN.
 * Kullanım: php install/demo.php
 */
if (PHP_SAPI !== 'cli') exit('Yalnızca komut satırından çalıştırılabilir.');
define('ROOT', dirname(__DIR__));
$GLOBALS['__config'] = require ROOT . '/config.php';
date_default_timezone_set('Europe/Istanbul');
require ROOT . '/includes/db.php';
require ROOT . '/includes/functions.php';
require ROOT . '/includes/auth.php';
require ROOT . '/includes/shop.php';

$staff = [
    ['Ayşe Yönetici', 'yonetici@gssportif.local', 'admin'],
    ['Mert Editör', 'editor@gssportif.local', 'editor'],
    ['Selin Satış', 'satis@gssportif.local', 'sales'],
    ['Deniz Müşteri', 'uye@gssportif.local', 'member'],
];
$ids = [];
foreach ($staff as [$n, $e, $r]) {
    $ids[$r] = (int) (val('SELECT id FROM users WHERE email = ?', [$e]) ?: insert('users', ['name' => $n, 'email' => $e, 'phone' => '0532 000 00 00', 'password_hash' => password_hash('Test1234!', PASSWORD_DEFAULT), 'role' => $r, 'status' => 'active', 'created_at' => now()]));
}
$names = ['Ahmet Yılmaz', 'Zeynep Kaya', 'Emre Demir', 'Elif Çelik', 'Burak Şahin', 'Ece Arslan', 'Can Doğan', 'Merve Aydın', 'Kaan Öztürk', 'Derya Koç'];
$cities = [['İstanbul', 'Kadıköy'], ['Ankara', 'Çankaya'], ['İzmir', 'Karşıyaka'], ['Bursa', 'Nilüfer'], ['Antalya', 'Muratpaşa']];
$variants = rows('SELECT v.*, p.name, p.price FROM product_variants v JOIN products p ON p.id = v.product_id');
$statuses = ['paid', 'preparing', 'shipped', 'shipped', 'delivered', 'delivered', 'failed', 'pending'];
$cargos = array_keys(cargo_companies());
for ($i = 0; $i < 70; $i++) {
    $ts = time() - random_int(0, 40 * 86400);
    $st = $statuses[array_rand($statuses)];
    $name = $names[array_rand($names)];
    [$city, $district] = $cities[array_rand($cities)];
    $created = date('Y-m-d H:i:s', $ts);
    $lines = [];
    for ($k = 0, $n = random_int(1, 3); $k < $n; $k++) {
        $v = $variants[array_rand($variants)];
        $q = random_int(1, 2);
        $lines[] = [$v, $q, round($v['price'] * $q, 2)];
    }
    $sub = array_sum(array_column($lines, 2));
    $ship = $sub >= 1500 ? 0 : 89.90;
    $sale = in_array($st, SALE_STATUSES, true);
    $oid = insert('orders', [
        'order_no' => 'GSS' . date('ymdHis', $ts) . strtoupper(bin2hex(random_bytes(2))), 'user_id' => $ids['member'],
        'subtotal' => $sub, 'discount' => 0, 'shipping_fee' => $ship, 'amount' => $sub + $ship, 'item_count' => array_sum(array_column($lines, 1)),
        'customer_name' => $name, 'customer_email' => slugify($name) . '@ornek.com', 'customer_phone' => '05' . random_int(300000000, 599999999),
        'ship_name' => $name, 'ship_phone' => '0532 000 00 00', 'ship_address' => 'Örnek Mah. Spor Cad. No:' . random_int(1, 99), 'ship_district' => $district, 'ship_city' => $city,
        'customer_address' => 'Örnek Mah. Spor Cad.', 'customer_city' => $city, 'invoice_type' => 'bireysel',
        'status' => $st, 'stock_reduced' => 0, 'payment_method' => 'garanti', 'payment_ref' => $sale ? 'DEMO-' . random_int(100000, 999999) : null,
        'payment_message' => $st === 'failed' ? 'Yetersiz bakiye (DEMO)' : ($sale ? 'Onaylandı' : null),
        'cargo_company' => in_array($st, ['shipped', 'delivered']) ? $cargos[array_rand($cargos)] : null,
        'tracking_no' => in_array($st, ['shipped', 'delivered']) ? (string) random_int(100000000000, 999999999999) : null,
        'shipped_at' => in_array($st, ['shipped', 'delivered']) ? date('Y-m-d H:i:s', $ts + 86400) : null,
        'assigned_to' => in_array($st, ['preparing', 'shipped', 'delivered']) ? $ids['sales'] : null, 'contract_accepted_at' => $created, 'ip' => '127.0.0.1',
        'paid_at' => $sale ? date('Y-m-d H:i:s', $ts + 120) : null, 'created_at' => $created, 'updated_at' => $created,
    ]);
    foreach ($lines as [$v, $q, $lt]) {
        insert('order_items', ['order_id' => $oid, 'product_id' => $v['product_id'], 'variant_id' => $v['id'], 'product_name' => $v['name'], 'size' => $v['size'], 'color' => $v['color'], 'unit_price' => $v['price'], 'qty' => $q, 'line_total' => $lt]);
    }
    insert('activity_log', ['user_id' => $ids['member'], 'user_name' => $name, 'user_role' => 'member', 'action' => $sale ? 'Ödeme alındı' : 'Sipariş oluşturdu', 'entity' => 'order', 'entity_id' => $oid, 'details' => count($lines) . ' kalem · ' . money($sub + $ship), 'ip' => '127.0.0.1', 'created_at' => $created]);
}
insert('coupons', ['code' => 'HOSGELDIN10', 'type' => 'percent', 'value' => 10, 'min_total' => 500, 'max_uses' => 0, 'used_count' => 0, 'is_active' => 1, 'created_at' => now()]);
insert('team_requests', ['club_name' => 'Yıldızspor Gençlik Kulübü', 'contact_name' => 'Ali Kaya', 'email' => 'ali@yildizspor.org', 'phone' => '0533 111 22 33', 'city' => 'İzmir', 'sport' => 'Futbol', 'quantity' => 25, 'products' => 'Maç forması (isim-numara baskılı), Maç şortu, Çorap', 'needed_by' => date('Y-m-d', strtotime('+30 days')), 'message' => 'U14 takımımız için sarı-kırmızı forma, logo ve isim-numara baskılı.', 'status' => 'new', 'ip' => '127.0.0.1', 'created_at' => now(), 'updated_at' => now()]);
insert('messages', ['name' => 'Kulüp Başkanı Ali Bey', 'email' => 'ali@kulup.com', 'phone' => '0533 111 22 33', 'subject' => 'Ürün Bilgisi', 'message' => 'Eşofman takımının L bedeni ne zaman gelir?', 'status' => 'new', 'ip' => '127.0.0.1', 'created_at' => now()]);
echo "Demo veri eklendi. Personel şifreleri: Test1234!\n";
