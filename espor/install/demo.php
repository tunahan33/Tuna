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

if (val('SELECT id FROM users WHERE email = ?', ['uye@gssportif.local'])) {
    exit("Demo veri zaten ekli.\n");
}

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
$packages = rows('SELECT p.*, s.title AS st FROM packages p JOIN services s ON s.id = p.service_id');
$statuses = ['paid', 'paid', 'processing', 'completed', 'completed', 'failed', 'pending'];
for ($i = 0; $i < 60; $i++) {
    $p = $packages[array_rand($packages)];
    $ts = time() - random_int(0, 40 * 86400);
    $st = $statuses[array_rand($statuses)];
    $name = $names[array_rand($names)];
    $created = date('Y-m-d H:i:s', $ts);
    $oid = insert('orders', [
        'order_no' => 'GSE' . date('ymdHis', $ts) . strtoupper(bin2hex(random_bytes(2))), 'user_id' => $ids['member'], 'package_id' => $p['id'],
        'service_title' => $p['st'], 'package_name' => $p['name'], 'amount' => $p['price'], 'customer_name' => $name,
        'customer_email' => slugify($name) . '@ornek.com', 'customer_phone' => '05' . random_int(300000000, 599999999),
        'customer_address' => 'Örnek Mah. Spor Cad. No:' . random_int(1, 99), 'customer_city' => 'İstanbul', 'invoice_type' => 'bireysel',
        'status' => $st, 'payment_method' => 'garanti', 'payment_ref' => in_array($st, SALE_STATUSES) ? 'DEMO-' . random_int(100000, 999999) : null,
        'payment_message' => $st === 'failed' ? 'Yetersiz bakiye (DEMO)' : (in_array($st, SALE_STATUSES) ? 'Onaylandı' : null),
        'assigned_to' => in_array($st, ['processing', 'completed']) ? $ids['sales'] : null, 'contract_accepted_at' => $created, 'ip' => '127.0.0.1',
        'paid_at' => in_array($st, SALE_STATUSES) ? date('Y-m-d H:i:s', $ts + 120) : null, 'created_at' => $created, 'updated_at' => $created,
    ]);
    insert('activity_log', ['user_id' => $ids['member'], 'user_name' => $name, 'user_role' => 'member', 'action' => in_array($st, SALE_STATUSES) ? 'Ödeme alındı' : 'Sipariş oluşturdu', 'entity' => 'order', 'entity_id' => $oid, 'details' => $p['st'] . ' / ' . $p['name'] . ' · ' . money($p['price']), 'ip' => '127.0.0.1', 'created_at' => $created]);
}
insert('messages', ['type' => 'iletisim', 'name' => 'Takım Kaptanı Ali', 'email' => 'ali@ornektakim.com', 'phone' => '0533 111 22 33', 'subject' => 'Takım & Turnuva Koçluğu', 'message' => 'Üniversite Valorant takımımız için sezon hazırlık paketi hakkında bilgi almak istiyoruz.', 'status' => 'new', 'ip' => '127.0.0.1', 'created_at' => now()]);
insert('messages', ['type' => 'ariza', 'ticket_no' => 'ARZ' . date('ymd') . 'DEMO', 'name' => 'Zeynep Kaya', 'email' => 'zeynep-kaya@ornek.com', 'phone' => '', 'subject' => 'Ders bağlantısı / Discord sorunu', 'message' => "Sorun türü: Ders bağlantısı / Discord sorunu\n\nDers sırasında ekran paylaşımı donuyor.", 'status' => 'new', 'ip' => '127.0.0.1', 'created_at' => now()]);
echo "Demo veri eklendi. Personel şifreleri: Test1234!\n";
