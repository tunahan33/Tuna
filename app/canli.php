<?php
/**
 * Siteyi yayına hazırlar: demo siparişleri, talepleri, aktivite kayıtlarını ve test hesaplarını siler,
 * gerçek süper admin hesabını oluşturur. Ürünler, kategoriler, sayfalar ve ayarlar korunur.
 *
 * Kullanım (sunucuda):
 *   php app/canli.php --eposta=siz@ornek.com --sifre='GucluSifre123!' --ad='Ad Soyad' --adres=http://80.253.246.203
 */
if (PHP_SAPI !== 'cli') {
    exit('Yalnızca terminalden çalıştırılabilir.');
}
require __DIR__ . '/bootstrap.php';

$opt = getopt('', ['eposta:', 'sifre:', 'ad::', 'adres::']);
$email = mb_strtolower(trim((string) ($opt['eposta'] ?? '')));
$pass = (string) ($opt['sifre'] ?? '');
$name = trim((string) ($opt['ad'] ?? 'Süper Admin')) ?: 'Süper Admin';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Geçerli bir e-posta girin: --eposta=siz@ornek.com\n");
}
if (strlen($pass) < 10 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
    exit("Şifre en az 10 karakter olmalı, harf ve rakam içermelidir.\n");
}

$pdo = db();
$pdo->beginTransaction();
q('DELETE FROM order_history');
q('DELETE FROM order_items');
q('DELETE FROM orders');
q('DELETE FROM requests');
q('DELETE FROM activity');
q("DELETE FROM users WHERE email LIKE '%@gssportif.local'");
q("DELETE FROM sqlite_sequence WHERE name IN ('orders','order_items','order_history','requests','activity')");

$existing = row('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    q("UPDATE users SET name = ?, password_hash = ?, role = 'super_admin', active = 1 WHERE id = ?", [$name, password_hash($pass, PASSWORD_DEFAULT), $existing['id']]);
} else {
    insert('users', ['name' => $name, 'email' => $email, 'phone' => setting('company_phone'), 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
        'role' => 'super_admin', 'active' => 1, 'created_at' => now()]);
}
if (!empty($opt['adres'])) {
    save_setting('site_url', rtrim((string) $opt['adres'], '/'));
}
save_setting('canli', '1');
insert('activity', ['user_id' => null, 'user_name' => 'Sistem', 'role' => 'sistem', 'action' => 'Site yayına hazırlandı',
    'details' => 'Demo veriler silindi, süper admin: ' . $email, 'link' => '', 'ip' => 'cli', 'created_at' => now()]);
$pdo->commit();

echo "Site yayına hazır.\n";
echo "Demo siparişler, talepler ve test hesapları silindi.\n";
echo "Süper admin: $email\n";
